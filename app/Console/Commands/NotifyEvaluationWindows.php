<?php

namespace App\Console\Commands;

use App\Models\EvaluationCycle;
use App\Models\User;
use Illuminate\Console\Command;

class NotifyEvaluationWindows extends Command
{
    protected $signature = 'evaluation:notify-windows';
    protected $description = 'Notifica apertura/cierre de ventanas de definición y seguimiento';

    public function handle(): int
    {
        $now = now();
        $cycles = EvaluationCycle::all();

        foreach ($cycles as $cycle) {
            // Placeholder: aquí se podrían enviar notificaciones reales (mail/database),
            // respetando que no se modifica información existente.
            // Por ahora, solo informamos en consola.
            $this->info("Ciclo {$cycle->name}: estado={$cycle->status}");
        }

        return Command::SUCCESS;
    }
}

