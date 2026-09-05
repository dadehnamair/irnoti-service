# راهنمای کارت ویزیت دیجیتال (Digital Business Card)

> مکمل [starter.md](starter.md). یک صفحهٔ عمومی کوتاه (کارت ویزیت) روی یکی از
> چند دامنهٔ کوتاه (`Domain`)، با قیمت‌گذاری استاندارد/VIP و همان زیرساخت پرداخت
> مشترک ([finance.md](finance.md)).

---

## ۱. مفهوم

مشتری از داشبورد یک کارت ویزیت می‌سازد که روی یکی از دامنه‌های کوتاهِ ادمین‌ساخته
(`Domain`، مثل `11v.ir`) با یک کد اختصاصی سرو می‌شود:
`https://{domain.host}/{code}`. دو سطح قیمت‌گذاری دارد:

| Tier | چطور قیمت تعیین می‌شود |
| --- | --- |
| `standard` | قیمت ثابت از تنظیم `business_card_standard_price` |
| `vip` | کدِ دلخواه؛ قیمت از `Domain::code_price_tiers` بر اساس نوع کاراکتر (numeric/alpha/mixed) و طول کد |

---

## ۲. مدل داده

- [`BusinessCard`](../app/Models/BusinessCard.php) — `token` (route key، ۲۴نویسه‌ای
  تصادفی)، `user_id`، `domain_id`، `tier` (`standard`|`vip`)، `code`، محتوای کارت
  (`title, position, company, bio, avatar_path, cover_path, phone, mobile,
  whatsapp, telegram, instagram, website, email, address`)، `socials`/`products`
  (JSON آرایه‌ای)، `theme_color`، `status` (`draft`|`awaiting_payment`|`active`|`disabled`)،
  فیلدهای پرداخت (`price, transaction_id, reference_id, payment_driver, paid_at`)،
  `views_count`. یکتا روی `(domain_id, code)`.
- [`Domain`](../app/Models/Domain.php) — `host, label, is_active, is_default,
  code_price_tiers (JSON), sort`. متد `tierForCode($code)` اولین ردیفِ منطبق را
  بر اساس نوع/طول برمی‌گرداند (یا `null` اگر هیچ تعرفه‌ای منطبق نباشد).

---

## ۳. جریان خرید/انتشار

1. `GET /dashboard/cards/create` — انتخاب دامنه + tier؛ برای `vip` یک اندپوینت
   AJAX (`GET /dashboard/cards/quote`) قیمت زندهٔ کد را برمی‌گرداند.
2. `POST /dashboard/cards` (`store`) — قیمت را با `resolvePricing()` نهایی
   می‌کند؛ اگر قیمت صفر شد، کارت بلافاصله `active` می‌شود (+ پیامک اطلاع‌رسانی)؛
   وگرنه `awaiting_payment`.
3. تسویه از دو مسیر ممکن است برسد (هر دو نهایتاً `PayableSettlement::settle()`
   را صدا می‌زنند):
   - **کیف پول** — `POST /dashboard/cards/{card}/wallet` → `Wallet::debit()`.
   - **درگاه آنلاین** — `GET /dashboard/cards/{card}/pay` (اگر تنظیم
     `business_card_payment_online` روشن باشد) → کال‌بک مشترک
     `HandlesGatewayPayment`.
4. `GET/PUT /dashboard/cards/{card}/edit` — فقط مالک کارت (`abort_unless`)؛
   آپلود آواتار/کاور روی دیسک `public` مسیر `cards/{user_id}`.

کنترلر: [`Dashboard\BusinessCardController`](../app/Http/Controllers/Dashboard/BusinessCardController.php).

---

## ۴. صفحهٔ عمومی

[`PublicBusinessCardController::show`](../app/Http/Controllers/PublicBusinessCardController.php) —
دامنه را از هدر `Host` درخواست پیدا می‌کند (`Domain::where('host', ...)`)، کارت
فعال با آن `code` را می‌آورد (۴۰۴ در غیر این صورت)، `views_count` را زیاد می‌کند،
و `resources/views/cards/show.blade.php` را رندر می‌کند (یک صفحهٔ RTL مستقل،
خارج از لایوت اصلی). پارامتر `?vcf=1` فایل مخاطب (`.vcf`) برای دخیره روی گوشی
می‌سازد.

> مسیر این کنترلر در [`routes/web.php`](../routes/web.php) عمداً **آخرین** روت
> است (`/{code}`، الگوی catch-all) تا هیچ روت نام‌داری را نپوشاند.

---

## ۵. تنظیمات مرتبط (`SettingsSeeder`, گروه `commerce`)

| کلید | پیش‌فرض | معنی |
| --- | --- | --- |
| `business_card_payment_online` | `0` | اتصال به درگاه پرداخت برای خرید کارت |
| `business_card_standard_price` | `600000` | قیمت تومانیِ tier استاندارد |

---

## ۶. Filament

[`Resources/BusinessCards`](../app/Filament/Resources/BusinessCards) — نمایش/ویرایش
فقط (`canCreate() => false`؛ کارت فقط از داشبورد مشتری متولد می‌شود)، زیر گروه
ناوبری «فروش»؛ بج تعداد کارت‌های `awaiting_payment`.

## ۷. تست‌ها

[`tests/Feature/BusinessCardPurchaseTest.php`](../tests/Feature/BusinessCardPurchaseTest.php) —
فعال‌سازی رایگان فوری، تعیین قیمت هر دو tier، رد کد بی‌تعرفه، یکتایی کد در هر
دامنه، تسویهٔ کال‌بک درگاه، و رزولوشن صفحهٔ عمومی بر اساس هدر Host.
