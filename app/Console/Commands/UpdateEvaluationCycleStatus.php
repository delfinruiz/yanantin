<?php

namespace App\Console\Commands;

use App\Models\EvaluationCycle;
use Illuminate\Console\Command;

class UpdateEvaluationCycleStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluation-cycles:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of evaluation cycles based on current date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating evaluation cycle statuses...');

        $cycles = EvaluationCycle::all();
        $updatedCount = 0;

        foreach ($cycles as $cycle) {
            $oldStatus = $cycle->status;
            
            // Explicitly update status
            $cycle->updateStatus();
            
            // Only save if status (or anything else, but mainly status) changed
            if ($cycle->isDirty('status')) {
                $cycle->save();
                $this->line("Cycle '{$cycle->name}': {$oldStatus} -> {$cycle->status}");
                $updatedCount++;
            }
        }

        $this->info("Updated {$updatedCount} cycles.");
    }
}
