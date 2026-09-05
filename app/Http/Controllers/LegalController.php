<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Legal pages linked from the site footer (docs/starter.md §67). Body copy is
 * markdown stored in the `settings` table (legal_terms_body / legal_privacy_body)
 * and editable from the Filament admin panel.
 */
class LegalController extends Controller
{
    public function terms(): View
    {
        return $this->render('قوانین و مقررات', Setting::get('legal_terms_body', ''));
    }

    public function privacy(): View
    {
        return $this->render('حریم خصوصی', Setting::get('legal_privacy_body', ''));
    }

    private function render(string $title, string $body): View
    {
        $rendered = Str::markdown($body ?: '', ['html_input' => 'strip']);
        $metaDescription = Str::of(strip_tags($rendered))->squish()->limit(160)->value();

        $siteUrl = rtrim(config('theme.seo.url'), '/');
        $url = url()->current();

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl.'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => $url],
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    'name' => $title,
                    'url' => $url,
                    'isPartOf' => ['@type' => 'WebSite', 'name' => config('theme.brand'), 'url' => $siteUrl.'/'],
                    'inLanguage' => 'fa-IR',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('legal', [
            'title' => $title,
            'rendered' => $rendered,
            'metaDescription' => $metaDescription,
            'jsonLd' => $jsonLd,
        ]);
    }
}
