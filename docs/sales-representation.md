# راهنمای نمایندگی فروش (Sales Representation)

> مکمل [starter.md](starter.md). صفحهٔ عمومی «نمایندگی فروش» — تعرفه‌های
> کمیسیونی ادمین‌ساخته + فرم ثبت درخواست همکاری، بدون پرداخت/فعال‌سازی
> خودکار؛ فقط بررسی دستی از پنل ادمین.

---

## ۱. مفهوم

بازدیدکنندهٔ سایت در `/representation` تعرفه‌های همکاری (درصد کمیسیون، سرمایهٔ
لازم، مزایا، شرایط) را می‌بیند و با پر کردن یک فرم لید (سرنخ) ثبت می‌کند. برخلاف
پلن/خط/کارت ویزیت، این جریان **هیچ پرداخت یا فعال‌سازی خودکاری ندارد** — فقط یک
درخواست ثبت می‌شود که ادمین به‌صورت دستی بررسی، وضعیتش را عوض و با متقاضی تماس
می‌گیرد.

---

## ۲. مدل داده

- [`RepresentationTier`](../app/Models/RepresentationTier.php) — `name`, `slug`
  (خودکار از `name` اگر خالی باشد)، `tagline`, `description`,
  `investment_amount` (تومان، صفر = بدون نیاز به سرمایه)، `commission_percent`,
  `benefits` (JSON آرایه‌ای، `getBenefitListAttribute()` فیلترشده)،
  `requirements`, `is_featured`, `is_active`, `sort`. رابطهٔ `applications()`.
- [`RepresentationApplication`](../app/Models/RepresentationApplication.php) —
  لید ثبت‌شده از فرم عمومی: `representation_tier_id` (اختیاری)، `full_name`,
  `mobile`, `email`, `city`, `company_name`, `message`, `status`
  (`STATUSES`: `pending`|`contacted`|`approved`|`rejected`), `admin_note`.
  رابطهٔ `tier()`.

---

## ۳. جریان

1. `GET /representation` (`RepresentationController::index`) — قابل غیرفعال‌سازی
   کامل با تنظیم `representation_enabled` (پیش‌فرض روشن؛ در صورت خاموش بودن
   ۴۰۴ برمی‌گرداند). تعرفه‌های فعال را می‌خواند (`RepresentationTier::active()
   ->ordered()`), JSON-LD (`BreadcrumbList` + `ItemList` از تعرفه‌ها) می‌سازد.
2. `POST /representation` (`RepresentationController::apply`, throttle `6,1`) —
   اعتبارسنجی فارسی، `RepresentationApplication::create()`, سپس
   `OperationNotifier::representationApplicationReceived()` (پیامک به
   `admin_mobile`، مشروط به تنظیم `sms_notifications_enabled`).
3. ادمین از `/admin/representation-applications` وضعیت را عوض می‌کند
   (`pending → contacted/approved/rejected`) و یادداشت داخلی می‌نویسد؛ هیچ اثر
   جانبی خودکاری (فعال‌سازی حساب، تسویه مالی و…) ندارد — کاملاً دستی.

---

## ۴. تنظیمات (`settings` جدول، گروه `commerce`)

| کلید | نوع | توضیح |
| --- | --- | --- |
| `representation_enabled` | `bool` | نمایش/عدم نمایش کامل صفحهٔ `/representation` |

---

## ۵. Filament

- [`RepresentationTierResource`](../app/Filament/Resources/RepresentationTiers/RepresentationTierResource.php)
  — CRUD کامل تعرفه‌ها (گروه ناوبری «فروش»)؛ `benefits` با `TagsInput`.
- [`RepresentationApplicationResource`](../app/Filament/Resources/RepresentationApplications/RepresentationApplicationResource.php)
  — فقط ویرایش/بررسی (`canCreate() === false` — لیدها فقط از فرم عمومی متولد
  می‌شوند)؛ فیلدهای متقاضی غیرفعال (read-only)، فقط `status`/`admin_note` قابل
  تغییر. بج ناوبری تعداد درخواست‌های `pending`.

---

## ۶. تست‌ها

جریان کامل (نمایش صفحه + JSON-LD + ثبت درخواست) در
[`tests/Feature/TempSitePagesSmokeTest.php`](../tests/Feature/TempSitePagesSmokeTest.php)
پوشش داده شده (به‌همراه FAQ/Features/Contact/Pages که زیرسیستم‌های مجاور همین
دسته «محتوای عمومی سایت» هستند).
