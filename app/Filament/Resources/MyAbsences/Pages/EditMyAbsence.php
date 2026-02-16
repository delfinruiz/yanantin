<?php

namespace App\Filament\Resources\MyAbsences\Pages;

use App\Filament\Resources\MyAbsences\MyAbsenceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditMyAbsence extends EditRecord
{
    protected static string $resource = MyAbsenceResource::class;

    protected function beforeFill(): void
    {
        if ($this->record->status !== 'pending') {
            Notification::make()
                ->title(__('my_absences.notifications.cannot_edit.title'))
                ->body(__('my_absences.notifications.only_pending.body'))
                ->danger()
                ->send();
            
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }
}
