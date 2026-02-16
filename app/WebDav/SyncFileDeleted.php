<?php

namespace App\WebDav;

use App\WebDav\FileDeleted;
use App\Models\FileItem;
use Illuminate\Support\Facades\Log;

class SyncFileDeleted
{
    public function handle(FileDeleted $event): void
    {
        Log::debug('🔥 SyncFileDeleted EJECUTADO', [
            'user_id' => $event->userId,
            'path'    => $event->path,
            'name'    => $event->name,
            'folder'  => $event->isFolder,
        ]);

        // 1️⃣ borrar item solicitado
        FileItem::where([
            'user_id'   => $event->userId,
            'path'      => $event->path,
            'name'      => $event->name,
            'is_folder' => $event->isFolder,
        ])->delete();

        // 2️⃣ limpiar carpetas vacías en cascada
        if ($event->isFolder) {
            $this->cleanupEmptyParents(
                $event->userId,
                $event->path
            );
        }
    }

    /**
     * 🧹 Limpia carpetas vacías hacia arriba
     */
    protected function cleanupEmptyParents(int $userId, string $path): void
    {
        if ($path === '/') {
            return;
        }

        $path = rtrim($path, '/') . '/';

        while ($path !== '/') {

            $parentPath = dirname(trim($path, '/'));
            $parentPath = $parentPath === '.'
                ? '/'
                : '/' . $parentPath . '/';

            $folderName = basename(trim($path, '/'));

            $folder = FileItem::where([
                'user_id'   => $userId,
                'path'      => $parentPath,
                'name'      => $folderName,
                'is_folder' => true,
            ])->first();

            if (! $folder) {
                return;
            }

            $hasChildren = FileItem::where([
                'user_id' => $userId,
                'path'    => $path,
            ])->exists();

            if ($hasChildren) {
                return; // 🛑 detener cascada
            }

            Log::debug('🧹 Eliminando carpeta vacía BD', [
                'path' => $folder->path,
                'name' => $folder->name,
            ]);

            $folder->delete();

            $path = $folder->path;
        }
    }
}
