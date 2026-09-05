# صفحه‌های اختصاصی خطوط (Line Landing Pages)

هر پیش‌شمارهٔ خطوط اختصاصی (خطوط ۱۰۰۰، ۲۰۰۰، ۳۰۰۰، ۰۲۱ و…) علاوه بر حضور در
کاتالوگ `/lines`، یک **صفحهٔ فرود مستقل** در `/lines/{slug}` دارد که کاملاً از
پنل ادمین ویرایش می‌شود. هدف، سئوی صفحه‌های پرجست‌وجو مثل «خرید خط ۳۰۰۰» و
داشتن جایی برای فروش خط با توضیحات، ویژگی‌ها، سؤالات متداول و **باندل‌های آماده**
است. کاتالوگ قدیمی دست‌نخورده باقی می‌ماند و فقط به این صفحه‌ها لینک می‌دهد.

## مفهوم

- **`LineGroup`** = یک صفحهٔ فرود برای یک پیش‌شماره. همهٔ ردیف‌های `SmsLine` که
  `prefix` یکسان دارند زیر همین صفحه جمع می‌شوند (`sms_lines.line_group_id`).
- **`LineBundle`** = «باندل اختصاصی خط»: خط + اعتبار پیامک + مدت اعتبار، با یک
  قیمت مقطوع. روی صفحهٔ فرود همان پیش‌شماره فروخته می‌شود و از طریق همان جریان
  `LineOrder` خریداری می‌شود (ستون‌های باندل روی `line_orders` snapshot می‌شوند).

## مدل داده

| جدول | توضیح |
|---|---|
| `line_groups` | [migration](../database/migrations/2026_09_05_170001_create_line_groups_table.php) — `slug` (کلید مسیر)، `prefix` (یکتا، کلید اتصال به `sms_lines`)، `title`/`tagline`/`body` (Markdown)، `features`/`use_cases`/`faqs` (JSON)، `seo_title`/`seo_description`/`og_image`، `sort`، `is_active`. |
| `line_bundles` | [migration](../database/migrations/2026_09_05_170002_create_line_bundles_table.php) — `line_group_id`، `sms_line_id` (اختیاری، گونهٔ مشخص)، `slug`، `title`، `description`، `sms_credit`، `validity_days`، `price`/`compare_at_price`، `badge_label`/`badge_style`، `features` (JSON)، `sort`، `is_active`. |
| `sms_lines.line_group_id` | [migration](../database/migrations/2026_09_05_170003_add_line_group_id_to_sms_lines_table.php) — FK nullable + backfill: برای هر `prefix` یک `LineGroup` ساخته و ردیف‌ها به آن وصل می‌شوند. `prefix` همچنان کلید نمایش/fallback است. |
| `line_orders` (+۴ ستون) | [migration](../database/migrations/2026_09_05_170004_add_bundle_fields_to_line_orders_table.php) — `line_bundle_id`، `bundle_label`، `sms_credit`، `validity_days` (همه snapshot). |

مدل‌ها: [`LineGroup`](../app/Models/LineGroup.php) (کلید مسیر `slug`؛ `slug` از
`prefix` در هوک `saving`؛ `scopeActive`/`scopeOrdered`؛ `rendered_body` مارک‌داون؛
`feature_list`/`use_case_list`/`faq_list`)، [`LineBundle`](../app/Models/LineBundle.php)
(کلید مسیر `slug`؛ `group()`/`smsLine()`)،
[`SmsLine`](../app/Models/SmsLine.php) (هوک `saving`: اگر `line_group_id` خالی بود
از روی `prefix` وصل می‌شود؛ رابطهٔ `lineGroup()`)،
[`LineOrder`](../app/Models/LineOrder.php) (`bundle()`، `isBundle()`).

## جریان

مسیرها در [`routes/web.php`](../routes/web.php) بعد از بلوک ثابت `/lines/*`:

- `GET /lines/{group}` → [`LineController@group`](../app/Http/Controllers/LineController.php) —
  `line-group` را رندر می‌کند: هیرو، توضیحات، ویژگی‌ها/کاربردها، **گونه‌های خرید**
  (کارت‌های `SmsLine` با CTA به `lines.checkout`)، **باندل‌ها** (CTA به
  `lines.bundle.checkout`)، سؤالات متداول، و JSON-LD شامل `BreadcrumbList` +
  یک `Product`/`Offer` برای هر خط + `FAQPage`.
- `GET /lines/{group}/bundle/{bundle}` → `bundleCheckout` — همان فرم تماس
  `line-checkout` با `line_bundle_id` مخفی.
- `POST /lines/order` → `order` — حالا **یا** `sms_line_id` **یا**
  `line_bundle_id` را می‌پذیرد. برای باندل، `bundle_label`/`price`/`sms_credit`/
  `validity_days`/`line_label` روی سفارش snapshot می‌شود؛ وضعیت `awaiting_payment`
  (یا `pending` اگر خط `requires_inquiry` باشد).
- پرداخت و کال‌بک همان `lines.pay` / `lines.payment.callback` است. کال‌بک حالا از
  [`PayableSettlement`](../app/Support/PayableSettlement.php) عبور می‌کند (یک مسیر
  idempotent). در `settleLineOrder`، اگر سفارش یک باندل باشد و کاربر لاگین باشد،
  `sms_credit` باندل به حساب کاربر اضافه می‌شود (مثل `settlePackageOrder`).
  مهمان‌ها را ادمین در گردش‌کار وضعیت شارژ می‌کند.

`/sitemap.xml` برای هر `LineGroup` فعال یک URL با `priority=0.7` اضافه می‌کند.
صفحهٔ `/lines` وقتی `LineGroup` متناظر وجود داشته باشد به هر پیش‌شماره لینک می‌دهد
(`$landings` از کنترلر).

## تنظیمات

قابلیت جدیدی در `settings` اضافه نشده؛ همان `line_payment_online` گیت پرداخت
آنلاین است (خط یا باندل با `price > 0` و بدون `requires_inquiry`).

## پنل ادمین (Filament)

- [`LineGroupResource`](../app/Filament/Resources/LineGroups/LineGroupResource.php) —
  گروه ناوبری «محتوای سایت». فرم: هویت (slug/prefix/title/tagline)، محتوا
  (`body` مارک‌داون + `TagsInput` برای `features`/`use_cases`)، سؤالات متداول
  (`Repeater` با `q`/`a`)، سئو، وضعیت. جدول شمارش گونه‌ها/باندل‌ها.
- [`LineBundleResource`](../app/Filament/Resources/LineBundles/LineBundleResource.php) —
  فرم: صفحهٔ خط + گونهٔ خط (اختیاری) + عنوان/نامک + `sms_credit`/`validity_days` +
  قیمت‌گذاری + badge + وضعیت.
- [`SmsLineForm`](../app/Filament/Resources/SmsLines/Schemas/SmsLineForm.php) — یک
  `Select` برای `line_group_id` (اگر خالی بماند، هوک مدل از روی `prefix` وصل می‌کند).

## سیدرها

[`LineGroupsSeeder`](../database/seeders/LineGroupsSeeder.php) (قبل از
`SmsLinesSeeder`) و [`LineBundlesSeeder`](../database/seeders/LineBundlesSeeder.php)
(بعد از آن) — idempotent، در [`DatabaseSeeder`](../database/seeders/DatabaseSeeder.php)
زنجیر شده‌اند.

## تست

[`tests/Feature/LineGroupPageTest.php`](../tests/Feature/LineGroupPageTest.php):
رندر صفحهٔ فرود با خطوط/باندل‌ها + JSON-LD، ۴۰۴ برای گروه غیرفعال، رندر چک‌اوت
باندل، snapshot شدن باندل روی `LineOrder`، شارژ idempotent پیامک هنگام settle،
حضور گروه در `sitemap.xml`.
