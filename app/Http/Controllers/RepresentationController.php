<?php

namespace App\Http\Controllers;

use App\Models\RepresentationApplication;
use App\Models\RepresentationTier;
use App\Models\Setting;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Public "نمایندگی فروش" (sales representation) page — admin-defined tiers
 * (RepresentationTier) + a lead-capture application form
 * (RepresentationApplication), reviewed manually by the admin. See
 * docs/sales-representation.md.
 */
class RepresentationController extends Controller
{
    public function index(): View
    {
        abort_unless((bool) rescue(fn () => Setting::get('representation_enabled', true), true, false), 404);

        $tiers = rescue(
            fn () => RepresentationTier::query()->active()->ordered()->get(),
            new Collection,
            false
        );

        $canonical = route('representation');

        return view('representation', [
            'tiers' => $tiers,
            'jsonLd' => $this->jsonLd($tiers, $canonical),
        ]);
    }

    public function apply(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'representation_tier_id' => ['nullable', 'exists:representation_tiers,id'],
            'full_name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'string', 'regex:/^0?9\d{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'representation_tier_id' => 'تعرفه',
            'full_name' => 'نام و نام‌خانوادگی',
            'mobile' => 'شماره موبایل',
            'email' => 'ایمیل',
            'city' => 'شهر',
            'company_name' => 'نام شرکت',
            'message' => 'توضیحات',
        ]);

        $application = RepresentationApplication::create($data);

        $notifier->representationApplicationReceived($application);

        return redirect()->route('representation')->with('success', 'درخواست نمایندگی شما ثبت شد. همکاران ما به‌زودی با شما تماس می‌گیرند.');
    }

    /** JSON-LD graph: BreadcrumbList + an ItemList of the active tiers. */
    private function jsonLd(Collection $tiers, string $canonical): string
    {
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'نمایندگی فروش', 'item' => $canonical],
                ],
            ],
        ];

        if ($tiers->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                'name' => 'تعرفه‌های نمایندگی فروش',
                'itemListElement' => $tiers->values()->map(fn ($tier, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => [
                        '@type' => 'Offer',
                        'name' => $tier->name,
                        'description' => $tier->tagline ?: $tier->name,
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
