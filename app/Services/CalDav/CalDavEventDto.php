<?php

namespace App\Services\CalDav;

use Illuminate\Support\Carbon;

class CalDavEventDto
{
    public function __construct(
        public string $title,
        public ?string $description,
        public Carbon $startsAt,
        public ?Carbon $endsAt,
        public bool $allDay,
        public ?string $color,
        public ?string $uid = null,
        public ?string $etag = null
    ) {}
}

