<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the site reachable on a single canonical host, with an exception for
 * the vanity domains (11v.ir, 7db.ir, …) that only exist to serve digital
 * business-card codes at their root (docs/business-cards.md §4). Any host
 * that isn't the admin-flagged default `Domain` gets bounced to it — unless
 * it's an active vanity domain, in which case only its bare index (no code
 * path) redirects; a real "/{code}" request is left alone for
 * PublicBusinessCardController to resolve by Host header. Skipped entirely
 * in local development, where the app is reached via localhost/APP_URL and
 * never matches the production default domain.
 */
class RedirectForeignDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment('local')) {
            return $next($request);
        }

        $host = $request->getHost();

        $defaultHost = rescue(
            fn() => Domain::query()->where('is_default', true)->value('host'),
            fn() => parse_url((string) config('app.url'), PHP_URL_HOST),
            report: false,
        ) ?: $host;

        if ($host === $defaultHost) {
            return $next($request);
        }

        $isVanityDomain = rescue(
            fn() => Domain::query()->where('host', $host)->active()->exists(),
            false,
            report: false,
        );

        if ($isVanityDomain && $request->path() !== '/') {
            return $next($request);
        }

        return redirect()->away($request->getScheme() . '://' . $defaultHost . $request->getRequestUri(), 301);
    }
}
