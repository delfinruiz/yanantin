<?php

namespace App\Filament\Resources\Evaluations\BonusRuleResource\Pages;

use App\Filament\Resources\Evaluations\BonusRuleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListBonusRules extends ListRecords
{
    protected static string $resource = BonusRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}

