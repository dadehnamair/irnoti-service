# راهنمای محتوای عمومی سایت (Site Content)

> مکمل [starter.md](starter.md). چهار صفحهٔ عمومی کوچک و ادمین‌مدیریت که مکمل
> صفحات بزرگ‌تر سایت (لندینگ، تعرفه‌ها، خطوط، بازارچه، بلاگ، مستندات) هستند:
> صفحات ثابت (درباره ما/همکاری با ما)، سوالات متداول، ویترین کامل امکانات، و
> فرم تماس با ما. زیرسیستم مجاور «نمایندگی فروش» جدا مستند شده:
> [sales-representation.md](sales-representation.md).

---

## ۱. مفهوم

پیش از این، متن «درباره ما»، سوالات متداول و لیست امکانات مستقیم داخل
`landing.blade.php` هاردکد بودند و هیچ صفحهٔ تماس یا صفحات ثابت جداگانه‌ای وجود
نداشت. این چهار جدول جایگزین آن می‌شوند تا **همه از پنل ادمین ویرایش‌پذیر** و
هرکدام دارای URL/JSON-LD مستقل برای سئو باشند:

| صفحه | Route | مدل |
| --- | --- | --- |
| درباره ما | `/about` | `Page` (slug=`about`) |
| همکاری با ما | `/cooperation` | `Page` (slug=`cooperation`) |
| سوالات متداول | `/faq` | `Faq` |
| امکانات | `/features` | `SiteFeature` |
| تماس با ما | `/contact` (GET+POST) | `ContactMessage` |

لندینگ (`HomeController::index`) از همین جداول (`Faq`, `SiteFeature`) برای
بخش‌های `#faq` و `#features` استفاده می‌کند — یک منبع داده، دو نمایش (تیزر در
لندینگ + صفحهٔ کامل مستقل).

---

## ۲. مدل داده

- [`Page`](../app/Models/Page.php) — صفحهٔ ثابت عمومی: `slug` (route key,
  خودکار از `title` اگر خالی)، `title`, `excerpt`, `body` (Markdown،
  `getRenderedBodyAttribute()`), `seo_title`, `seo_description`, `sort`,
  `is_published`. `scopePublished()`.
- [`Faq`](../app/Models/Faq.php) — `question`, `answer`, `sort`, `is_active`.
  جایگزین ثابتِ قدیمیِ `HomeController::FAQS`.
- [`SiteFeature`](../app/Models/SiteFeature.php) — کارت امکانات بازاریابی:
  `icon` (اموجی)، `title`, `tagline`, `description`, `category`
  (`SiteFeature::CATEGORIES`)، `badge` (مثلاً «جدید»)، `href` (لینک اطلاعات
  بیشتر)، `is_featured` (نمایش در اسپاتلایت `/features`)، `is_active`, `sort`.
  **جدا از `Feature`/`FeatureCatalog`** (کاتالوگ دسترسی سایدبار داشبورد،
  [panel-features.md](panel-features.md)) — این یکی صرفاً محتوای بازاریابی
  است، نه گیت دسترسی.
- [`ContactMessage`](../app/Models/ContactMessage.php) — پیام فرم تماس: `name`,
  `mobile`, `email`, `subject`, `message`, `status`
  (`STATUSES`: `new`|`read`|`replied`), `admin_note`. فقط از سایت عمومی متولد
  می‌شود، هرگز از پنل ادمین (`canCreate() === false`).

---

## ۳. جریان

- **صفحات ثابت**: `PageController::show(slug)` رکورد منتشرشده را با
  `firstOrFail()` می‌خواند و `page.blade.php` را با JSON-LD (`BreadcrumbList` +
  `WebPage`) رندر می‌کند. Route با `->defaults('slug', 'about'|'cooperation')`
  ثابت شده تا URL تمیز بماند (بدون `{slug}` در مسیر).
- **FAQ**: `FaqController::index()` سوالات فعال را می‌خواند و JSON-LD
  `FAQPage` می‌سازد؛ همان کوئری در `HomeController::index()` برای تیزر لندینگ
  استفاده می‌شود.
- **امکانات**: `FeaturesController::index()` امکانات فعال را می‌خواند، بر اساس
  `category` گروه‌بندی می‌کند و یک آیتم `is_featured` را به‌عنوان اسپاتلایت
  نشان می‌دهد؛ فیلتر دسته‌بندی در `resources/js/irnoti.js` (همان اسکریپت
  فیلتر بازارچه، به‌خاطر ساختار مشترک `.mk-grid`/`.mk-filter`).
- **تماس با ما**: `ContactController::index()` فرم را نشان می‌دهد؛
  `ContactController::store()` (throttle `6,1`) پس از اعتبارسنجی فارسی،
  `ContactMessage::create()` می‌کند و `OperationNotifier::contactMessageReceived()`
  را صدا می‌زند (فقط پیامک به ادمین — بازدیدکننده پیامک دریافت نمی‌کند).

همهٔ کوئری‌های DB و ساخت JSON-LD داخل کنترلرها هستند، نه Blade — همان قرارداد
سراسری سایت عمومی (`HomeController`, `PricingController`, `LineController`, …).

---

## ۴. Filament

گروه ناوبری «محتوای سایت»:

- [`PageResource`](../app/Filament/Resources/Pages/PageResource.php) — CRUD
  کامل؛ `MarkdownEditor` برای `body`، `slug` خودکار از `title`.
- [`FaqResource`](../app/Filament/Resources/Faqs/FaqResource.php) — CRUD کامل،
  قابل مرتب‌سازی با `sort` (`reorderable`).
- [`SiteFeatureResource`](../app/Filament/Resources/SiteFeatures/SiteFeatureResource.php)
  — CRUD کامل، فیلتر بر اساس `category`.

گروه ناوبری «فروش»:

- [`ContactMessageResource`](../app/Filament/Resources/ContactMessages/ContactMessageResource.php)
  — فقط ویرایش/بررسی (`canCreate() === false`)؛ فیلدهای فرستنده read-only، فقط
  `status`/`admin_note` قابل تغییر. بج ناوبری تعداد پیام‌های `new`.

---

## ۵. سئو

هر پنج صفحه (`about`, `cooperation`, `/faq`, `/features`, `/contact`) و
`/representation` در `routes/web.php` داخل `/sitemap.xml` لیست می‌شوند و از
`config('theme.footer.columns')` در فوتر لینک می‌خورند (به‌جای لینک‌های داخلی
قدیمی `/#about`, `/#faq` که فقط روی لندینگ کار می‌کردند).

---

## ۶. تست‌ها

[`tests/Feature/SitePagesTest.php`](../tests/Feature/SitePagesTest.php) —
رندر هر صفحه + JSON-LD + ثبت فرم تماس + صفحات ثابت منتشرشده/منتشرنشده +
پوشش در sitemap. صفحات Filament در
[`tests/Feature/AdminSiteContentResourcesTest.php`](../tests/Feature/AdminSiteContentResourcesTest.php).
