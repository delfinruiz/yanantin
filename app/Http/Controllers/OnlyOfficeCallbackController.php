<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OnlyOfficeCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // 📥 Log inicial
        Log::channel('daily')->info('[DEBUG ONLYOFFICE] Callback recibido', [
            'headers' => $request->header(),
            'payload' => $request->all(),
        ]);

        $data = $request->all();

        $status      = $data['status'] ?? null;
        $fileUrl     = $data['url'] ?? null;
        $documentKey = $data['key'] ?? null;

        /**
         * OnlyOffice:
         * status = 2 → documento listo para guardar
         * status = 6 → documento forzado a guardar (force save)
         */
        if (!in_array($status, [2, 6]) || !$fileUrl || !$documentKey) {
            Log::channel('daily')->info('[DEBUG ONLYOFFICE] Sin cambios para guardar o datos incompletos', [
                'status' => $status,
                'has_url' => !empty($fileUrl),
                'has_key' => !empty($documentKey)
            ]);

            return response()->json(['error' => 0]);
        }

        /* =====================================================
         | 1. Leer key_map.json
         ===================================================== */
        $keyMapPath = storage_path('app/onlyoffice/key_map.json');

        if (!file_exists($keyMapPath)) {
            Log::channel('daily')->error('[DEBUG ONLYOFFICE] key_map.json no existe en: ' . $keyMapPath);

            return response()->json(['error' => 1]);
        }

        $keyMap = json_decode(file_get_contents($keyMapPath), true);

        if (!isset($keyMap[$documentKey])) {
            Log::channel('daily')->error('[DEBUG ONLYOFFICE] Clave de documento no encontrada en mapa', [
                'key_recibida' => $documentKey,
                'keys_disponibles_count' => count($keyMap)
            ]);

            return response()->json(['error' => 1]);
        }

        /* =====================================================
         | 2. Resolver ruta destino
         ===================================================== */
        $relativePath = ltrim(
            str_replace('//', '/', $keyMap[$documentKey]),
            '/'
        );

        Log::channel('daily')->info('[DEBUG ONLYOFFICE] Ruta destino resuelta', ['path' => $relativePath]);

        $disk = Storage::disk('public');

        /* =====================================================
         | 3. Descargar archivo desde OnlyOffice (cURL)
         ===================================================== */
        Log::channel('daily')->info('[DEBUG ONLYOFFICE] Iniciando descarga desde OnlyOffice', ['url' => $fileUrl]);
        
        $fileContent = $this->downloadFromOnlyOffice($fileUrl);

        if ($fileContent === false || strlen($fileContent) === 0) {
            Log::channel('daily')->error('[DEBUG ONLYOFFICE] Archivo descargado vacío o fallido', [
                'url' => $fileUrl,
            ]);

            return response()->json(['error' => 1]);
        }

        /* =====================================================
         | 4. Asegurar directorio destino
         ===================================================== */
        $directory = dirname($relativePath);

        if (!$disk->exists($directory)) {
            Log::channel('daily')->info('[DEBUG ONLYOFFICE] Creando directorio', ['dir' => $directory]);
            $disk->makeDirectory($directory);
        }

        /* =====================================================
         | 5. Guardar archivo en disco
         ===================================================== */
        if (!$disk->put($relativePath, $fileContent)) {
            Log::channel('daily')->error('[DEBUG ONLYOFFICE] Error escribiendo archivo en disco', [
                'path' => $relativePath,
            ]);

            return response()->json(['error' => 1]);
        }

        Log::channel('daily')->info('[DEBUG ONLYOFFICE] Archivo guardado correctamente en disco', [
            'path' => $relativePath,
            'size' => strlen($fileContent),
        ]);

        /* =====================================================
         | 6. Sincronizar con Base de Datos (FileItem)
         ===================================================== */
        try {
            // $relativePath es tipo: users/1/docs/reporte.docx
            $parts = explode('/', $relativePath);
            
            // Validar estructura mínima: users/{id}/{file}
            if (count($parts) >= 3 && $parts[0] === 'users') {
                $userId = (int) $parts[1];
                $fileName = array_pop($parts); // Saca el nombre del final
                
                // Reconstruir path lógico (sin users/id y sin nombre)
                // parts ahora contiene ['users', '1', 'docs'] (ejemplo)
                $logicalPath = '/';
                if (count($parts) > 2) {
                    $logicalPath = '/' . implode('/', array_slice($parts, 2)) . '/';
                }

                Log::channel('daily')->info('[DEBUG ONLYOFFICE] Buscando archivo en BD', [
                    'user_id' => $userId,
                    'name' => $fileName,
                    'logical_path_calculated' => $logicalPath
                ]);

                // Buscar y actualizar (flexible path para root)
                $query = FileItem::where('user_id', $userId)
                    ->where('name', $fileName);
                
                if ($logicalPath === '/') {
                    // Si es root, aceptar '/', '' o null
                    $query->where(function($q) {
                        $q->where('path', '/')
                          ->orWhere('path', '')
                          ->orWhereNull('path');
                    });
                } else {
                    $query->where('path', $logicalPath);
                }

                $fileItem = $query->first();

                if ($fileItem) {
                    $fileItem->update([
                        'size' => strlen($fileContent),
                        'mime_type' => mime_content_type($disk->path($relativePath)) ?: 'application/octet-stream',
                    ]);
                    
                    Log::channel('daily')->info('[DEBUG ONLYOFFICE] BD sincronizada correctamente', [
                        'id' => $fileItem->id,
                        'new_size' => strlen($fileContent)
                    ]);
                } else {
                    Log::channel('daily')->warning('[DEBUG ONLYOFFICE] No se encontró registro en BD', [
                        'user_id' => $userId,
                        'path_searched' => $logicalPath,
                        'name' => $fileName
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('OnlyOffice: Error sincronizando BD', ['error' => $e->getMessage()]);
        }

        return response()->json(['error' => 0]);
    }

    /**
     * Descarga segura desde OnlyOffice usando Http Client (Guzzle)
     * Reemplaza cURL nativo para evitar deprecaciones y mejorar compatibilidad.
     */
    private function downloadFromOnlyOffice(string $url): string|false
    {
        // Reemplazar localhost por IP de docker si es necesario, o usar URL tal cual
        // En entornos dev locales, a veces el contenedor de OnlyOffice devuelve una URL interna
        // que Laravel (en el host o en otro contenedor) no puede alcanzar.
        // Pero normalmente la URL de descarga que envía OnlyOffice es accesible si se configuró bien.
        
        try {
            $response = Http::withOptions([
                'verify'          => false, 
                'connect_timeout' => 15,
                'timeout'         => 120,
            ])
            ->withUserAgent('Laravel-OnlyOffice')
            ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('OnlyOffice: Download failed', [
                'url'    => $url,
                'status' => $response->status(),
                'error'  => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('OnlyOffice: Download exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}