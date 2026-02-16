<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Pages\MonthlyDashboard;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            //crear boton que envia a expenses list llamar a la clase ExpenseResource
            Action::make('expenses_list')
                ->button()
                ->color('secondary')
                ->outlined()
                ->label(__('go_to_expenses'))
                //volver a la pagina de expenses list pero a la tab gastos enviar #gastos
                ->url(MonthlyDashboard::getUrl() . '#expenses'),
        ];
    }

    //ancho maximo de la tabla
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
