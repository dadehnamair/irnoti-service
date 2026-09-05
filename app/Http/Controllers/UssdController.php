<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Contracts\View\View;

/**
 * Public USSD plan catalogue ("/ussd"), parallel to /pricing but scoped to
 * `plans.type = ussd`. Checkout reuses SubscriptionController unchanged —
 * plan type only affects which catalogue lists it.
 */
class UssdController extends Controller
{
    public function index(): View
    {
        return view('ussd', [
            'plans' => Plan::query()->ofType('ussd')->active()->ordered()->get(),
        ]);
    }
}
