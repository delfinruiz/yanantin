<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use App\Filament\Resources\Tasks\TaskResource;

class TasksBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        $this->badgeContent = TaskResource::getNavigationBadge();
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.tasks.tasks-badge-poll');
    }
}

