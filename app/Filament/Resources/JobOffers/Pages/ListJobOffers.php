<?php

namespace App\Filament\Resources\JobOffers\Pages;

use App\Filament\Resources\JobOffers\JobOfferResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListJobOffers extends ListRecords
{
    protected static string $resource = JobOfferResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

}



