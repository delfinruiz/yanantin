<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Models\FileShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicShareController extends Controller
{
    public function show(Request $request, string $token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es válido.');
        }

        $rootItem = $link->fileItem;

        // Caso 1: Compartir un solo archivo
        if (! $rootItem->is_folder) {
            return view('public.share', [
                'link' => $link,
                'fileItem' => $rootItem,
                'isFolder' => false,
                'items' => [],
                'currentPath' => '/',
                'breadcrumbs' => [],
            ]);
        }

        // Caso 2: Compartir carpeta
        // currentPath es relativo a la carpeta compartida
        $relativePath = $request->query('path', '');
        $relativePath = trim($relativePath, '/'); 

        // Construir la ruta lógica absoluta en la BD
        // La carpeta compartida reside en $rootItem->path, y su nombre es $rootItem->name
        // Sus hijos directos tienen path = $rootItem->path . $rootItem->name . '/'
        
        $rootLogicalPath = $this->normalizePath($rootItem->path . $rootItem->name);
        
        // Ruta que buscaremos en la columna 'path' de los items hijos
        if ($relativePath === '') {
            $targetPath = $rootLogicalPath;
        } else {
            $targetPath = $this->normalizePath($rootLogicalPath . $relativePath);
        }

        // Seguridad: Evitar Path Traversal (aunque al concatenar con rootLogicalPath ya se mitiga, validamos que target empiece con root)
        if (!str_starts_with($targetPath, $rootLogicalPath)) {
            abort(403, 'Acceso denegado.');
        }

        $items = FileItem::where('user_id', $rootItem->user_id)
            ->where('path', $targetPath)
            ->orderBy('is_folder', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        // Breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($relativePath, $token);

        return view('public.share', [
            'link' => $link,
            'fileItem' => $rootItem,
            'isFolder' => true,
            'items' => $items,
            'currentPath' => $relativePath,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function download(Request $request, string $token)
    {
        $link = FileShareLink::with('fileItem')->where('token', $token)->firstOrFail();

        if (! $link->isValid()) {
            abort(404, 'El enlace ha expirado o no es válido.');
        }

        // Incrementar contador (solo si no es preview/navegación, aunque aquí es download explícito)
        // Podríamos contar descargas únicas por sesión, pero por ahora simple.
        $link->increment('downloads');

        $rootItem = $link->fileItem;
        $disk = Storage::disk('public');

        // Si se compartió un archivo, descargar ese archivo
        if (! $rootItem->is_folder) {
            $physicalPath = $this->getPhysicalPath($rootItem);
            if (!$disk->exists($physicalPath)) {
                abort(404, 'Archivo no encontrado en el servidor.');
            }
            return response()->download($disk->path($physicalPath), $rootItem->name);
        }

        // Si se compartió una carpeta, verificar qué se quiere descargar
        $relativePath = $request->query('path', '');
        $filename = $request->query('file'); // Nombre del archivo a descargar dentro de la carpeta

        if (!$filename) {
            // TODO: Implementar descarga de carpeta completa como ZIP
            abort(404, 'Descarga de carpetas no implementada aún.');
        }

        // Buscar el archivo específico dentro de la estructura compartida
        // Ruta lógica padre del archivo
        $rootLogicalPath = $this->normalizePath($rootItem->path . $rootItem->name);
        $targetPath = $relativePath ? $this->normalizePath($rootLogicalPath . $relativePath) : $rootLogicalPath;

        // Seguridad
        if (!str_starts_with($targetPath, $rootLogicalPath)) {
            abort(403);
        }

        $targetItem = FileItem::where('user_id', $rootItem->user_id)
            ->where('path', $targetPath)
            ->where('name', $filename)
            ->where('is_folder', false)
            ->firstOrFail();

        $physicalPath = $this->getPhysicalPath($targetItem);
        
        if (!$disk->exists($physicalPath)) {
             abort(404, 'Archivo físico no encontrado.');
        }

        return response()->download($disk->path($physicalPath), $targetItem->name);
    }

    // Helpers
    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);
        if ($path === '' || $path === '/') {
            return '/';
        }
        return '/' . trim($path, '/') . '/';
    }

    private function getPhysicalPath(FileItem $item): string
    {
        // Replicar lógica de FileManager para obtener ruta física
        // userRoot = 'users/' . $item->user_id
        // physical = userRoot . item->path . item->name
        
        $userRoot = 'users/' . $item->user_id;
        $logicalPath = $this->normalizePath($item->path . $item->name);
        
        return trim($userRoot . '/' . trim($logicalPath, '/'), '/');
    }

    private function buildBreadcrumbs($relativePath, $token)
    {
        if (!$relativePath) return [];

        $parts = explode('/', $relativePath);
        $breadcrumbs = [];
        $accumulated = '';

        foreach ($parts as $part) {
            if (!$part) continue;
            $accumulated .= ($accumulated ? '/' : '') . $part;
            $breadcrumbs[] = [
                'name' => $part,
                'url' => route('public.share', ['token' => $token, 'path' => $accumulated]),
            ];
        }
        return $breadcrumbs;
    }
}
