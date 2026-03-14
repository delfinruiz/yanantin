<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Calendar;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:cleanup-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina eventos duplicados basados en caldav_uid para calendarios personales.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de eventos duplicados...');

        // Agrupar por caldav_uid en calendarios personales
        $duplicates = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
            ->where('calendars.is_personal', true)
            ->whereNotNull('events.caldav_uid')
            ->select('events.caldav_uid', DB::raw('count(*) as total'))
            ->groupBy('events.caldav_uid')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No se encontraron eventos duplicados.');
            return;
        }

        $this->info("Se encontraron {$duplicates->count()} grupos de eventos duplicados.");

        $bar = $this->output->createProgressBar($duplicates->count());
        $deletedCount = 0;

        foreach ($duplicates as $dupe) {
            $uid = $dupe->caldav_uid;

            // Obtener todos los eventos con este UID en calendarios personales
            $events = Event::join('calendars', 'events.calendar_id', '=', 'calendars.id')
                ->where('calendars.is_personal', true)
                ->where('events.caldav_uid', $uid)
                ->select('events.*')
                ->orderBy('events.id', 'asc') // Mantener el más antiguo (o cambiar lógica si se prefiere)
                ->get();

            // Mantener el primero, borrar el resto
            $keep = $events->first();
            
            foreach ($events as $event) {
                if ($event->id !== $keep->id) {
                    $event->delete();
                    $deletedCount++;
                }
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Limpieza completada. Se eliminaron {$deletedCount} eventos duplicados.");
    }
}
