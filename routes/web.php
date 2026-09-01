<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

/*
 * API documentation ("/developers"). Content is fully database-driven
 * (doc_categories / doc_articles / doc_code_samples / doc_parameters) and
 * managed from the Filament admin panel — see docs/starter.md §34.
 */
Route::prefix('developers')->name('docs.')->group(function () {
    Route::get('/', [DocsController::class, 'index'])->name('index');
    Route::get('/{category}', [DocsController::class, 'category'])->name('category');
    Route::get('/{category}/{article}', [DocsController::class, 'show'])->name('show');
});

/*
 * Runtime theme stylesheet. Emits the brand color custom properties from
 * config/theme.php so the whole public site can be re-themed by changing
 * THEME_PRIMARY in .env — no asset rebuild needed. Loaded after the compiled
 * CSS in the <head>, so these win. Kept as a route (not inline CSS-in-Blade)
 * so Blade formatters can't mangle it.
 */
Route::get('/assets/theme.css', function () {
    $css = ':root{'
        . '--primary:' . config('theme.primary') . ' !important;'
        . '--accent:' . config('theme.accent') . ' !important;'
        . '--secondary:' . config('theme.secondary') . ' !important;'
        . '}';

    return Response::make($css, 200, [
        'Content-Type' => 'text/css',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('theme.css');

Route::get('/sitemap.xml', function () {
    $base = rtrim(config('theme.seo.url'), '/');
    $today = now()->toDateString();

    $urls = [
        ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
        $xml .= "  <url>\n"
            . "    <loc>{$url['loc']}</loc>\n"
            . "    <lastmod>{$today}</lastmod>\n"
            . "    <changefreq>{$url['changefreq']}</changefreq>\n"
            . "    <priority>{$url['priority']}</priority>\n"
            . "  </url>\n";
    }

    $xml .= '</urlset>';

    return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
