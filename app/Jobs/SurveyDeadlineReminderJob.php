<?php

namespace App\Jobs;

use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SurveyDeadlineReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $surveys = Survey::where('active', true)
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now(), now()->addDay()])
            ->get();

        foreach ($surveys as $survey) {
            $requiredIds = $survey->questions()->where('required', true)->pluck('id');
            $userIds = $survey->users()->pluck('users.id');

            foreach ($userIds as $uid) {
                $answeredCount = Response::whereIn('question_id', $requiredIds)->where('user_id', $uid)->count();
                if ($answeredCount < $requiredIds->count()) {
                    $recipient = User::find($uid);
                    if ($recipient) {
                        Notification::make()
                            ->title('Recordatorio de encuesta')
                            ->body('La encuesta “' . $survey->title . '” vence pronto.')
                            ->success()
                            ->sendToDatabase($recipient);
                    }
                }
            }
        }
    }
}

