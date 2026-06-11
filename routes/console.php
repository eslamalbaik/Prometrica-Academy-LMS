<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FTR-005: Send weekly study progress digest every Monday at 08:00
Schedule::command('study:send-digests')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->runInBackground();

