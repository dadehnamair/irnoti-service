<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Standalone /faq page — same query the landing page's #faq section uses
 * (HomeController::index), kept as its own deep-linkable, SEO-friendly page.
 */
class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = rescue(
            fn () => Faq::query()->active()->ordered()->get(),
            new Collection,
            false
        );

        $canonical = route('faq');

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => rtrim(config('theme.seo.url'), '/').'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'سوالات متداول', 'item' => $canonical],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqs->map(fn (Faq $faq) => [
                        '@type' => 'Question',
                        'name' => $faq->question,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
                    ])->all(),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('faq', [
            'faqs' => $faqs,
            'jsonLd' => $jsonLd,
        ]);
    }
}
