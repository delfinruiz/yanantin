<?php

namespace App\Livewire\Surveys;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Survey;

class PendingBadgePoll extends Component
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
            ->where(function ($q) {
                $q->whereNull('deadline')
                  ->orWhere('deadline', '>=', now());
            })
            ->accessibleToUser($userId)
            ->pendingForUser($userId)
            ->count();

        $this->badgeContent = $count > 0 ? (string) $count : null;
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.surveys.pending-badge-poll');
    }
}
