<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('meetings:update-status')->everyMinute();
Schedule::command('caldav:sync')->everyFiveMinutes()->withoutOverlapping();
