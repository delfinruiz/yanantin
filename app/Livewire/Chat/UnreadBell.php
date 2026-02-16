<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UnreadBell extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->updateCount();
    }

    public function refresh(): void
    {
        $this->updateCount();
    }

    private function updateCount(): void
    {
        $user = Auth::user();
        if (! $user instanceof \App\Models\User) {
            $this->count = 0;
            return;
        }
        $method = 'getUnreadCount';
        $this->count = method_exists($user, $method) ? (int) \call_user_func([$user, $method]) : 0;
    }

    public function render()
    {
        return view('livewire.unread-bell');
    }
}
