<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateMeetingStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meetings:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update meeting statuses based on start time and duration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // 1. Mark 'scheduled' meetings as 'active' if it's time
        // Only for Type 2 (Scheduled). Type 1 (Instant) are handled at creation.
        Meeting::where('status', 'scheduled')
            ->where('type', 2) 
            ->where('start_time', '<=', $now)
            ->chunk(100, function ($meetings) use ($now) {
                foreach ($meetings as $meeting) {
                    // Check if it should already be finished (missed meeting?)
                    // If start_time + duration < now, it's finished (or missed)
                    $endTime = $meeting->start_time->copy()->addMinutes($meeting->duration);
                    
                    if ($endTime < $now) {
                        $meeting->update(['status' => 'finished']);
                        $this->info("Meeting ID {$meeting->id} marked as finished (missed).");
                    } else {
                        $meeting->update(['status' => 'active']);
                        $this->info("Meeting ID {$meeting->id} marked as active.");
                    }
                }
            });

        // 2. Mark 'active' meetings as 'finished' if time is up
        // Only for Type 2 (Scheduled). Type 1 (Instant) should NOT be auto-finished here as per user request ("no caducan").
        Meeting::where('status', 'active')
            ->where('type', 2)
            ->chunk(100, function ($meetings) use ($now) {
                foreach ($meetings as $meeting) {
                    $endTime = $meeting->start_time->copy()->addMinutes($meeting->duration);
                    
                    if ($endTime < $now) {
                        $meeting->update(['status' => 'finished']);
                        $this->info("Meeting ID {$meeting->id} marked as finished.");
                    }
                }
            });

        $this->info('Meeting statuses updated successfully.');
    }
}
