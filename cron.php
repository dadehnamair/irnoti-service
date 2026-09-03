<?php

/*
 * Shared-hosting cron entry point (docs/starter.md §12/§14).
 *
 * The host's control panel only accepts a PHP file path as the cron command —
 * no shell operators, no `sh`, no arguments — and such hosts frequently also
 * disable proc_open/exec, which Laravel's `schedule:run` needs to launch each
 * `Schedule::command()` in its own process. So this file does NOT rely on that:
 * it boots the framework and runs the queue worker and the scheduler IN-PROCESS
 * via the console kernel. Point the cron job at it, once per minute:
 *
 *     * * * * * /usr/local/bin/php /home/h355718/include/cron.php
 *
 * Every run appends a line to storage/logs/cron.log so you can confirm from the
 * file manager that the cron is actually firing and see what each step did.
 * Once everything is healthy you can trim the logging back.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$logFile = __DIR__.'/storage/logs/cron.log';
$log = function (string $line) use ($logFile) {
    file_put_contents($logFile, '['.date('Y-m-d H:i:s').'] '.$line.PHP_EOL, FILE_APPEND);
};

$log('--- cron start | queue='.config('queue.default').' | sms='.config('sms.provider'));

// 1) Drain the queued jobs (SendSmsJob, SendContactGroupSmsJob, …). Called
//    directly on the kernel, so it works even when proc_open/exec are blocked.
try {
    $kernel->call('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 50,
        '--tries' => 3,
        '--sleep' => 3,
    ]);
    $log('queue:work done'.rtrim("\n".trim($kernel->output())));
} catch (Throwable $e) {
    $log('queue:work ERROR: '.$e->getMessage());
}

// 2) Run the remaining scheduled tasks (sms:delivery-sync, marketplace:expire).
try {
    $kernel->call('schedule:run');
    $log('schedule:run done'.rtrim("\n".trim($kernel->output())));
} catch (Throwable $e) {
    $log('schedule:run ERROR: '.$e->getMessage());
}

exit(0);
