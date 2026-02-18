<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use App\Services\AiMessageService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class DailyMoodWidget extends Widget
{
    protected string $view = 'filament.widgets.daily-mood-widget';

    public ?Mood $today = null;

    protected static bool $isLazy = false;

    public function mount(): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $this->today = Mood::where('user_id', $userId)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    #[On('daily-mood-updated')]
    public function reloadToday(): void
    {
        $userId = Auth::id();
        if (! $userId) {
            $this->today = null;
            return;
        }

        $this->today = Mood::where('user_id', $userId)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    public function setMood(string $mood): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }
        $date = Carbon::today();

        $entry = Mood::firstOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            ['mood' => $mood, 'score' => Mood::scoreFor($mood)],
        );

        if ($entry->wasRecentlyCreated || empty($entry->message)) {
            $ai = app(AiMessageService::class)->generateDailyMessage($user, $mood);
            $entry->fill([
                'message' => $ai['message'] ?? null,
                'message_model' => $ai['model'] ?? null,
                'message_generated_at' => now(),
            ])->save();
        }

        $this->today = $entry->refresh();
    }
}
