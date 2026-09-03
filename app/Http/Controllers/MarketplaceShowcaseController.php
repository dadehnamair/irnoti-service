<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dashboard\MarketplaceController;
use App\Models\MarketplaceApp;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Public marketing page for «بازارچه افزونه‌ها» ("/marketplace"). Read-only,
 * no auth — mirrors the thin public controllers (PricingController, LineController).
 * The in-panel install / connect / pay flow lives in
 * {@see MarketplaceController}. Content is fully
 * database-driven (marketplace_apps table) and managed from the Filament admin
 * panel — see docs/starter.md §15.
 */
class MarketplaceShowcaseController extends Controller
{
    public function index(): View
    {
        // Degrade gracefully on a fresh / mid-migration DB, just like the landing.
        abort_unless((bool) rescue(fn () => Setting::get('marketplace_enabled', true), true, false), 404);

        $apps = rescue(
            fn () => MarketplaceApp::query()->active()->ordered()->get(),
            new Collection,
            false
        );

        return view('marketplace', [
            'apps' => $apps,
            'featured' => $apps->firstWhere('is_featured', true) ?? $apps->first(),
            'groups' => $apps->groupBy('category'),
            'categories' => MarketplaceApp::CATEGORIES,
        ]);
    }
}
