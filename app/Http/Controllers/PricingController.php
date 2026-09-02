<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SmsPackage;
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

    /**
     * Public SMS credit-bundle catalogue ("/sms-packages"). Bundles are managed
     * from the Filament admin panel; buying one is done from the customer panel
     * (docs/starter.md §12).
     */
    public function packages(): View
    {
        return view('sms-packages', [
            'packages' => rescue(
                fn () => SmsPackage::query()->active()->ordered()->get(),
                collect(),
                report: false,
            ),
        ]);
    }
}
