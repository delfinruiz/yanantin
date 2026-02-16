<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\CalDavSyncJob;

class CalDavSyncCommand extends Command
{
    protected $signature = 'caldav:sync {userId?}';
    protected $description = 'Sincroniza calendarios personales con CalDAV para todos los usuarios o uno específico';

    public function handle(): int
    {
        $userId = $this->argument('userId');
        if ($userId) {
            CalDavSyncJob::dispatch((int) $userId);
            $this->info("Sincronización encolada para usuario {$userId}");
            return self::SUCCESS;
        }

        User::query()->whereHas('emailAccount')->chunk(100, function ($users) {
            foreach ($users as $user) {
                CalDavSyncJob::dispatch($user->id);
            }
        });
        $this->info('Sincronización encolada para todos los usuarios con cuenta de email.');
        return self::SUCCESS;
    }
}
