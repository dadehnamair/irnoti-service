<?php

namespace App\Http\Controllers;

use App\Models\SiteFeature;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Public /features showcase — the full sales page for every platform
 * capability (SiteFeature catalogue), grouped by category. The landing
 * page's #features section shows a short teaser of the same data
 * (HomeController::index) and links here for the full list.
 */
class FeaturesController extends Controller
{
    public function index(): View
    {
        $features = rescue(
            fn () => SiteFeature::query()->active()->ordered()->get(),
            new Collection,
            false
        );

        $canonical = route('features');

        return view('features', [
            'features' => $features,
            'featured' => $features->firstWhere('is_featured', true) ?? $features->first(),
            'groups' => $features->groupBy('category'),
            'categories' => SiteFeature::CATEGORIES,
            'jsonLd' => $this->jsonLd($features, $canonical),
        ]);
    }

    /** JSON-LD graph: BreadcrumbList + an ItemList of the active features. */
    private function jsonLd(Collection $features, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'امکانات', 'item' => $canonical],
                ],
            ],
        ];

        if ($features->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                'name' => 'امکانات '.$brand,
                'itemListElement' => $features->values()->map(fn ($feature, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => [
                        '@type' => 'Thing',
                        'name' => $feature->title,
                        'description' => $feature->tagline ?: $feature->title,
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
