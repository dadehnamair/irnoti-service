<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Dashboard\BankReceiptController;
use App\Http\Controllers\Dashboard\BusinessCardController;
use App\Http\Controllers\Dashboard\ContactController;
use App\Http\Controllers\Dashboard\ContactGroupController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\InvoiceController;
use App\Http\Controllers\Dashboard\LineOrderController as DashboardLineOrderController;
use App\Http\Controllers\Dashboard\MarketplaceController;
use App\Http\Controllers\Dashboard\MessagesController;
use App\Http\Controllers\Dashboard\MessengerController;
use App\Http\Controllers\Dashboard\PackageOrderController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SmsController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LineController;
use App\Http\Controllers\MarketplaceShowcaseController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\PublicBusinessCardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UssdController;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Plan;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
 * Standalone pricing / plans page ("/pricing"). Reads active plans from the DB
 * (plans table) — same source as the landing "تعرفه‌ها" section.
 */
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

/*
 * Dedicated per-plan page ("/pricing/{plan}") so Google indexes each plan as
 * its own Product with its own canonical URL/JSON-LD, instead of only ever
 * seeing the aggregate /pricing catalogue.
 */
Route::get('/pricing/{plan}', [PricingController::class, 'show'])->name('pricing.show');

/*
 * USSD plan catalogue ("/ussd") — reuses the Plan/Subscription purchase
 * machinery, scoped to plans.type = "ussd". Checkout is the same
 * dashboard.plan.checkout / subscriptions.* flow used by /pricing.
 */
Route::get('/ussd', [UssdController::class, 'index'])->name('ussd');

/*
 * Dedicated SMS lines ("/lines") — the catalogue of line numbers for sale
 * (sms_lines table), managed from the Filament admin panel. Buyer picks a line,
 * fills the checkout form, and either pays online (shetabit/multipay, when the
 * "line_payment_online" setting is on) or just registers an order that the
 * admin processes. Orders are tracked by token — see docs/starter.md §9/§10/§11.
 */
Route::get('/lines', [LineController::class, 'index'])->name('lines');

/*
 * «بازارچه» public showcase ("/marketplace") — the marketing catalogue
 * of installable add-ons (marketplace_apps table, docs/starter.md §15). Read-only;
 * the actual install / connect / pay flow lives behind auth under
 * /dashboard/marketplace.
 */
Route::get('/marketplace', [MarketplaceShowcaseController::class, 'index'])->name('marketplace');
Route::get('/lines/{line}/checkout', [LineController::class, 'checkout'])->name('lines.checkout');
Route::post('/lines/order', [LineController::class, 'order'])->name('lines.order');
Route::get('/lines/order/{order}', [LineController::class, 'track'])->name('lines.track');
Route::get('/lines/order/{order}/pay', [LineController::class, 'pay'])->name('lines.pay');
Route::match(['get', 'post'], '/lines/payment/callback', [LineController::class, 'paymentCallback'])->name('lines.payment.callback');

/*
 * Legal pages linked from the site footer (docs/starter.md §67). Body copy is
 * markdown stored in the `settings` table (legal_terms_body / legal_privacy_body)
 * and editable from the Filament admin panel.
 */
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

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
     * Self-service digital business cards: create, edit, buy ("standard" flat
     * price, or "vip" — a custom code under one of the admin-managed domains,
     * priced by that domain's code_price_tiers). Static segments before {card}.
     */
    Route::get('/dashboard/cards', [BusinessCardController::class, 'index'])->name('dashboard.cards');
    Route::get('/dashboard/cards/create', [BusinessCardController::class, 'create'])->name('dashboard.cards.create');
    Route::get('/dashboard/cards/quote', [BusinessCardController::class, 'quote'])->name('dashboard.cards.quote');
    Route::post('/dashboard/cards', [BusinessCardController::class, 'store'])->name('dashboard.cards.store');
    Route::get('/dashboard/cards/{card}/edit', [BusinessCardController::class, 'edit'])->name('dashboard.cards.edit');
    Route::put('/dashboard/cards/{card}', [BusinessCardController::class, 'update'])->name('dashboard.cards.update');
    Route::get('/dashboard/cards/{card}/pay', [BusinessCardController::class, 'pay'])->name('dashboard.cards.pay');
    Route::post('/dashboard/cards/{card}/wallet', [BusinessCardController::class, 'payFromWallet'])->name('dashboard.cards.wallet');

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

    /*
     * «بازارچه» (docs/starter.md §15): the add-on catalogue + install /
     * connect / pay / manage flow. Static `app/…`, `i/…` and `payment/callback`
     * segments are declared before the {app:slug} wildcard. Behaviour per add-on
     * lives in its handler class (config/marketplace.php).
     */
    Route::get('/dashboard/marketplace', [MarketplaceController::class, 'index'])->name('dashboard.marketplace');
    Route::get('/dashboard/marketplace/app/{app:slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');
    Route::post('/dashboard/marketplace/app/{app:slug}/install', [MarketplaceController::class, 'install'])->name('marketplace.install');
    Route::get('/dashboard/marketplace/i/{installation:token}', [MarketplaceController::class, 'manage'])->name('marketplace.manage');
    Route::post('/dashboard/marketplace/i/{installation:token}/sync', [MarketplaceController::class, 'sync'])
        ->middleware('throttle:20,1')->name('marketplace.sync');
    Route::post('/dashboard/marketplace/i/{installation:token}/config', [MarketplaceController::class, 'updateConfig'])->name('marketplace.config');
    Route::post('/dashboard/marketplace/i/{installation:token}/wallet', [MarketplaceController::class, 'payFromWallet'])->name('marketplace.wallet');
    Route::get('/dashboard/marketplace/i/{installation:token}/pay', [MarketplaceController::class, 'pay'])->name('marketplace.pay');
    Route::delete('/dashboard/marketplace/i/{installation:token}', [MarketplaceController::class, 'uninstall'])->name('marketplace.uninstall');

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

        /*
         * «پیام‌ها» menu (docs/starter.md §14): دریافتی + ارسالی. The pages read a
         * local mirror (provider_messages); opening one — or the «بروزرسانی»
         * button — queues SyncProviderMessagesJob to refresh it from the panel.
         */
        Route::get('/dashboard/messages/inbox', [MessagesController::class, 'inbox'])->name('dashboard.messages.inbox');
        Route::get('/dashboard/messages/sent', [MessagesController::class, 'sent'])->name('dashboard.messages.sent');
        Route::post('/dashboard/messages/{box}/refresh', [MessagesController::class, 'refresh'])
            ->whereIn('box', ['inbox', 'sent'])->middleware('throttle:10,1')->name('dashboard.messages.refresh');

        /*
         * پیام‌رسان‌ها (docs/starter.md §91): a service parallel to SMS for bulk
         * sending to بله / ایتا / واتساپ from a contact group or a pasted list.
         * Cost is taken from the wallet up front; SendMessengerCampaignJob
         * delivers and refunds whatever fails.
         */
        Route::get('/dashboard/messenger', [MessengerController::class, 'index'])->name('dashboard.messenger');
        Route::get('/dashboard/messenger/{channel}', [MessengerController::class, 'create'])
            ->whereIn('channel', array_keys((array) config('messenger.channels')))
            ->name('dashboard.messenger.create');
        Route::post('/dashboard/messenger/send', [MessengerController::class, 'send'])
            ->middleware('throttle:10,1')->name('dashboard.messenger.send');

        /*
         * Phonebook (docs/starter.md §17): contacts + groups CRUD, mirrored to the
         * customer's own SMS panel, plus group SMS. Static segments are
         * declared before the {contact} routes.
         */
        Route::get('/dashboard/contacts', [ContactController::class, 'index'])->name('dashboard.contacts');
        Route::post('/dashboard/contacts', [ContactController::class, 'store'])->name('dashboard.contacts.store');
        Route::get('/dashboard/contacts/check-mobile', [ContactController::class, 'checkMobile'])->name('dashboard.contacts.check-mobile');
        Route::get('/dashboard/contacts/send', [SmsController::class, 'bulk'])->name('dashboard.contacts.send');
        Route::post('/dashboard/contacts/send', [SmsController::class, 'sendBulk'])
            ->middleware('throttle:10,1')->name('dashboard.contacts.send.post');
        Route::get('/dashboard/contacts/groups', [ContactGroupController::class, 'index'])->name('dashboard.contacts.groups');
        Route::post('/dashboard/contacts/groups', [ContactGroupController::class, 'store'])->name('dashboard.contacts.groups.store');
        Route::get('/dashboard/contacts/groups/{group}/edit', [ContactGroupController::class, 'edit'])->name('dashboard.contacts.groups.edit');
        Route::put('/dashboard/contacts/groups/{group}', [ContactGroupController::class, 'update'])->name('dashboard.contacts.groups.update');
        Route::delete('/dashboard/contacts/groups/{group}', [ContactGroupController::class, 'destroy'])->name('dashboard.contacts.groups.destroy');
        Route::post('/dashboard/contacts/groups/{group}/sync', [ContactGroupController::class, 'sync'])->name('dashboard.contacts.groups.sync');
        Route::post('/dashboard/contacts/groups/{group}/contacts', [ContactGroupController::class, 'pullContacts'])
            ->middleware('throttle:20,1')->name('dashboard.contacts.groups.pull');
        Route::post('/dashboard/contacts/import', [ContactGroupController::class, 'importGroups'])
            ->middleware('throttle:6,1')->name('dashboard.contacts.import');
        Route::get('/dashboard/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('dashboard.contacts.edit');
        Route::put('/dashboard/contacts/{contact}', [ContactController::class, 'update'])->name('dashboard.contacts.update');
        Route::delete('/dashboard/contacts/{contact}', [ContactController::class, 'destroy'])->name('dashboard.contacts.destroy');

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
Route::match(['get', 'post'], '/marketplace/payment/callback', [MarketplaceController::class, 'callback'])->name('marketplace.payment.callback');
Route::match(['get', 'post'], '/invoices/payment/callback', [InvoiceController::class, 'callback'])->name('invoices.callback');
Route::match(['get', 'post'], '/cards/payment/callback', [BusinessCardController::class, 'paymentCallback'])->name('cards.payment.callback');

// Public SMS-package catalogue, parallel to /pricing (docs/starter.md §12).
Route::get('/sms-packages', [PricingController::class, 'packages'])->name('sms-packages');

Route::get('/sitemap.xml', function () {
    $today = now()->toDateString();

    $urls = [
        ['loc' => route('home'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => route('pricing'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => route('lines'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['loc' => route('marketplace'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('ussd'), 'lastmod' => $today, 'changefreq' => 'weekly', 'priority' => '0.7'],
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

    foreach (Plan::query()->active()->ordered()->get() as $plan) {
        $urls[] = [
            'loc' => route('pricing.show', $plan->slug),
            'lastmod' => optional($plan->updated_at)->toDateString() ?: $today,
            'changefreq' => 'weekly',
            'priority' => '0.6',
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

/*
 * Public digital-business-card page — vanity code under one of the
 * admin-managed domains (11v.ir, 7db.ir, irnoti.com, …). Resolved by Host
 * header in PublicBusinessCardController, so no per-domain route is needed.
 * Kept absolutely last so every other path above wins first.
 */
Route::get('/{code}', [PublicBusinessCardController::class, 'show'])
    ->where('code', '[A-Za-z0-9\-]{2,32}')
    ->name('cards.show');
