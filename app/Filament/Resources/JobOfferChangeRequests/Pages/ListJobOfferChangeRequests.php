<?php

namespace App\Filament\Resources\JobOfferChangeRequests\Pages;

use App\Filament\Resources\JobOfferChangeRequests\JobOfferChangeRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListJobOfferChangeRequests extends ListRecords
{
    protected static string $resource = JobOfferChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
        
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    } 

}
