<?php

namespace App\WebDav;

use App\Models\FileItem;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;

use App\WebDav\FileDeleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Sabre\DAV\IMoveTarget;
use Sabre\DAV\INode;

class DirectoryNode extends Collection implements IMoveTarget
{
    protected string $path;
    protected bool $isRoot;
    protected array $hiddenEntries = [
        'desktop.ini',
        'Thumbs.db',
        '.DS_Store',
        'Compartidos',
    ];

    public function __construct(string $path, bool $isRoot = false)
    {
        $this->path = $path;
        $this->isRoot = $isRoot;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    protected function parentPath(): string
    {
        $parent = dirname($this->path);

        // normalizar separadores
        $parent = str_replace('\\', '/', $parent);

        // quitar todo antes de /users/{id}
        $pos = strpos($parent, '/users/');
        if ($pos !== false) {
            $parent = substr($parent, strpos($parent, '/', $pos + 7));
        }

        // raíz
        if ($parent === '' || $parent === '/' || $parent === '.') {
            return '/';
        }

        return '/' . trim($parent, '/') . '/';
    }


    public function getName(): string
    {
        // El root NO debe tener nombre
        return $this->isRoot ? '' : basename($this->path);
    }

    public function getChildren(): array
    {
        $children = [];

        foreach (scandir($this->path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (in_array($item, $this->hiddenEntries, true)) {
                continue; // 👻 invisible
            }

            $fullPath = $this->path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $children[] = new self($fullPath);
            } else {
                $children[] = new FileNode($fullPath);
            }
        }

        return $children;
    }

    public function getChild($name)
    {
        if (in_array($name, $this->hiddenEntries, true)) {
            throw new \Sabre\DAV\Exception\NotFound();
        }
        $fullPath = $this->path . DIRECTORY_SEPARATOR . $name;

        if (! file_exists($fullPath)) {
            throw new NotFound('File not found: ' . $name);
        }

        if (is_dir($fullPath)) {
            return new self($fullPath);
        }

        return new FileNode($fullPath);
    }

    public function childExists($name)
    {
        if (in_array($name, $this->hiddenEntries, true)) {
            return false;
        }
        return file_exists($this->path . DIRECTORY_SEPARATOR . $name);
    }

    public function getLastModified(): int
    {
        return filemtime($this->path) ?: time();
    }

    public function delete(): void
    {
        if ($this->isRoot) {
            throw new Forbidden('No se puede eliminar la raíz');
        }

        // =====================================================
        // 1️⃣ Convertir path físico → path virtual (FileManager)
        // =====================================================

        $storageRoot = storage_path('app/public/users/' . Auth::id());

        $relativePath = str_replace($storageRoot, '', $this->path);
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = trim($relativePath, '/'); // 👈 CRÍTICO

        // =====================================================
        // 2️⃣ Calcular path padre virtual (BLINDADO)
        // =====================================================

        if ($relativePath === '') {
            // Caso imposible pero defensivo
            $parentPath = '/';
        } elseif (! str_contains($relativePath, '/')) {
            // Carpeta cuelga directo del root
            $parentPath = '/';
        } else {
            // Carpeta anidada
            $parentPath = '/' . dirname($relativePath) . '/';
        }

        $name = basename($this->path);

        Log::debug('🧪 Evento delete carpeta', [
            'path' => $parentPath,
            'name' => $name,
        ]);

        // =====================================================
        // 3️⃣ Disparar evento (ANTES de borrar físico)
        // =====================================================

        event(new FileDeleted(
            Auth::id(),
            $parentPath,
            $name,
            true
        ));

        // 🧠 Sincronizar borrado con BD (carpeta y su contenido)
        $folderPathInDb = '/' . trim($relativePath, '/') . '/';

        // 1. Borrar contenido recursivo en BD
        FileItem::where('user_id', Auth::id())
            ->where('path', 'like', $folderPathInDb . '%')
            ->delete();

        // 2. Borrar la carpeta misma
        FileItem::where([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $name,
            'is_folder' => true,
        ])->delete();

        // =====================================================
        // 4️⃣ Borrado físico
        // =====================================================

        $this->deleteRecursive($this->path);
    }

    protected function deleteRecursive(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;

            $full = $dir . DIRECTORY_SEPARATOR . $item;

            is_dir($full)
                ? $this->deleteRecursive($full)
                : unlink($full);
        }

        rmdir($dir);
    }

    //crear carpeta
    public function createDirectory($name): void
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $name;

        if (!mkdir($path, 0755, true)) {
            throw new Forbidden('No se pudo crear la carpeta');
        }

        // 🧠 Sincronizar con DB
        $storageRoot = storage_path('app/public/users/' . Auth::id());
        $relativePath = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $path)
        ), '/');

        $parentPath = dirname($relativePath);
        $parentPath = $parentPath === '.' ? '/' : '/' . trim($parentPath, '/') . '/';
        $folderName = basename($relativePath);

        FileItem::firstOrCreate([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $folderName,
            'is_folder' => true,
        ], [
            // No necesitamos más campos por ahora, pero aquí irían si hubiera
        ]);
    }


    public function setName($name): void
    {
        if ($this->isRoot) {
            throw new Forbidden('No se puede renombrar la raíz');
        }

        $oldPath = $this->path;
        $newPath = dirname($this->path) . DIRECTORY_SEPARATOR . $name;

        if (!rename($oldPath, $newPath)) {
            throw new Forbidden('No se pudo renombrar');
        }

        $storageRoot = storage_path('app/public/users/' . Auth::id());

        $oldRelative = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $oldPath)
        ), '/');

        $newRelative = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $newPath)
        ), '/');

        $this->renameCascade(
            Auth::id(),
            $oldRelative,
            $newRelative,
            is_dir($newPath)
        );

        $this->path = $newPath;
    }


    protected function renameCascade(
        int $userId,
        string $oldRelative,
        string $newRelative,
        bool $isFolder
    ): void {
        $oldParent = dirname($oldRelative);
        $newParent = dirname($newRelative);

        $oldParent = $oldParent === '.' ? '/' : '/' . trim($oldParent, '/') . '/';
        $newParent = $newParent === '.' ? '/' : '/' . trim($newParent, '/') . '/';

        $oldName = basename($oldRelative);
        $newName = basename($newRelative);

        Log::debug('🔁 Rename cascade', [
            'from' => $oldParent . $oldName,
            'to'   => $newParent . $newName,
            'is_folder' => $isFolder,
        ]);

        // 🔹 Renombrar el nodo principal (archivo o carpeta)
        FileItem::where([
            'user_id'   => $userId,
            'path'      => $oldParent,
            'name'      => $oldName,
            'is_folder' => $isFolder,
        ])->update([
            'path' => $newParent,
            'name' => $newName,
        ]);

        // 🔹 Si es carpeta → cascada de hijos
        if ($isFolder) {
            $oldPath = '/' . trim($oldRelative, '/') . '/';
            $newPath = '/' . trim($newRelative, '/') . '/';

            FileItem::where('user_id', $userId)
                ->where('path', 'like', $oldPath . '%')
                ->get()
                ->each(function ($item) use ($oldPath, $newPath) {
                    $item->update([
                        'path' => str_replace($oldPath, $newPath, $item->path),
                    ]);
                });
        }
    }




    public function createFile($name, $data = null)
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $name;

        file_put_contents($path, '');

        if ($data) {
            $dest = fopen($path, 'w');
            stream_copy_to_stream($data, $dest);
            fclose($dest);
        }

        // 🧠 Sincronizar con DB
        $storageRoot = storage_path('app/public/users/' . Auth::id());
        $relativePath = trim(str_replace(
            str_replace('\\', '/', $storageRoot),
            '',
            str_replace('\\', '/', $path)
        ), '/');

        $parentPath = dirname($relativePath);
        $parentPath = $parentPath === '.' ? '/' : '/' . trim($parentPath, '/') . '/';
        $fileName = basename($relativePath);

        $size = file_exists($path) ? filesize($path) : 0;
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        FileItem::updateOrCreate([
            'user_id'   => Auth::id(),
            'path'      => $parentPath,
            'name'      => $fileName,
            'is_folder' => false,
        ], [
            'filename'  => $fileName,
            'disk'      => 'public', // Asumiendo disco publico local
            'mime'      => $mime,
            'size'      => $size,
        ]);

        return new FileNode($path);
    }

    //ocultar carpetas
    protected array $hiddenFolders = [
        'Share',
        'Compartidos',
    ];

    public function moveInto($targetName, $sourcePath, INode $sourceNode): bool
    {
        // 1. Validar origen (solo soportamos mover nuestros propios nodos)
        if (!($sourceNode instanceof DirectoryNode) && !($sourceNode instanceof FileNode)) {
            return false;
        }

        // 2. Paths físicos
        $physicalSourcePath = $sourceNode->getPath();
        $physicalDestPath = $this->path . DIRECTORY_SEPARATOR . $targetName;

        Log::debug('🚚 MOVE start', [
            'from' => $physicalSourcePath,
            'to'   => $physicalDestPath
        ]);

        // 3. Mover archivo/carpeta físicamente
        if (!rename($physicalSourcePath, $physicalDestPath)) {
            throw new Forbidden('No se pudo mover el elemento');
        }

        // 4. Actualizar BD (usando lógica de renameCascade)
        $userId = Auth::id();
        $storageRoot = storage_path('app/public/users/' . $userId);

        $toRelative = function ($p) use ($storageRoot) {
            return trim(str_replace(
                str_replace('\\', '/', $storageRoot),
                '',
                str_replace('\\', '/', $p)
            ), '/');
        };

        $oldRelative = $toRelative($physicalSourcePath);
        $newRelative = $toRelative($physicalDestPath);

        $isFolder = $sourceNode instanceof DirectoryNode;

        $this->renameCascade($userId, $oldRelative, $newRelative, $isFolder);

        return true;
    }

}
