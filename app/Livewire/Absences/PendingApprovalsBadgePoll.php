<?php

namespace App\Livewire\Absences;

use Livewire\Component;
use App\Filament\Resources\AbsenceRequests\AbsenceRequestResource;

class PendingApprovalsBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        $this->badgeContent = AbsenceRequestResource::getNavigationBadge();
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.absences.pending-approvals-badge-poll');
    }
}

