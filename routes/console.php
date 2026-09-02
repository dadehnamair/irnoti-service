<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Pull carrier delivery receipts for panel-sent SMS (docs/starter.md §12/§14).
 * Cheap and idempotent — settled messages are skipped — so it runs often.
 * Needs the system cron entry: * * * * * php artisan schedule:run
 */
Schedule::command('sms:delivery-sync')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
