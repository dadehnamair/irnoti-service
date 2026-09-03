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

/*
 * Expire past-due «بازارچه» subscription add-ons and revoke their capabilities
 * (docs/starter.md §15). Idempotent — safe to run daily.
 */
Schedule::command('marketplace:expire')
    ->daily()
    ->withoutOverlapping();

/*
 * The queued SMS jobs are drained on shared hosting by cron.php, which calls
 * `queue:work --stop-when-empty` in-process every minute (it can't be a
 * Schedule::command() here because that needs proc_open, which the host blocks).
 * If you move to a host with a real worker, add it back or run `queue:work` as
 * a daemon instead.
 */
