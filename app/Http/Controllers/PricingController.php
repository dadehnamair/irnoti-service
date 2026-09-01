<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Contracts\View\View;

class PricingController extends Controller
{
    /**
     * Standalone pricing page ("/pricing") — the same plans shown on the
     * landing section, plus a full feature-comparison table. Plans are managed
     * from the Filament admin panel (docs/starter.md §8 / §40).
     */
    public function index(): View
    {
        $plans = Plan::query()->active()->ordered()->get();

        return view('pricing', [
            'plans' => $plans,
        ]);
    }
}
