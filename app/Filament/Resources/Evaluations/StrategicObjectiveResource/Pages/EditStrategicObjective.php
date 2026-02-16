<?php

namespace App\Filament\Resources\Evaluations\StrategicObjectiveResource\Pages;

use App\Filament\Resources\Evaluations\StrategicObjectiveResource;
use Filament\Resources\Pages\EditRecord;

use Filament\Actions;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditStrategicObjective extends EditRecord
{
    protected static string $resource = StrategicObjectiveResource::class;

    protected function getFormActions(): array
    {
        /** @var \App\Models\StrategicObjective $record */
        $record = $this->getRecord();

        // Si el estado es 'approved' o 'pending_approval', ocultar el botón guardar
        if (in_array($record->status, ['approved', 'pending_approval'])) {
            return [];
        }

        return parent::getFormActions();
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

