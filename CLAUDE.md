# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**irnoti** — a Persian (RTL, `fa` locale) SaaS marketing/commerce site for an SMS-panel service, built on **Laravel 12 + PHP 8.2 + Filament 4**. Product/technical spec is [docs/starter.md](docs/starter.md) (Persian); its numbered sections (`§8`, `§11`, `§33`…) are referenced throughout the code comments — keep that convention.

Scope now spans the **public site + admin CMS + the customer dashboard** (a real logged-in SMS-panel front end has been built, not just marketing pages):
- Public: Landing (`/`), pricing (`/pricing`, per-plan `/pricing/{plan}`), USSD catalogue (`/ussd`), dedicated SMS lines catalogue + buy flow (`/lines`), blog (`/blog`), API docs (`/developers`), marketplace showcase (`/marketplace`), site content — features (`/features`), FAQ (`/faq`), contact (`/contact`), static pages (`/about`, `/cooperation`) — sales representation (`/representation`), plus `/sitemap.xml` and `/blog/feed` (RSS).
- Customer dashboard (`/dashboard/*`, behind mobile-OTP auth): SMS send + phonebook, wallet/ledger + invoices + bank receipts, subscriptions, dedicated-line orders, digital business cards, marketplace add-on installs, bulk messenger sends (Bale/Eitaa/WhatsApp) — see the **documentation map** below for each.
- All content is **database-driven and edited from the Filament admin panel at `/admin`**. Blade views should never hard-code brand copy, colors, plans, or line numbers.

## Documentation map

`docs/starter.md` is the original product spec (Persian, numbered sections `§8`, `§11`, `§33`… referenced throughout code comments — keep that convention). Several subsystems built since then have their own deep-dive doc in `docs/` — **read the relevant one before touching that area, and add a new one (plus a line here) whenever a new subsystem is built instead of only relying on this file's summaries**:

- [docs/starter.md](docs/starter.md) — product/technical spec (Persian).
- [docs/finance.md](docs/finance.md) — wallet + immutable ledger, bank receipts, SMS packages, admin invoices, `PayableSettlement` (the shared "mark paid" dispatcher every purchase flow converges on).
- [docs/phonebook.md](docs/phonebook.md) — contacts/groups with two-way sync to the customer's own SMS panel, group SMS sending.
- [docs/panel-features.md](docs/panel-features.md) — the feature-catalogue + user-group + per-user-override system that drives the entire dashboard sidebar and gates non-system pages.
- [docs/messenger.md](docs/messenger.md) — bulk sending to Bale/Eitaa/WhatsApp, wallet-priced with failed-portion refund.
- [docs/business-cards.md](docs/business-cards.md) — digital business card public pages (vanity domains + codes), standard/VIP pricing.
- [docs/marketplace.md](docs/marketplace.md) — installable add-ons (integrations like ایرپلاس, internal feature unlocks like the business card) with a common handler architecture.
- [docs/site-content.md](docs/site-content.md) — admin-managed static pages (About/Cooperation), FAQ, the marketing features showcase, and the contact form.
- [docs/sales-representation.md](docs/sales-representation.md) — admin-defined commission tiers + a manually-reviewed lead-capture application form, no self-service payment/activation.

## Commands

```bash
composer setup            # first run: install, .env, key:gen, migrate, npm install, npm run build
composer dev              # run everything: php artisan serve + queue:listen + pail (logs) + vite, concurrently
npm run dev               # vite dev server only
npm run build             # production asset build

composer test             # config:clear then artisan test (full suite)
php artisan test --filter=LinePurchaseTest              # single test class
php artisan test --filter=test_gateway_callback_marks_order_paid   # single test method
php artisan test tests/Feature/LinePurchaseTest.php     # single file

./vendor/bin/pint         # format PHP (Laravel Pint; also enforced by StyleCI)

php artisan migrate:fresh --seed   # rebuild DB + reseed (admin user, settings, plans, lines, docs, blog)
```

Tests run on in-memory SQLite (see [phpunit.xml](phpunit.xml)); the dev/prod DB is MySQL. Seeded admin login and SMS/payment credentials come from `.env` — see [.env.example](.env.example).

## Architecture

### Theme / brand: a three-layer cascade
Everything visual and identity-related resolves through `config('theme.*')`, but the value can come from three places (last wins):

1. **[config/theme.php](config/theme.php)** — file defaults (brand name, taglines, colors, contact, SEO, nav).
2. **`.env`** — `THEME_PRIMARY`, `THEME_ACCENT`, `THEME_SECONDARY`, `THEME_EMAIL`, etc. override the file.
3. **`settings` DB table** — [`App\Models\Setting`](app/Models/Setting.php), edited in the admin panel. [`AppServiceProvider::overlaySettingsOnConfig()`](app/Providers/AppServiceProvider.php) runs on every boot and overlays non-empty settings rows onto `config('theme.*')`, so views keep reading `config()` unchanged. Falls back silently to file/env when the table is missing (fresh checkout / mid-migration).

`Setting::map()` is cached forever (`settings.map` key) and auto-flushed on save/delete. Read settings via `Setting::get($key, $default)`.

**Colors reach the browser** via the `GET /assets/theme.css` route (`theme.css` name), which emits `:root{--primary/--accent/--secondary}` custom properties with `!important`. Every public layout links it **after** `@vite(...)` so it wins. All shades are derived in CSS with `color-mix()` from those three vars — there is no asset rebuild for a re-theme. `--secondary` is intentionally green (online/success semantics) and must not track the brand color.

### Front-end assets (Vite + Tailwind 4)
Vite inputs are in [vite.config.js](vite.config.js). `resources/js/app.js` imports the shared public-site bundle: `resources/css/irnoti.css` (~1700 lines, design tokens + landing/lines/pricing styles) and `resources/js/irnoti.js` (mobile nav, etc.). The blog and docs sections layer their own bundles (`blog.css`/`blog.js`, `docs.css`/`docs.js`) on top of the `app` bundle. Public Blade layouts load assets as:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js', ...section bundle])
<link rel="stylesheet" href="{{ route('theme.css') }}" />
```

### Public controllers (`app/Http/Controllers/`)
Thin, read-only, no auth. `PricingController`, `BlogController`, `DocsController`, `LineController`. They query models with scopes (`->active()->ordered()`, `->published()`, `->visible()`) and return Blade views. `BlogController::feed()` and the `sitemap.xml` closure in [routes/web.php](routes/web.php) emit XML with heredocs.

### Lines purchase flow (`LineController`) — docs/starter.md §9–§11
- `SmsLine` = a line number offered for sale (admin-managed). `LineOrder` = a purchase request; **route key is `token`** (random 24-char, auto-set on `creating`), not `id` — order pages are public and unguessable.
- Flow: `/lines` (catalogue, grouped by `prefix`, filtered client-side) → `/lines/{line}/checkout` → `POST /lines/order` → either the payment gateway or straight to the token tracking page `/lines/order/{token}`.
- **Online payment is gated by two conditions**: the `line_payment_online` setting (admin toggle) AND the line being `price > 0` and not `requires_inquiry`. When off, orders just land as `pending`/`awaiting_payment` for the admin to process.
- Payment uses **shetabit/multipay** via [config/payment.php](config/payment.php); driver = `PAYMENT_DRIVER` env (`local` = bundled test gateway, no credentials). `pay()` → `Payment::purchase()->pay()->render()`; `paymentCallback()` handles both GET and POST, reads `transactionId`/`Authority`/etc. depending on driver, then `Payment::verify()` and marks the order `paid`. `LineOrder::STATUSES` is the full status workflow.

### Customer accounts & auth (`app/Http/Controllers/Auth`, `Dashboard`) — docs/starter.md §26/§27
- **Mobile-first, stepped.** `web` guard + session (no Breeze). `/register` takes only a mobile → creates a `pending` `User` → texts a 5-digit code (`OtpCode`, hashed, 5-min TTL, 90s resend throttle) → `/verify` logs in and flips the account to `active`. `/login` is mobile+password when a password was set, else `/login/otp` reuses the OTP flow. Filament's `/admin` login is entirely separate.
- The identity profile (all of §26's fields + 3 ID images) is completed later from `/dashboard/profile/step/{1..3}` — each step validates and persists only its own slice so it's resumable; step 3 sets `profile_completed_at` and notifies the admin. ID images go on the **private `local` disk** under `identity/{user_id}/`.
- **Account type — حقیقی / حقوقی (`users.account_type` = `individual` | `legal`, `User::ACCOUNT_TYPES`, `User::isLegal()`).** Chosen by a radio at the top of profile wizard **step 1**; a Blade inline script + CSS toggles the `[data-when="legal|individual"]` blocks and disables the hidden side's inputs (server ignores extra fields anyway). For a **legal** account:
  - Step 1 also captures the company registration data — `company` (full name), `company_type`, `company_national_id` (شناسه ملی, `digits:11`), `company_registration_number`, `company_registered_at` (**Jalali free text, plain string column** — no date cast), `company_economic_code`, `company_phone`, `company_postal_code`, `company_address`, `rep_role`. `first_name`/`last_name`/`national_code` + the 3 ID images describe the **signing representative**.
  - Step 3 adds the company gazette docs on the private `local` disk under `identity/{id}/company/` — `company_registration_doc` (آگهی تأسیس/روزنامه رسمی, required until first uploaded), `company_changes_doc`, and `company_extra_docs` (**`array`-cast JSON**, multiple files, appended not replaced; `company_extra_docs[]` in the form). Company docs accept `pdf` too; a fresh company-doc upload also resets `documents_status` to `pending`.
  - `users.name` / `User::full_name` resolve to the **company name** for a legal account (the `saving` hook watches `company`/`account_type`); `User::contact_name` is the representative's name. `account_type` + the registration fields join `LOCKED_IDENTITY_FIELDS` (frozen after admin approval); company contact fields (`company_phone`/`_postal_code`/`_address`/`rep_role`) stay editable.
  - Filament `UserResource`: `account_type` select (`live()`) drives a conditional «مشخصات و اطلاعات ثبتی شرکت» section (fields + 3 doc uploaders); `UsersTable` has an `account_type` badge column + filter. Spec: [docs/starter.md](docs/starter.md) §26 «نوع حساب: حقیقی / حقوقی».
- A plan chosen on `/pricing` is carried as `?plan=<slug>&period=` → stashed in `session('intended_plan')` → after verification the user lands on that plan's checkout instead of the dashboard. `partials/plan-cta.blade.php` builds every plan-card CTA (guest → `register`, auth → `dashboard.plan.checkout`).

### Subscriptions / plan purchase (`SubscriptionController`) — docs/starter.md §8/§24
- `Subscription` mirrors `LineOrder`: **route key `token`**, snapshot columns, `STATUSES` const map. Free plan (`price 0`) → created `active`, plan rolled onto `users.plan_id`/`plan_expires_at` immediately, shown as «رایگان». Paid plan → gateway when the `plan_payment_online` setting is on, else `awaiting_payment` for the admin.
- Gateway plumbing shared with lines via the `App\Support\HandlesGatewayPayment` trait (`purchaseViaGateway` / `verifyViaGateway` / driver-agnostic request parsing). Subscription callback is `subscriptions/payment/callback` (CSRF-excepted in `bootstrap/app.php`).

### SMS provider layer (`app/Services/Sms`) — docs/starter.md §12/§13/§44
- All SMS config lives in [config/sms.php](config/sms.php) — `provider` (active codename), `label` (brand-neutral name shown to customers — **never expose the real vendor**; overlaid by the `sms_provider_label` setting, read via the `sms_provider_label()` helper), `admin_mobile`, and a `providers` registry mapping an opaque codename → driver class + credentials.
- `SmsProviderInterface` (`send` / `sendPattern` / `deliveryStatus`) with `LogProvider` (credential-free dev default, `SMS_PROVIDER=log`, like `PAYMENT_DRIVER=local`), `PasargadProvider` (prod driver — internal codename for the real upstream, env `SMS_PASARGAD_*`), `NullProvider` (tests, `SMS_PROVIDER=null` in `phpunit.xml`). Bound in `AppServiceProvider::register()` from the `config('sms.providers.*')` registry keyed by `config('sms.provider')`.
- Never call a provider directly: dispatch the queued `App\Jobs\SendSmsJob` (`::text()` / `::pattern()`). Operation notifications go through the single `App\Support\OperationNotifier` (`userRegistered`, `profileCompleted`, `subscriptionActivated`, `lineOrderCreated`/`Paid`/`StatusChanged`, `businessCardPaid`, …), gated by the `sms_notifications_enabled` setting, admin copy sent to `admin_mobile`. `LineOrderObserver` covers admin status changes made from Filament.
- The phonebook's contact/group sync talks to the same customer-specific panel credentials via `App\Services\Sms\Phonebook\*` — see [docs/phonebook.md](docs/phonebook.md).

### Finance / wallet (`app/Support/PayableSettlement`, `app/Models/Wallet*`)
Every customer has one `Wallet`, mutated only through an immutable `WalletTransaction` ledger (row-locked, idempotency-keyed). Every purchase flow (plan, line, SMS package, business card, marketplace install, admin invoice) converges on the single `PayableSettlement::settle()` dispatcher, reachable from a gateway callback, a "pay from wallet" action, or bank-receipt approval. Full detail: [docs/finance.md](docs/finance.md).

### Panel features access (dashboard sidebar)
The entire customer-dashboard sidebar is generated from one `FeatureCatalog` PHP array, mirrored into the `features` table by `FeaturesSeeder`, bundled into `UserGroup`s, with per-user `UserFeatureOverride` grant/revoke exceptions. Controllers gate non-system pages with `abort_unless($user->canUseFeature($key), 403)` — there is no blanket middleware for this. Full detail, and the checklist for adding a new dashboard feature: [docs/panel-features.md](docs/panel-features.md).

### Phonebook, messenger, business cards, marketplace
Customer-dashboard subsystems built on top of the finance + panel-features layers above. See their dedicated docs rather than re-deriving from code: [docs/phonebook.md](docs/phonebook.md), [docs/messenger.md](docs/messenger.md), [docs/business-cards.md](docs/business-cards.md), [docs/marketplace.md](docs/marketplace.md).

### Site content & sales representation
Small admin-managed public pages that round out the marketing site: static `Page` rows (About/Cooperation), `Faq`, the `SiteFeature` marketing showcase, and the `ContactMessage` lead form — see [docs/site-content.md](docs/site-content.md). A parallel, unrelated-to-payment lead flow, admin-defined commission tiers plus a manually-reviewed application form, lives at `/representation` — see [docs/sales-representation.md](docs/sales-representation.md).

### Filament admin (`/admin`)
[`AdminPanelProvider`](app/Providers/Filament/AdminPanelProvider.php) — auto-discovers resources in `app/Filament/Resources`, primary color from `config('theme.primary')`, Vazirmatn font, Jalali (Shamsi) date pickers panel-wide via a single `DateTimePicker::configureUsing(...->jalali())` hook in `AppServiceProvider::boot()`. Access is gated by `User::canAccessPanel()` → `is_admin` boolean column.

Resources use the Filament 4 split layout: each `XxxResource.php` has sibling `Schemas/XxxForm.php`, `Tables/XxxTable.php`, and `Pages/`. Beyond the original content resources (BlogPosts / BlogCategories / BlogTags, DocArticles / DocCategories, Plans, SmsLines, LineOrders, Users, Subscriptions, Settings), there are now resources per subsystem above: WalletTransactions, BankReceipts, Invoices, PackageOrders, SmsPackages, Contacts, ContactGroups, Features, UserGroups, MessengerCampaigns, BusinessCards, MarketplaceApps/MarketplaceInstallations, Domains. Most customer-born resources are edit/view-only (`canCreate() === false`) — records are born on the public site or the dashboard, not in Filament.

### Models & data
Content models live in `app/Models/`. Conventions to follow when extending:
- Slugs auto-generated in a `booted()` `saving` hook when blank (`Plan`, `BlogPost`, `DocArticle`, `DocCategory` — the last has `uniqueSlug()` with collision suffixes).
- `features` columns are `array`-cast JSON, exposed as `getFeatureListAttribute()` (filtered).
- Persian display labels for enum-ish string columns are `const` maps on the model (`SmsLine::TYPES`, `LineOrder::STATUSES`, `User::STATUSES`, `Subscription::STATUSES`, …) with `getXxxLabelAttribute()` accessors.
- `token` route-key models (`LineOrder`, `Subscription`) set a random 24-char token in a `booted()` `creating` hook and override `getRouteKeyName()`.
- Markdown body fields render via `Str::markdown(..., ['html_input' => 'strip'])` in a `getRenderedBodyAttribute()`.
- Docs are a nested tree: `DocCategory` self-references via `parent_id`; `DocsController::navigationTree()` eager-loads two levels of published children + articles.

Seeders (`database/seeders/`) are idempotent (`updateOrCreate`) and chained from `DatabaseSeeder` (which also creates the admin user), now including Settings → Plans → SmsLines → Docs → Blog → Features/UserGroups → Domains → SmsPackages → MarketplaceApps. New admin-editable toggles live in `SettingsSeeder`, grouped by area (`commerce`, `messenger`, …) — see [docs/finance.md](docs/finance.md), [docs/messenger.md](docs/messenger.md), [docs/business-cards.md](docs/business-cards.md) for the settings each subsystem reads.

## Conventions

- **Persian-first, RTL.** All user-facing strings are Persian. `APP_LOCALE=fa`. Validation messages/attributes are passed inline in Persian in the controllers.
- Reference `docs/starter.md` section numbers in comments for any feature that maps to the spec.
- Never hard-code brand identity, colors, prices, contact info, or line numbers in Blade — read `config('theme.*')` or the relevant model.
- Landing/teaser queries that hit content models are wrapped in `rescue(...)` with a static fallback so the page renders on a fresh/empty DB.
- Run `./vendor/bin/pint` before finishing PHP changes (StyleCI config: [.styleci.yml](.styleci.yml)).
- **Keep the documentation map current.** When a new subsystem/feature area is built (not just a small addition to an existing one), add a `docs/<subsystem>.md` file (Persian, same structure as the existing ones: concept → data model with file links → flow → settings → Filament → tests) and add one line for it under **Documentation map** above and in this Architecture section. Don't let `CLAUDE.md` drift out of sync with what actually exists — it did once (wallet/phonebook/messenger/business-cards/marketplace all shipped before this file mentioned any of them).
