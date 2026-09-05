<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Generic static content pages (About / Cooperation) — one row per slug in
 * the `pages` table, edited from the Filament admin panel. Routes bind the
 * slug via `->defaults('slug', ...)` so URLs stay clean (/about, /cooperation)
 * while the content is fully data-driven, like LegalController's terms/privacy.
 */
class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->published()->firstOrFail();

        $rendered = $page->rendered_body;
        $metaDescription = $page->seo_description
            ?: Str::of(strip_tags($rendered))->squish()->limit(160)->value();

        $siteUrl = rtrim(config('theme.seo.url'), '/');
        $url = url()->current();

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl.'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $page->title, 'item' => $url],
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    'name' => $page->seo_title ?: $page->title,
                    'url' => $url,
                    'isPartOf' => ['@type' => 'WebSite', 'name' => config('theme.brand'), 'url' => $siteUrl.'/'],
                    'inLanguage' => 'fa-IR',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('page', [
            'page' => $page,
            'title' => $page->seo_title ?: $page->title,
            'rendered' => $rendered,
            'metaDescription' => $metaDescription,
            'jsonLd' => $jsonLd,
        ]);
    }
}
