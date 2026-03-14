<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Wirechat\Wirechat\Models\Group;

class WirechatFixGeneralGroupAvatar extends Command
{
    protected $signature = 'wirechat:fix-general-avatar';

    protected $description = 'Normaliza el avatar del grupo General para que use una ruta válida del disco public.';

    public function handle(): int
    {
        $group = Group::query()->where('name', 'General')->first();
        if (! $group) {
            $this->warn('No existe el grupo General.');
            return self::SUCCESS;
        }

        $path = 'groups/avatars/01KECPXESFJ5SEA60BCHRZ8B25.jpg';

        if (! Storage::disk('public')->exists($path)) {
            $this->warn("No existe el archivo en storage/app/public/{$path}.");
            return self::SUCCESS;
        }

        $group->avatar_url = $path;
        $group->save();

        $mimeType = null;
        try {
            $fullPath = Storage::disk('public')->path($path);
            if (is_string($fullPath) && file_exists($fullPath)) {
                $mimeType = mime_content_type($fullPath) ?: null;
            }
        } catch (\Throwable $e) {
        }

        $group->cover()->updateOrCreate(
            [],
            [
                'file_path' => $path,
                'file_name' => basename($path),
                'original_name' => basename($path),
                'mime_type' => $mimeType,
                'url' => '/storage/' . $path,
            ],
        );

        $this->info('Avatar del grupo General actualizado.');

        return self::SUCCESS;
    }
}
