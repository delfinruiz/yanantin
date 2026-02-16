<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FilePreviewController extends Controller
{
    public function show(FileItem $fileItem)
    {
        // 1. Autorización
        if ($fileItem->user_id !== Auth::id()) {
            // Verificar si está compartido
            $share = $fileItem->sharedWith()
                ->where('users.id', Auth::id())
                ->first();

            if (!$share) {
                abort(403, 'No tienes permiso para ver este archivo.');
            }

            $pivot = $share->pivot;
            if (($pivot->requires_ack ?? false) && empty($pivot->ack_completed_at)) {
                abort(403, 'Debes completar la toma de conocimiento antes de acceder.');
            }
        }

        // 2. Determinar ruta en disco
        // Replicamos la lógica de path de FileManager
        $name = $fileItem->filename ?? $fileItem->name;
        
        $path = trim(
            "users/{$fileItem->user_id}/" . trim($fileItem->path, '/') . '/' . $name,
            '/'
        );

        // 3. Verificar existencia
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado en el almacenamiento.');
        }

        // 4. Servir archivo
        // response()->file() maneja headers de cache, mime-type y range requests automáticamente
        return response()->file(Storage::disk('public')->path($path));
    }
}
