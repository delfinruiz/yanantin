<?php

namespace App\Livewire\Meetings;

use Livewire\Component;
use App\Filament\Resources\Meetings\MeetingResource;

class MeetingsBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        $this->badgeContent = MeetingResource::getNavigationBadge();
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.meetings.meetings-badge-poll');
    }
}

