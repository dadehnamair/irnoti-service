<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Public USSD plan catalogue ("/ussd"), parallel to /pricing but scoped to
 * `plans.type = ussd`. Checkout reuses SubscriptionController unchanged —
 * plan type only affects which catalogue lists it.
 */
class UssdController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->ofType('ussd')->active()->ordered()->get();
        $canonical = route('ussd');

        return view('ussd', [
            'plans' => $plans,
            'jsonLd' => $this->jsonLd($plans, $canonical),
        ]);
    }

    /** JSON-LD graph: BreadcrumbList + one Service/Offer per USSD plan. */
    private function jsonLd(Collection $plans, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'کد دستوری USSD', 'item' => $canonical],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            $graph[] = [
                '@type' => 'Service',
                'name' => 'کد دستوری '.$plan->name.' '.$brand,
                'description' => $plan->description ?: implode('، ', $plan->feature_list),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'provider' => ['@type' => 'Organization', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $plan->price_monthly * 10,
                    'priceCurrency' => 'IRR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $canonical,
                    'priceValidUntil' => now()->addYear()->toDateString(),
                ],
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
