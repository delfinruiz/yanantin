<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Models\FileShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;


class OnlyOfficeController extends Controller
{
    public function downloadInternal(Request $request, FileItem $fileItem)
    {
        // Validar firma (ya lo hace el middleware 'signed', pero por seguridad extra)
        if (!$request->hasValidSignature()) {
             abort(403);
        }

        $name = $fileItem->filename ?? $fileItem->name;
        $path = trim(
            "users/{$fileItem->user_id}/" . trim($fileItem->path, '/') . '/' . $name,
            '/'
        );

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    public function openPublic($token, Request $request)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es válido.');
        }

        $fileItem = $link->fileItem;

        // Si es carpeta, manejamos la navegación de archivos dentro (si aplica)
        // Pero OnlyOffice es para un archivo específico. 
        // Si el enlace es a una carpeta, el usuario debería haber hecho clic en "Abrir" en un archivo específico dentro de esa carpeta.
        // En ese caso, la URL debería incluir el path relativo del archivo deseado, pero el token sigue siendo el de la carpeta raíz compartida.
        // Sin embargo, para simplificar, asumiremos que si es carpeta, el request trae un 'path' query param.
        
        $targetFile = $fileItem;

        if ($fileItem->is_folder) {
            $relativePath = $request->query('path', '');
            $relativePath = trim($relativePath, '/');

            if ($relativePath === '') {
                 abort(400, 'Se requiere especificar un archivo dentro de la carpeta.');
            }

            // Buscar el archivo hijo
            $rootLogicalPath = trim($fileItem->path . $fileItem->name, '/');
            $targetLogicalPath = trim($rootLogicalPath . '/' . dirname($relativePath), '/');
            // Si dirname es '.', path es rootLogicalPath
            if (dirname($relativePath) === '.') {
                $targetLogicalPath = $rootLogicalPath;
            }
            
            $fileName = basename($relativePath);

            $targetFile = FileItem::where('user_id', $fileItem->user_id)
                ->where('path', $this->normalizePath('/' . $targetLogicalPath)) // normalizePath añade '/' al inicio si falta
                ->where('name', $fileName)
                ->where('is_folder', false)
                ->firstOrFail();
        }

        // Permisos
        // El permiso viene del link: 'view' o 'edit'
        $permission = $link->permission;
        $isEditable = ($permission === 'edit');

        // Path físico
        $path = trim(
            "users/{$targetFile->user_id}/" . trim($targetFile->path, '/') . '/' . $targetFile->name,
            '/'
        );

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Corrección: Usar Storage::disk('public')->path() en lugar de public_path()
        // Esto es más robusto porque public_path() asume una estructura de carpetas específica
        // que puede no coincidir con donde el link simbólico o el disco apunta realmente.
        $absolutePath = Storage::disk('public')->path($path);
        
        // Verificación adicional de existencia física
        if (!file_exists($absolutePath)) {
            // Intento de fallback si el path no es accesible directamente
            // A veces Storage::path devuelve rutas absolutas correctas incluso si es un link simbólico
             abort(404, 'Archivo físico no encontrado: ' . $absolutePath);
        }

        // Generar key única
        $docKey = md5($path . filemtime($absolutePath));
        
        // Registrar key mapping (reutilizando lógica existente)
        $this->registerKeyMap($docKey, $path);

        $config = [
            "document" => [
                "fileType" => pathinfo($targetFile->name, PATHINFO_EXTENSION),
                "key"      => $docKey,
                "title"    => $targetFile->name,
                // Usar una ruta pública específica para descarga que no exponga la ruta física directa si es posible,
                // pero si usamos asset(), debe ser accesible públicamente.
                // En este caso, asset("storage/{$path}") genera http://dominio/storage/users/1/doc.docx
                // Asegurarse de que el servidor web (Nginx/Apache) permite servir archivos de esa ruta.
                // Si hay problemas de CORS o acceso, OnlyOffice fallará al descargar.
                // Como alternativa, podemos usar una ruta proxy de descarga si asset() falla por temas de red interna vs externa.
                "url"      => route('public.download.onlyoffice', ['token' => $token]),
                "permissions" => [
                    "edit" => $isEditable,
                    "download" => true,
                    "print" => true,
                    "review" => $isEditable,
                ],
            ],
            "editorConfig" => [
                "callbackUrl" => route('onlyoffice.callback'),
                "lang" => "es",
                "locale" => "es",
                "region" => "es-ES",
                "mode" => $isEditable ? "edit" : "view",
                "user" => [
                    "id" => "guest-" . uniqid(),
                    "name" => "Invitado",
                ],
            ],
            "documentType" => $this->getDocumentType(pathinfo($targetFile->name, PATHINFO_EXTENSION)),
        ];

        return view('onlyoffice.editor', compact('config'));
    }

    private function registerKeyMap($docKey, $path)
    {
        $mapPath = storage_path('app/onlyoffice/key_map.json');
        if (!is_dir(dirname($mapPath))) mkdir(dirname($mapPath), 0755, true);
        
        $keyMap = [];
        if (file_exists($mapPath)) {
            $keyMap = json_decode(file_get_contents($mapPath), true) ?? [];
        }
        $keyMap[$docKey] = $path;
        file_put_contents($mapPath, json_encode($keyMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function normalizePath(string $path): string
    {
        if ($path === '/' || $path === '') {
            return '/';
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        if (!str_ends_with($path, '/')) {
            $path = $path . '/';
        }
        return $path;
    }

    private function getDocumentType($ext)
    {
        $types = [
            'text' => ['docx', 'doc', 'txt', 'odt', 'rtf', 'html', 'htm'],
            'spreadsheet' => ['xlsx', 'xls', 'ods', 'csv'],
            'presentation' => ['pptx', 'ppt', 'odp'],
        ];

        foreach ($types as $type => $extensions) {
            if (in_array(strtolower($ext), $extensions)) {
                return $type;
            }
        }
        return 'text';
    }

    public function downloadForOnlyOffice($token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();
        
        // No validar expiración estricta aquí si queremos permitir que OnlyOffice termine de cargar
        // pero idealmente sí.
        if (! $link->isValid()) {
            abort(404);
        }

        $fileItem = $link->fileItem;
        
        // Manejar lógica de archivos dentro de carpetas si fuera necesario
        // Por ahora asumimos archivo directo para simplificar el fix
        $targetFile = $fileItem;
        
        $path = trim(
            "users/{$targetFile->user_id}/" . trim($targetFile->path, '/') . '/' . $targetFile->name,
            '/'
        );

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        // Devolver el archivo directamente
        return response()->file(Storage::disk('public')->path($path));
    }

    public function open(FileItem $fileItem)
    {
        //  abort_if($fileItem->user_id !== Auth::id(), 403);

        $user = Auth::user();

        // 🧠 Determinar permiso real
        if ($fileItem->user_id === $user->id) {
            $permission = 'full';
        } else {
            $permission = $fileItem->sharedWith()
                ->where('users.id', $user->id)
                ->value('permission');

            abort_if(! $permission, 403);
        }

        $isEditable = in_array($permission, ['full', 'edit']);

        /* $path = trim(
            "users/{$user->id}/" . trim($fileItem->path, '/') . '/' . $fileItem->name,
            '/'
        );*/

        $path = trim(
            "users/{$fileItem->user_id}/" . trim($fileItem->path, '/') . '/' . $fileItem->name,
            '/'
        );




        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        // Fix: Usar Storage::path para obtener la ruta absoluta correcta incluso con symlinks
        $absolutePath = Storage::disk('public')->path($path);

        if (!file_exists($absolutePath)) {
            // Fallback: si el archivo existe en disco según Laravel pero no físicamente (raro), 
            // o si hay problemas de permisos.
            abort(404, 'Archivo no accesible en disco');
        }

        $docKey = md5($path . filemtime($absolutePath));

        $mapPath = storage_path('app/onlyoffice/key_map.json');

        // Asegurar carpeta
        if (!is_dir(dirname($mapPath))) {
            mkdir(dirname($mapPath), 0755, true);
        }

        // Leer mapa existente
        $keyMap = [];
        if (file_exists($mapPath)) {
            $keyMap = json_decode(file_get_contents($mapPath), true) ?? [];
        }

        // Registrar key → path
        $keyMap[$docKey] = $path;

        // Guardar
        file_put_contents(
            $mapPath,
            json_encode($keyMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $config = [
            "document" => [
                "fileType" => pathinfo($fileItem->name, PATHINFO_EXTENSION),
                "key"      => $docKey,
                "title"    => $fileItem->name,
                "url"      => URL::temporarySignedRoute(
                    'onlyoffice.download.internal',
                    now()->addMinutes(60),
                    ['fileItem' => $fileItem->id]
                ),
                "permissions" => [
                    "edit" => $isEditable,
                    "download" => true,
                    "print" => true,
                    "review" => $isEditable,
                ],
            ],
            "editorConfig" => [
                "callbackUrl" => route('onlyoffice.callback'),
                "lang" => "es",
                "locale" => "es",
                "region" => "es-ES",
                "mode" => $isEditable ? "edit" : "view",
                "user" => [
                    "id" => (string) $user->id,
                    "name" => $user->name,
                ],
            ],
            
        ];

        return view('onlyoffice.editor', compact('config'));
    }
}
