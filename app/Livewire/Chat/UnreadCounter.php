<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Wirechat\Wirechat\Enums\ConversationType;

class UnreadCounter extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            $this->count = 0;
            return;
        }
        
        $this->count = $user->conversations()
            ->where('type', ConversationType::PRIVATE)
            ->get()
            ->sum(fn ($conv) => $conv->getUnreadCountFor($user));
    }

    public function render()
    {
        return view('livewire.chat.unread-counter');
    }
}
