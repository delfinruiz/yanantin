<?php

namespace App\WebDav;

use App\Models\FileItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Sabre\DAV\File;
use Sabre\DAV\Exception\Forbidden;


class FileNode extends File
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    public function setName($name): void
    {
        $oldPath = $this->path;
        $newPath = dirname($this->path) . DIRECTORY_SEPARATOR . $name;

        if (!rename($oldPath, $newPath)) {
            throw new Forbidden('No se pudo renombrar el archivo');
        }

        $storageRoot = storage_path('app/public/users/' . Auth::id());

        // mismo cálculo confiable que ya usas
        $relative = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $newPath)
        ), '/');

        $parentPath = dirname($relative);
        $parentPath = $parentPath === '.' ? '/' : '/' . trim($parentPath, '/') . '/';

        $oldName = basename($oldPath);
        $newName = basename($newPath);

        Log::debug('✏️ Rename archivo BD', [
            'path' => $parentPath,
            'from' => $oldName,
            'to'   => $newName,
        ]);

        FileItem::where([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $oldName,
            'is_folder' => false,
        ])->update([
            'name'     => $newName,
            'filename' => $newName,
        ]);

        $this->path = $newPath;
    }

    public function get()
    {
        return fopen($this->path, 'r');
    }

    public function put($data): void
    {
        $dest = fopen($this->path, 'w');
        stream_copy_to_stream($data, $dest);
        fclose($dest);

        // 🧠 Sincronizar actualización (size/mime) con DB
        $storageRoot = storage_path('app/public/users/' . Auth::id());
        $relativePath = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $this->path)
        ), '/');

        $parentPath = dirname($relativePath);
        $parentPath = $parentPath === '.' ? '/' : '/' . trim($parentPath, '/') . '/';
        $fileName = basename($relativePath);

        $size = filesize($this->path);
        $mime = mime_content_type($this->path) ?: 'application/octet-stream';

        FileItem::where([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $fileName,
            'is_folder' => false,
        ])->update([
            'size' => $size,
            'mime_type' => $mime,
        ]);
    }

    public function getSize(): int
    {
        return filesize($this->path);
    }

    public function getLastModified(): int
    {
        return filemtime($this->path) ?: time();
    }

    // =====================================================
    // 🗑️ DELETE ARCHIVO (CLAVE)
    // =====================================================

    public function delete(): void
    {
        if (! file_exists($this->path)) {
            return;
        }

        // 🧠 path físico → virtual
        $storageRoot = storage_path('app/public/users/' . Auth::id());

        $relativePath = str_replace($storageRoot, '', $this->path);
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = trim($relativePath, '/');

        // padre
        if (! str_contains($relativePath, '/')) {
            $parentPath = '/';
        } else {
            $parentPath = '/' . dirname($relativePath) . '/';
        }

        $name = basename($this->path);

        Log::debug('🧪 Evento delete archivo', [
            'path' => $parentPath,
            'name' => $name,
        ]);

        // 📣 evento (opcional, si tienes un listener que haga algo más)
        event(new FileDeleted(
            Auth::id(),
            $parentPath,
            $name,
            false
        ));

        // 🧠 Sincronizar borrado con DB directamente (para asegurar)
        FileItem::where([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $name,
            'is_folder' => false,
        ])->delete();

        // 🧹 borrar físico
        unlink($this->path);
    }
}
