<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Pages\MonthlyDashboard;
use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            //crear boton que envia a expenses category llamar a la clase ExpensesCategoryResource
            Action::make('expenses_category')
                ->button()
                ->color('secondary')
                ->outlined()
                ->label(__('create_categories'))
                //mostrar y refrescar #expensesCategories
                ->url(MonthlyDashboard::getUrl() . '#expensesCategories'),
        ];
    }
}
