<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceApp;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Public marketing page for «بازارچه» ("/marketplace"). Read-only,
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
        abort_unless((bool) rescue(fn() => Setting::get('marketplace_enabled', true), true, false), 404);

        $apps = rescue(
            fn() => MarketplaceApp::query()->active()->ordered()->get(),
            new Collection,
            false
        );

        $canonical = route('marketplace');

        return view('marketplace', [
            'apps' => $apps,
            'featured' => $apps->firstWhere('is_featured', true) ?? $apps->first(),
            'groups' => $apps->groupBy('category'),
            'categories' => MarketplaceApp::CATEGORIES,
            'ctaHref' => auth()->check() ? route('dashboard.marketplace') : route('register'),
            'jsonLd' => $this->jsonLd($apps, $canonical),
        ]);
    }

    /** JSON-LD graph: BreadcrumbList + an ItemList of the active add-ons. */
    private function jsonLd(Collection $apps, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'بازارچه', 'item' => $canonical],
                ],
            ],
        ];

        if ($apps->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                'name' => 'بازارچهی '.$brand,
                'itemListElement' => $apps->values()->map(fn ($app, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => [
                        '@type' => 'Product',
                        'name' => $app->name,
                        'description' => $app->tagline ?: $app->name,
                        'brand' => ['@type' => 'Brand', 'name' => $app->vendor ?: $brand],
                        'category' => $app->category_label,
                    ],
                ])->all(),
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
