<?php

namespace App\Filament\Resources\Incomes\Pages;

use App\Filament\Pages\MonthlyDashboard;
use App\Filament\Resources\Incomes\IncomeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            //crear boton que envia a incomes list llamar a la clase IncomeResource
            Action::make('create_income_type')
                ->button()
                ->color('secondary')
                ->outlined()
                ->label(__('create_income_type'))
                //mostrar y refrescar #incomesTypes
                ->url(MonthlyDashboard::getUrl() . '#incomesTypes'),
        ];
    }
}
