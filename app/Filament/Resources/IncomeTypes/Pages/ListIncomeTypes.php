<?php

namespace App\Filament\Resources\IncomeTypes\Pages;

use App\Filament\Pages\MonthlyDashboard;
use App\Filament\Resources\IncomeTypes\IncomeTypeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListIncomeTypes extends ListRecords
{
    protected static string $resource = IncomeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            //crear boton que envia a expenses list llamar a la clase ExpenseResource
            Action::make('expenses_list')
                ->button()
                ->color('secondary')
                ->outlined()
                ->label(__('go_to_incomes'))
                //volver a la pagina de incomes list pero a la tab ingresos enviar #incomes
                ->url(MonthlyDashboard::getUrl() . '#incomes'),
        ];
    }

    //ancho maximo de la tabla
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
