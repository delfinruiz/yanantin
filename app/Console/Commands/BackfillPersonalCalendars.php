<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillPersonalCalendars extends Command
{
    protected $signature = 'calendars:backfill-personal';

    protected $description = 'Crea calendarios personales para usuarios que aún no los tienen';

    public function handle(): int
    {
        $count = 0;
        User::cursor()->each(function (User $user) use (&$count) {
            $exists = Calendar::where('is_personal', true)->where('user_id', $user->id)->exists();
            if (!$exists) {
                Calendar::create([
                    'name' => 'Calendario de ' . ($user->name ?? 'Usuario'),
                    'is_public' => false,
                    'is_personal' => true,
                    'user_id' => $user->id,
                    'created_by' => $user->id,
                ]);
                $count++;
            }
        });

        $this->info("Calendarios personales creados: {$count}");
        return self::SUCCESS;
    }
}

