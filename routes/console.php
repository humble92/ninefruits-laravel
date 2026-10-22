<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled jobs (docs/manual/operations/scheduled-jobs.md).
Schedule::command('admission:expire-drafts')->dailyAt('03:10')->withoutOverlapping()->onOneServer();
Schedule::command('registration:release-reserved-seats')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('waitlist:promote')->hourly()->withoutOverlapping()->onOneServer();
