<?php

namespace App\Events;

use App\Models\JobOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobOfferPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public JobOffer $jobOffer)
    {
    }
}

