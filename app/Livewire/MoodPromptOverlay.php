<?php

namespace App\Livewire;

use App\Models\Mood;
use App\Services\AiMessageService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MoodPromptOverlay extends Component
{
    public ?Mood $today = null;
    public bool $showPrompt = false;

    public function mount(): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $this->today = Mood::where('user_id', $userId)
            ->whereDate('date', Carbon::today())
            ->first();

        $this->showPrompt = $this->today === null;
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
        // Notificar a todos los Livewire components que dependen de este dato
        $this->dispatch('daily-mood-updated');
        // Mostrar feedback en UI y cerrar posteriormente desde el frontend
        $this->dispatch('mood-saved');
    }

    public function closePrompt(): void
    {
        $this->showPrompt = false;
    }

    public function render()
    {
        return view('livewire.mood-prompt-overlay');
    }
}

