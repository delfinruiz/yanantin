<?php

namespace App\Filament\Resources\Nominas\Pages;

use App\Filament\Resources\Nominas\NominaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditNomina extends EditRecord
{
    protected static string $resource = NominaResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_pdf')
                ->label(__('nominas.pdf.print_button'))
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('nominas.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['department_id'])) {
            $departmentId = $data['department_id'];
            unset($data['department_id']);
            
            // Actualizar el departamento del usuario asociado
            $this->getRecord()->user->departments()->sync([$departmentId]);
        }

        return $data;
    }
}
