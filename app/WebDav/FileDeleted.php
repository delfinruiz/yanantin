<?php

namespace App\WebDav;

class FileDeleted
{
    public function __construct(
        public int $userId,
        public string $path,   // carpeta padre
        public string $name,   // archivo o carpeta
        public bool $isFolder
    ) {}
}
