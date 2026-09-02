<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The payment gateway posts the result back here without a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'lines/payment/callback',
            'subscriptions/payment/callback',
            'wallet/topup/callback',
            'packages/payment/callback',
            'invoices/payment/callback',
            'marketplace/payment/callback',
        ]);

        // Panel features stay locked until an admin approves the account (docs/starter.md §39).
        $middleware->alias([
            'approved' => \App\Http\Middleware\EnsureAccountApproved::class,
        ]);

        // Customer auth entry points (docs/starter.md §26/§27).
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
 * On shared hosting the front controller lives in the domain's document root
 * while the app lives elsewhere. Set APP_PUBLIC_PATH in .env to that document
 * root so web requests AND artisan commands (storage:link, sitemap, queue…)
 * agree on where "public" is. Locally, leave it unset.
 */
if ($publicPath = env('APP_PUBLIC_PATH')) {
    $app->usePublicPath($publicPath);
}

return $app;
