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

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label(__('calendars.refresh') !== 'calendars.refresh' ? __('calendars.refresh') : 'Actualizar')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('refresh'); // Standard Filament table refresh?
                    // Actually, just executing this action causes a livewire roundtrip which refreshes the table query.
                    // We can also dispatch a notification.
                    \Filament\Notifications\Notification::make()
                        ->title(__('calendars.notification.refreshed') !== 'calendars.notification.refreshed' ? __('calendars.notification.refreshed') : 'Lista actualizada')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
