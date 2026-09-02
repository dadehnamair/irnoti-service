<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Dashboard\BankReceiptController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\InvoiceController;
use App\Http\Controllers\Dashboard\LineOrderController as DashboardLineOrderController;
use App\Http\Controllers\Dashboard\PackageOrderController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SmsController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SubscriptionController;
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
 * (sms_lines table), managed from the Filament admin panel. Buyer picks a line,
 * fills the checkout form, and either pays online (shetabit/multipay, when the
 * "line_payment_online" setting is on) or just registers an order that the
 * admin processes. Orders are tracked by token — see docs/starter.md §9/§10/§11.
 */
Route::get('/lines', [LineController::class, 'index'])->name('lines');
Route::get('/lines/{line}/checkout', [LineController::class, 'checkout'])->name('lines.checkout');
Route::post('/lines/order', [LineController::class, 'order'])->name('lines.order');
Route::get('/lines/order/{order}', [LineController::class, 'track'])->name('lines.track');
Route::get('/lines/order/{order}/pay', [LineController::class, 'pay'])->name('lines.pay');
Route::match(['get', 'post'], '/lines/payment/callback', [LineController::class, 'paymentCallback'])->name('lines.payment.callback');

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
        .'--primary:'.config('theme.primary').' !important;'
        .'--accent:'.config('theme.accent').' !important;'
        .'--secondary:'.config('theme.secondary').' !important;'
        .'}';

    return Response::make($css, 200, [
        'Content-Type' => 'text/css',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('theme.css');

/*
 * Customer authentication (docs/starter.md §26 / §27). Mobile-first: register
 * with a phone number, verify a one-time code, then complete the identity
 * profile from the dashboard. Separate from the Filament /admin login.
 */
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')->name('register.store');

    Route::get('/verify', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/verify', [OtpController::class, 'verify'])
        ->middleware('throttle:10,1')->name('otp.verify');
    Route::post('/verify/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:4,1')->name('otp.resend');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:6,1')->name('login.store');
    Route::post('/login/otp', [LoginController::class, 'requestOtp'])
        ->middleware('throttle:6,1')->name('login.otp');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Stepped identity-profile completion (docs/starter.md §26).
    Route::get('/dashboard/profile', [ProfileController::class, 'index'])->name('dashboard.profile');
    Route::get('/dashboard/profile/step/{step}', [ProfileController::class, 'edit'])
        ->whereNumber('step')->name('dashboard.profile.step');
    Route::put('/dashboard/profile/step/{step}', [ProfileController::class, 'update'])
        ->whereNumber('step')->name('dashboard.profile.update');

    /*
     * Plan selection & purchase (docs/starter.md §8 / §24). Free plans activate
     * instantly; paid plans use the same gateway flow as line orders.
     */
    Route::get('/dashboard/plans', [SubscriptionController::class, 'plans'])->name('dashboard.plans');
    Route::get('/dashboard/plan/{plan}/checkout', [SubscriptionController::class, 'checkout'])->name('dashboard.plan.checkout');
    Route::post('/dashboard/plan/order', [SubscriptionController::class, 'order'])->name('subscriptions.order');
    Route::get('/dashboard/subscription/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('/dashboard/subscription/{subscription}/pay', [SubscriptionController::class, 'pay'])->name('subscriptions.pay');
    Route::post('/dashboard/subscription/{subscription}/wallet', [SubscriptionController::class, 'payFromWallet'])->name('subscriptions.wallet');

    /*
     * Wallet & financial history (docs/starter.md §22 / §23): "شارژ حساب" to any
     * amount, the immutable ledger, plus bank-receipt submission and admin-issued
     * invoices. All money is integer Toman; dates render Jalali.
     */
    Route::get('/dashboard/wallet', [WalletController::class, 'show'])->name('dashboard.wallet');
    Route::post('/dashboard/wallet/topup', [WalletController::class, 'topup'])->name('dashboard.wallet.topup');
    Route::get('/dashboard/wallet/topup/{topup}/pay', [WalletController::class, 'pay'])->name('wallet.topup.pay');
    Route::get('/dashboard/transactions', [WalletController::class, 'transactions'])->name('dashboard.transactions');

    Route::get('/dashboard/receipts', [BankReceiptController::class, 'index'])->name('dashboard.receipts');
    Route::get('/dashboard/receipts/create', [BankReceiptController::class, 'create'])->name('dashboard.receipts.create');
    Route::post('/dashboard/receipts', [BankReceiptController::class, 'store'])->name('receipts.store');

    // SMS credit bundles (docs/starter.md §12).
    Route::get('/dashboard/packages', [PackageOrderController::class, 'index'])->name('dashboard.packages');
    Route::get('/dashboard/packages/{package}/checkout', [PackageOrderController::class, 'checkout'])->name('dashboard.packages.checkout');
    Route::post('/dashboard/packages/order', [PackageOrderController::class, 'order'])->name('package-orders.order');
    Route::get('/dashboard/packages/order/{order}', [PackageOrderController::class, 'show'])->name('package-orders.show');
    Route::get('/dashboard/packages/order/{order}/pay', [PackageOrderController::class, 'pay'])->name('package-orders.pay');
    Route::post('/dashboard/packages/order/{order}/wallet', [PackageOrderController::class, 'payFromWallet'])->name('package-orders.wallet');

    // Admin-issued invoices (docs/starter.md §22 / §51).
    Route::get('/dashboard/invoices', [InvoiceController::class, 'index'])->name('dashboard.invoices');
    Route::get('/dashboard/invoices/{invoice}', [InvoiceController::class, 'show'])->name('dashboard.invoices.show');
    Route::get('/dashboard/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('/dashboard/invoices/{invoice}/wallet', [InvoiceController::class, 'payFromWallet'])->name('invoices.wallet');

    /*
     * Panel features that need a fully approved account (docs/starter.md §39):
     * single SMS send + buying a dedicated line from inside the panel. The
     * "approved" middleware bounces unapproved accounts back to the dashboard.
     */
    Route::middleware('approved')->group(function () {
        Route::get('/dashboard/sms', [SmsController::class, 'index'])->name('dashboard.sms');
        Route::post('/dashboard/sms', [SmsController::class, 'send'])
            ->middleware('throttle:20,1')->name('dashboard.sms.send');
        Route::post('/dashboard/sms/senders/refresh', [SmsController::class, 'refreshNumbers'])
            ->middleware('throttle:10,1')->name('dashboard.sms.numbers.refresh');
        Route::post('/dashboard/sms/senders/default', [SmsController::class, 'setDefaultSender'])
            ->name('dashboard.sms.numbers.default');

        Route::get('/dashboard/lines', [DashboardLineOrderController::class, 'index'])->name('dashboard.lines');
        Route::get('/dashboard/lines/{line}/checkout', [DashboardLineOrderController::class, 'checkout'])->name('dashboard.lines.checkout');
        Route::post('/dashboard/lines/order', [DashboardLineOrderController::class, 'order'])->name('dashboard.lines.order');
        Route::get('/dashboard/lines/order/{order}', [DashboardLineOrderController::class, 'show'])->name('dashboard.lines.show');
        Route::get('/dashboard/lines/order/{order}/pay', [DashboardLineOrderController::class, 'pay'])->name('dashboard.lines.pay');
        Route::post('/dashboard/lines/order/{order}/wallet', [DashboardLineOrderController::class, 'payFromWallet'])->name('dashboard.lines.wallet');
    });
});

Route::match(['get', 'post'], '/subscriptions/payment/callback', [SubscriptionController::class, 'paymentCallback'])
    ->name('subscriptions.payment.callback');

// Gateway callbacks for the new payment flows (CSRF-excepted in bootstrap/app.php).
Route::match(['get', 'post'], '/wallet/topup/callback', [WalletController::class, 'callback'])->name('wallet.topup.callback');
Route::match(['get', 'post'], '/packages/payment/callback', [PackageOrderController::class, 'callback'])->name('package-orders.callback');
Route::match(['get', 'post'], '/invoices/payment/callback', [InvoiceController::class, 'callback'])->name('invoices.callback');

// Public SMS-package catalogue, parallel to /pricing (docs/starter.md §12).
Route::get('/sms-packages', [PricingController::class, 'packages'])->name('sms-packages');

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

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

    foreach ($urls as $url) {
        $xml .= "  <url>\n"
            ."    <loc>{$url['loc']}</loc>\n"
            ."    <lastmod>{$url['lastmod']}</lastmod>\n"
            ."    <changefreq>{$url['changefreq']}</changefreq>\n"
            ."    <priority>{$url['priority']}</priority>\n"
            ."  </url>\n";
    }

    $xml .= '</urlset>';

    return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
