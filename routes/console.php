<?php

use App\Console\Commands\System\HealthCheck;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled jobs (docs/manual/ko/operations/scheduled-jobs.md).
Schedule::command('admission:expire-drafts')->dailyAt('03:10')->withoutOverlapping()->onOneServer();
Schedule::command('registration:release-reserved-seats')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('waitlist:promote')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('lms:reconcile')->dailyAt('02:30')->withoutOverlapping()->onOneServer();
Schedule::command('audit:seal')->dailyAt('03:30')->withoutOverlapping()->onOneServer();
// system:health-check reads this heartbeat to tell whether schedule:run is alive.
Schedule::call(fn () => Cache::put(HealthCheck::HEARTBEAT_KEY, now()->toDateTimeString(), 600))->everyMinute()->name('system.heartbeat');
