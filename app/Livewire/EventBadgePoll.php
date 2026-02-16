<?php

namespace App\Livewire;

use Livewire\Component;
use App\Filament\Resources\Events\EventResource;

class EventBadgePoll extends Component
{
    public $badgeContent = null;
    public $shouldShow = false;

    public function mount()
    {
        $this->updateBadge();
    }

    public function updateBadge()
    {
        // Llamamos al método estático existente del recurso para obtener el conteo actualizado
        $this->badgeContent = EventResource::getNavigationBadge();
        $this->shouldShow = $this->badgeContent !== null;
    }

    public function render()
    {
        return view('livewire.event-badge-poll');
    }
}
