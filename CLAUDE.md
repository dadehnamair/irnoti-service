# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**irnoti** — a Persian (RTL, `fa` locale) SaaS marketing/commerce site for an SMS-panel service, built on **Laravel 12 + PHP 8.2 + Filament 4**. Product/technical spec is [docs/starter.md](docs/starter.md) (Persian); its numbered sections (`§8`, `§11`, `§33`…) are referenced throughout the code comments — keep that convention.

Current scope is the **public site + admin CMS**, not the end-user SMS panel:
- Landing (`/`), pricing (`/pricing`), dedicated SMS lines catalogue + buy flow (`/lines`), blog (`/blog`), API docs (`/developers`), plus `/sitemap.xml` and `/blog/feed` (RSS).
- All content is **database-driven and edited from the Filament admin panel at `/admin`**. Blade views should never hard-code brand copy, colors, plans, or line numbers.

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

### Filament admin (`/admin`)
[`AdminPanelProvider`](app/Providers/Filament/AdminPanelProvider.php) — auto-discovers resources in `app/Filament/Resources`, primary color from `config('theme.primary')`, Vazirmatn font. Access is gated by `User::canAccessPanel()` → `is_admin` boolean column.

Resources use the Filament 4 split layout: each `XxxResource.php` has sibling `Schemas/XxxForm.php`, `Tables/XxxTable.php`, and `Pages/`. Resources: BlogPosts / BlogCategories / BlogTags, DocArticles / DocCategories, Plans, SmsLines, LineOrders, Settings.

### Models & data
Content models live in `app/Models/`. Conventions to follow when extending:
- Slugs auto-generated in a `booted()` `saving` hook when blank (`Plan`, `BlogPost`, `DocArticle`, `DocCategory` — the last has `uniqueSlug()` with collision suffixes).
- `features` columns are `array`-cast JSON, exposed as `getFeatureListAttribute()` (filtered).
- Persian display labels for enum-ish string columns are `const` maps on the model (`SmsLine::TYPES`, `LineOrder::STATUSES`, …) with `getXxxLabelAttribute()` accessors.
- Markdown body fields render via `Str::markdown(..., ['html_input' => 'strip'])` in a `getRenderedBodyAttribute()`.
- Docs are a nested tree: `DocCategory` self-references via `parent_id`; `DocsController::navigationTree()` eager-loads two levels of published children + articles.

Seeders (`database/seeders/`) are idempotent (`updateOrCreate`) and chained from `DatabaseSeeder`: Settings → Plans → SmsLines → Docs → Blog.

## Conventions

- **Persian-first, RTL.** All user-facing strings are Persian. `APP_LOCALE=fa`. Validation messages/attributes are passed inline in Persian in the controllers.
- Reference `docs/starter.md` section numbers in comments for any feature that maps to the spec.
- Never hard-code brand identity, colors, prices, contact info, or line numbers in Blade — read `config('theme.*')` or the relevant model.
- Landing/teaser queries that hit content models are wrapped in `rescue(...)` with a static fallback so the page renders on a fresh/empty DB.
- Run `./vendor/bin/pint` before finishing PHP changes (StyleCI config: [.styleci.yml](.styleci.yml)).
