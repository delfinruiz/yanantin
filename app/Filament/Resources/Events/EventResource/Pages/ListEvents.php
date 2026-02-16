<?php

namespace App\Filament\Resources\Events\EventResource\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use App\Jobs\CalDavSyncJob;
use App\Models\EmailAccount;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;
    
    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $hasEmailAccount = EmailAccount::where('user_id', $user->id)->exists();
        if (!$hasEmailAccount) {
            return;
        }

        CalDavSyncJob::dispatch($user->id);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
