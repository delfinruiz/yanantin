<?php

namespace App\Providers;

use App\Events\JobOfferPublished;
use App\Listeners\SendNewJobOfferNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JobOfferPublished::class => [
            SendNewJobOfferNotification::class,
        ],
    ];
}

