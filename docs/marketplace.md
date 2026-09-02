# راهنمای «بازارچه افزونه‌ها» (Marketplace)

> مکمل [starter.md](starter.md) §15. این سند معماری، نحوه‌ی افزودن افزونه‌ی جدید، و
> گردش خرید/فعال‌سازی را توضیح می‌دهد.

---

## ۱. مفهوم

**بازارچه** جایی است که کاربر افزونه‌های کسب‌وکار را به پنل خود اضافه می‌کند. هر افزونه یکی از دو نوع است:

| نوع | نمونه | چه می‌کند |
|---|---|---|
| **اتصال بیرونی** (`integration`) | ایرپلاس | با کلید API به یک سرویس بیرونی وصل می‌شود؛ داده (لیست مسافران، گروه‌بندی‌ها) را می‌کشد و یک دفترچه‌تلفن اختصاصی می‌سازد. |
| **قابلیت داخلی** (`card` / `messaging` / `tool`) | کارت ویزیت، منشی پیامکی | یک ردیف از منوی کناری پنل را پشت دروازه‌ی خرید/نصب باز می‌کند. |

هر دو از یک زیرساخت مشترک استفاده می‌کنند: ردیف کاتالوگ + یک **کلاس هندلر** + قیمت‌گذاری
(رایگان / خرید یک‌باره / اشتراک دوره‌ای).

نام‌گذاری: رابط کاربری = «بازارچه افزونه‌ها»؛ کد = namespace `App\Marketplace`،
جدول‌ها `marketplace_apps` / `marketplace_installations`.

---

## ۲. مدل داده

### جدول `marketplace_apps` — کاتالوگ (مدیر می‌سازد)

| ستون | توضیح |
|---|---|
| `slug` | کلید مسیر (`/dashboard/marketplace/app/{slug}`) |
| `name` / `vendor` / `tagline` / `description` | معرفی؛ `description` مارک‌داون |
| `category` | `integration` \| `messaging` \| `card` \| `tool` \| `other` |
| `icon` / `accent_color` / `docs_url` | نمایش |
| `handler` | کلید هندلر در `config/marketplace.php` |
| `billing_type` | `free` \| `one_time` \| `subscription` |
| `price` (تومان) / `billing_period` (`monthly`\|`yearly`) / `trial_days` | قیمت |
| `config_schema` (JSON) | فیلدهای فرم اتصال: `[{key,label,type,required,secret,help}]` |
| `capabilities` (JSON) | کلید ردیف‌های `features` که با نصب فعال می‌شوند (فقط برای `feature_unlock`) |
| `is_active` / `is_featured` / `sort` | «بزودی»/زنده، ویژه، ترتیب |

مدل: [`app/Models/MarketplaceApp.php`](../app/Models/MarketplaceApp.php) — `slug` خودکار، `scopeActive`/`scopeOrdered`،
`configFields()`، `capabilityKeys()`، `price_label`.

### جدول `marketplace_installations` — نصبِ هر کاربر (روی سایت متولد می‌شود)

| ستون | توضیح |
|---|---|
| `token` | کلید مسیر غیرقابل‌حدس ۲۴ نویسه‌ای (مثل `LineOrder` / `Subscription`) |
| `user_id` / `marketplace_app_id` | یکتا با هم (نصب تکراری ممنوع) |
| `status` | `pending` → `awaiting_payment` → `active` → `expired` / `suspended` / `cancelled` |
| `config` | **`encrypted:array`** — اعتبارهای API کاربر |
| `settings` | `array` — وضعیت رانتایم هندلر (مثل `group_id` محلی، cursor) |
| `price` / `billing_type` / `billing_period` | snapshot هنگام نصب |
| `payment_driver` / `transaction_id` / `reference_id` / `paid_at` | پرداخت (مثل `PackageOrder`) |
| `installed_at` / `activated_at` / `expires_at` / `last_synced_at` | چرخه‌ی عمر |

مدل: [`app/Models/MarketplaceInstallation.php`](../app/Models/MarketplaceInstallation.php) —
`isPayable()`، `isActive()`، `isExpired()`، `handler()`، `configValue()` / `settingValue()` / `putSetting()`.

### افزوده به دفترچه‌تلفن

`contacts` و `contact_groups` دو ستون گرفتند:

- `marketplace_installation_id` (nullable, `nullOnDelete`) — کدام نصب مالک این ردیف است.
- `source` (پیش‌فرض `manual`) — منبع؛ افزونه مقدار کلید هندلر خودش را می‌گذارد (`irplus`).

---

## ۳. معماری هندلر (`app/Marketplace/`)

```
app/Marketplace/
├── Contracts/
│   ├── AppHandler.php        رفتار هر افزونه
│   └── SyncsContacts.php     زیرقرارداد اختیاری برای اتصال‌های بیرونی
├── AppRegistry.php           نگاشت کلید → کلاس (singleton)
├── SyncResult.php            DTO خروجی pull()
└── Handlers/
    ├── FeatureUnlockHandler.php
    ├── IrPlusHandler.php
    └── IrPlus/{IrPlusClient, FakeIrPlusClient, HttpIrPlusClient}.php
```

### `AppHandler`

```php
interface AppHandler
{
    public function key(): string;                          // = کلید در config/marketplace.php
    public function validateConfig(array $config): array;   // پاک‌شده برمی‌گرداند یا ValidationException
    public function onActivate(MarketplaceInstallation $i): void;   // provision — idempotent
    public function onDeactivate(MarketplaceInstallation $i): void; // teardown — idempotent
    public function panelView(MarketplaceInstallation $i): ?string; // نام ویو صفحه‌ی افزونه، یا null
}
```

### `SyncsContacts` (اختیاری)

```php
interface SyncsContacts
{
    public function remoteGroups(MarketplaceInstallation $i): array;
    public function remoteContacts(MarketplaceInstallation $i, ?string $groupExternalId = null): array;
    public function pull(MarketplaceInstallation $i): SyncResult;   // upsert به contacts/contact_groups
}
```

اگر هندلر `SyncsContacts` را پیاده کند، دکمه‌ی «همگام‌سازی» در صفحه‌ی افزونه فعال می‌شود
(`MarketplaceController@sync`).

### `AppRegistry`

- singleton در [`AppServiceProvider::register()`](../app/Providers/AppServiceProvider.php).
- `for(MarketplaceApp|MarketplaceInstallation): AppHandler` — از `config('marketplace.handlers')` resolve و از کانتینر می‌سازد (پس هندلر می‌تواند وابستگی داشته باشد).
- `options()` — برای Select پنل مدیریت.

### `config/marketplace.php`

```php
return [
    'handlers' => [
        'feature_unlock' => FeatureUnlockHandler::class,
        'irplus'         => IrPlusHandler::class,
    ],
    'irplus' => [
        'driver'   => env('MARKETPLACE_IRPLUS_DRIVER', 'fake'), // fake|http
        'base_url' => env('MARKETPLACE_IRPLUS_BASE_URL', 'https://api.irplus.ir'),
        'timeout'  => 15,
    ],
];
```

---

## ۴. هندلرهای موجود

### `FeatureUnlockHandler` (`key = feature_unlock`)

قابلیت‌های داخلی. بدون `config`.

- `onActivate` → برای هر کلید در `app->capabilities` یک ردیف `UserFeatureOverride` با `mode = grant`.
- `onDeactivate` → همان ردیف‌ها را حذف می‌کند.
- `panelView` → `null` (کاربر از منوی کناری استفاده می‌کند).

> نکته: ردیف `features` متناظر هم باید در `/admin → Features` مقدار `is_active = true` داشته باشد،
> وگرنه با اینکه grant داده شده، لینک «بزودی» می‌ماند. رجوع به [starter.md](starter.md) §15.

### `IrPlusHandler` (`key = irplus`, پیاده‌ساز `AppHandler` + `SyncsContacts`)

- `validateConfig` → `api_key` (secret) + `agency_code` لازم، `base_url` اختیاری.
- `onActivate` → یک `ContactGroup` اختصاصی («ایرپلاس»، `source = irplus`, `marketplace_installation_id`) می‌سازد و `settings['group_id']` را ذخیره می‌کند.
- `pull` → گروه‌ها و مسافران را می‌کشد و به `contacts` / `contact_groups` (scope‌شده به نصب) upsert می‌کند؛
  کلید یکتا `user_id + marketplace_installation_id + mobile` — تکرار امن.
- `panelView` → `dashboard.marketplace.handlers.irplus`.

**درایورها** (`config('marketplace.irplus.driver')`):

| درایور | کلاس | کاربرد |
|---|---|---|
| `fake` | `FakeIrPlusClient` | داده‌ی نمونه‌ی ثابت — دِو/تست، بدون اعتبار (پیش‌فرض؛ مثل `SMS_PROVIDER=log`) |
| `http` | `HttpIrPlusClient` | REST واقعی با bearer token؛ اندپوینت‌های `/api/v1/groups` و `/api/v1/passengers` |

---

## ۵. افزودن یک افزونه‌ی جدید — گام‌به‌گام

فرض کنیم می‌خواهیم افزونه‌ی «سپیدار» (اتصال به نرم‌افزار حسابداری) اضافه کنیم.

### گام ۱ — کلاس هندلر

`app/Marketplace/Handlers/SepidarHandler.php`:

```php
class SepidarHandler implements AppHandler, SyncsContacts
{
    public function key(): string { return 'sepidar'; }

    public function validateConfig(array $config): array
    {
        return Validator::make($config, [
            'api_token' => ['required', 'string', 'min:8'],
            'company_id' => ['required', 'string'],
        ], [], ['api_token' => 'توکن', 'company_id' => 'شناسه شرکت'])->validate();
    }

    public function onActivate(MarketplaceInstallation $i): void { /* ساخت گروه اختصاصی */ }
    public function onDeactivate(MarketplaceInstallation $i): void { /* اختیاری */ }
    public function panelView(MarketplaceInstallation $i): ?string { return 'dashboard.marketplace.handlers.sepidar'; }

    public function remoteGroups(...): array { /* ... */ }
    public function remoteContacts(...): array { /* ... */ }
    public function pull(MarketplaceInstallation $i): SyncResult { /* upsert scoped */ }
}
```

اگر افزونه فقط یک قابلیت داخلی است، نیازی به کلاس جدید نیست — از `feature_unlock` استفاده کنید.

### گام ۲ — ثبت در `config/marketplace.php`

```php
'handlers' => [
    // ...
    'sepidar' => \App\Marketplace\Handlers\SepidarHandler::class,
],
```

و در صورت نیاز یک بلوک `'sepidar' => ['driver' => env(...), ...]` برای درایور بیرونی.
همچنین لیبل هندلر را در `AppRegistry::options()` اضافه کنید.

### گام ۳ — ویوی صفحه‌ی افزونه (اگر `panelView` غیر `null` است)

`resources/views/dashboard/marketplace/handlers/sepidar.blade.php` — به آن `$installation` و `$app` پاس داده می‌شود.
از `handlers/irplus.blade.php` الگو بگیرید (دکمه‌ی همگام‌سازی به `route('marketplace.sync', $installation)`،
فرم ارسال گروهی به `route('dashboard.contacts.send.post')` با `groups[]` از پیش انتخاب‌شده).

### گام ۴ — ردیف کاتالوگ

یا از `/admin → بازارچه → افزونه‌ها → ایجاد`، یا در
[`MarketplaceAppsSeeder`](../database/seeders/MarketplaceAppsSeeder.php) یک ردیف idempotent اضافه کنید.

### گام ۵ — تست

`tests/Feature/Marketplace/SepidarSyncTest.php` — از `IrPlusSyncTest` الگو بگیرید. درایور `fake` را در
`phpunit.xml` تنظیم کنید.

**تمام.** نیازی به مهاجرت، مسیر، یا کنترلر جدید نیست — همه‌چیز داده‌محور است.

---

## ۶. گردش خرید / فعال‌سازی

کنترلر: [`app/Http/Controllers/Dashboard/MarketplaceController.php`](../app/Http/Controllers/Dashboard/MarketplaceController.php)
(`use HandlesGatewayPayment`).

```
/dashboard/marketplace                     index   — کاتالوگ، گروه‌بندی بر اساس category
/dashboard/marketplace/app/{app:slug}      show    — جزئیات + فرم اتصال از config_schema
POST .../app/{slug}/install                install — validateConfig → ساخت نصب
                                                     رایگان → onActivate آنی
                                                     پولی → awaiting_payment + سه‌راهی online|wallet|receipt
/dashboard/marketplace/i/{installation:token}       manage  — رندر panelView() یا صفحه‌ی وضعیت
POST .../i/{token}/sync                     sync    — handler->pull()  (throttle 20/min)
POST .../i/{token}/config                   updateConfig — ویرایش اعتبار (فیلد خالی = حفظ مقدار قبلی)
POST .../i/{token}/wallet                   payFromWallet
GET  .../i/{token}/pay                      pay     — درگاه
DELETE .../i/{token}                        uninstall — onDeactivate + status=cancelled
GET|POST /marketplace/payment/callback      callback — verify + PayableSettlement  (CSRF-excepted)
```

**تسویه:** [`PayableSettlement::settleMarketplaceInstallation()`](../app/Support/PayableSettlement.php) —
`status = active`، ست‌کردن `activated_at`، `expires_at` (ماهانه/سالانه برای اشتراک)، اجرای
`handler->onActivate()`، و اطلاع‌رسانی `OperationNotifier::marketplaceAppActivated()`.
idempotent تا وقتی نصب هنوز `active` و منقضی‌نشده است — پس **تمدید = پرداخت دوباره‌ی همان نصب**
که `expires_at` را جلو می‌برد.

**فیش بانکی:** کلید `marketplace` در `BankReceiptController::PURPOSES` (تنظیم `receipt_for_marketplace`).

---

## ۷. پنل مدیریت

گروه ناوبری **«بازارچه»**:

| ریسورس | نوع | کار |
|---|---|---|
| `MarketplaceApps` | CRUD کامل | ساخت/ویرایش افزونه، قیمت‌گذاری شرطی، Repeater فیلدهای اتصال، انتخاب capabilityها، تیک `is_active` |
| `MarketplaceInstallations` | فقط List/Edit (`canCreate()=false`) | مشاهده‌ی نصب‌ها، تغییر `status` (تعلیق)، تمدید دستی `expires_at`، دیدن `config` با mask رمز |

---

## ۸. منوی داشبورد کاربر

- `FeatureCatalog::GROUPS['marketplace']` → ردیف `marketplace.browse` (system) → لینک «بازارچه افزونه‌ها».
- [`resources/views/dashboard/partials/nav-marketplace.blade.php`](../resources/views/dashboard/partials/nav-marketplace.blade.php) —
  گروه «افزونه‌های من» با نصب‌های `active` که هندلرشان `panelView()` دارد (اتصال‌های بیرونی).
- کارت تبلیغی گرادیانی `.mkt-cta` در فوتر منوی کناری، درست بالای «ورود به پنل VIP» — هدایت به بازارچه.
- افزونه‌های `feature_unlock` اینجا نمی‌آیند؛ ردیف `features` خودشان با grant روشن می‌شود.

---

## ۹. تنظیمات (`/admin → تنظیمات`)

| کلید | پیش‌فرض | کار |
|---|---|---|
| `marketplace_enabled` | `1` | روشن بودن کل بازارچه (خاموش = مسیرها 404) |
| `marketplace_payment_online` | `0` | فعال بودن پرداخت آنلاین افزونه‌ها از درگاه |
| `receipt_for_marketplace` | `1` | امکان ثبت فیش بانکی برای خرید افزونه |

---

## ۱۰. متغیرهای محیطی

```dotenv
MARKETPLACE_IRPLUS_DRIVER=fake            # fake (پیش‌فرض) | http
MARKETPLACE_IRPLUS_BASE_URL=https://api.irplus.ir
```

`phpunit.xml` مقدار `MARKETPLACE_IRPLUS_DRIVER=fake` را ست می‌کند.

---

## ۱۱. انقضای اشتراک

- دستور: `php artisan marketplace:expire` —
  [`app/Console/Commands/ExpireMarketplaceInstallations.php`](../app/Console/Commands/ExpireMarketplaceInstallations.php).
  نصب‌های `active` با `expires_at < now()` را `expired` می‌کند و `handler->onDeactivate()` را صدا می‌زند (revoke قابلیت‌ها).
- زمان‌بندی: `routes/console.php` — روزانه، `withoutOverlapping()`.
- نیازمند cron سیستمی: `* * * * * php artisan schedule:run`.

---

## ۱۲. تست‌ها

```bash
php artisan test --filter=Marketplace
```

- `tests/Feature/Marketplace/MarketplaceInstallTest.php` — کاتالوگ، گیت `marketplace_enabled`، نصب رایگان/یک‌باره/اشتراک،
  کیف پول، callback درگاه + `expires_at`، نصب تکراری، `feature_unlock` grant/revoke، مالکیت، صحت سیدر.
- `tests/Feature/Marketplace/IrPlusSyncTest.php` — اعتبارسنجی config، ساخت دفترچه‌تلفن scope‌شده، idempotency.

---

## ۱۳. فایل‌های کلیدی

| بخش | مسیر |
|---|---|
| مدل‌ها | `app/Models/MarketplaceApp.php`، `MarketplaceInstallation.php` |
| هندلر | `app/Marketplace/**` |
| کانفیگ | `config/marketplace.php` |
| کنترلر | `app/Http/Controllers/Dashboard/MarketplaceController.php` |
| تسویه | `app/Support/PayableSettlement.php` (`settleMarketplaceInstallation`) |
| اطلاع‌رسانی | `app/Support/OperationNotifier.php` (`marketplaceAppActivated`) |
| مسیرها | `routes/web.php` (بلوک «بازارچه افزونه‌ها») + `bootstrap/app.php` (CSRF) |
| Filament | `app/Filament/Resources/MarketplaceApps/**`، `MarketplaceInstallations/**` |
| سیدر | `database/seeders/MarketplaceAppsSeeder.php` + زنجیره در `DatabaseSeeder` |
| ویوها | `resources/views/dashboard/marketplace/**` |
| منو | `resources/views/dashboard/partials/nav-marketplace.blade.php`، `nav.blade.php` |
| مهاجرت‌ها | `database/migrations/2026_09_02_18000{1,2,3}_*` |
