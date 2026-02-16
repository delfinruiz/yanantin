<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Survey;

class SurveysPendingBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        $userId = Auth::id();
        if (! $userId) {
            $this->badgeContent = null;
            $this->shouldShow = false;
            return;
        }
        $count = Survey::query()
            ->where('active', true)
            ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now());
            })
            ->whereHas('questions', function ($q) use ($userId) {
                $q->where('required', true)
                  ->whereDoesntHave('responses', fn ($r) => $r->where('user_id', $userId));
            })
            ->count();
        $this->badgeContent = $count > 0 ? (string) $count : null;
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.surveys.pending-badge-poll');
    }
}
