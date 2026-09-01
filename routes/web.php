<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\PricingController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

/*
 * Standalone pricing / plans page ("/pricing"). Reads active plans from the DB
 * (plans table) — same source as the landing "تعرفه‌ها" section.
 */
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

/*
 * Dedicated SMS lines ("/lines") — the catalogue of line numbers for sale
 * (sms_lines table), managed from the Filament admin panel. Buyers submit a
 * purchase request (line_orders) and track it by token — see docs/starter.md
 * §9 / §10 / §11. No online payment yet: the admin processes each order.
 */
Route::get('/lines', [LineController::class, 'index'])->name('lines');
Route::post('/lines/order', [LineController::class, 'order'])->name('lines.order');
Route::get('/lines/order/{order}', [LineController::class, 'track'])->name('lines.track');

/*
 * Blog ("/blog"). Content-marketing articles managed from the Filament admin
 * panel (blog_posts / blog_categories / blog_tags) — see docs/starter.md §33.
 */
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/feed', [BlogController::class, 'feed'])->name('feed');
    Route::get('/category/{category}', [BlogController::class, 'category'])->name('category');
    Route::get('/tag/{tag}', [BlogController::class, 'tag'])->name('tag');
    Route::get('/{post}', [BlogController::class, 'show'])->name('show');
});

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
    $today = now()->toDateString();

    $urls = [
        ['loc' => route('home'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => route('pricing'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => route('lines'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => route('blog.index'), 'lastmod' => $today, 'changefreq' => 'daily', 'priority' => '0.8'],
        ['loc' => route('docs.index'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.6'],
    ];

    foreach (BlogCategory::query()->visible()->get() as $category) {
        $urls[] = [
            'loc' => route('blog.category', $category->slug),
            'lastmod' => optional($category->updated_at)->toDateString() ?: $today,
            'changefreq' => 'weekly',
            'priority' => '0.5',
        ];
    }

    foreach (BlogPost::query()->published()->get() as $post) {
        $urls[] = [
            'loc' => route('blog.show', $post->slug),
            'lastmod' => optional($post->updated_at)->toDateString() ?: $today,
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
        $xml .= "  <url>\n"
            . "    <loc>{$url['loc']}</loc>\n"
            . "    <lastmod>{$url['lastmod']}</lastmod>\n"
            . "    <changefreq>{$url['changefreq']}</changefreq>\n"
            . "    <priority>{$url['priority']}</priority>\n"
            . "  </url>\n";
    }

    $xml .= '</urlset>';

    return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
