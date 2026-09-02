<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the panel features (send SMS, buy a line) behind admin approval
 * (docs/starter.md §39). Completing the profile and buying a plan only gets the
 * account to "awaiting_approval"; an admin flips it to "active". The toggle
 * `require_admin_approval` (settings) can lift the gate for early setup/testing.
 */
class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::get('require_admin_approval', true)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            return redirect()->route('dashboard')->with(
                'gate_notice',
                'حساب شما هنوز توسط کارشناسان تأیید نشده است. پس از تأیید، امکانات پنل فعال می‌شود.',
            );
        }

        return $next($request);
    }
}
