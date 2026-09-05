# راهنمای «قابلیت‌های پنل» (Panel Features Access)

> مکمل [starter.md](starter.md) §15. کل منوی کنارهٔ داشبورد مشتری از **یک کاتالوگ
> قابلیت** ساخته می‌شود، نه از ویوهای هاردکد — این سند مکانیزم گرنت/ریوک و نمایش
> «بزودی» را توضیح می‌دهد. هر زیرسیستم جدیدی که یک صفحهٔ داشبورد اضافه می‌کند
> (مثل [messenger.md](messenger.md)، [business-cards.md](business-cards.md))
> باید کلید خودش را اینجا اضافه کند، نه این‌که مستقیم توی ویو لینک بزند.

---

## ۱. مفهوم

- **`FeatureCatalog`** ([`app/Support/FeatureCatalog.php`](../app/Support/FeatureCatalog.php))
  یک آرایهٔ ثابت PHP است: کل ساختار منو (گروه‌ها → آیتم‌ها → مسیر/URL → آیا
  «سیستمی» است). این تک منبع حقیقت است.
- **`FeaturesSeeder`** هر بار (هر دیپلوی/سید) فیلدهای ساختاری را از `FeatureCatalog`
  با جدول `features` هم‌گام می‌کند، اما دو ستون ادمین‌ساخته (`label`, `is_active`)
  را برای آیتم‌های غیرسیستمی دست‌نخورده نگه می‌دارد و کلیدهای منسوخ را حذف می‌کند.
- ادمین قابلیت‌ها را در **`UserGroup`** (گروه کاربری) بسته‌بندی می‌کند
  (چندبه‌چند با `Feature` روی پیوت `feature_user_group`)، یک گروه به هر `User`
  می‌دهد (`users.user_group_id`)، و می‌تواند استثنای per-user هم بگذارد
  (`UserFeatureOverride`، `mode`: `grant`/`revoke`).

---

## ۲. مدل داده

| مدل | جدول | نکته |
| --- | --- | --- |
| [`Feature`](../app/Models/Feature.php) | `features` | `key, group_key, group_label, label, icon, route, url, description, sort, is_active, is_system` |
| [`UserGroup`](../app/Models/UserGroup.php) | `user_groups` | `name, slug (auto), is_default (فقط یکی)`, `sort`؛ `defaultId()` استاتیک |
| [`UserFeatureOverride`](../app/Models/UserFeatureOverride.php) | `user_feature_overrides` | `user_id, feature_id, mode` (`grant`\|`revoke`)، یکتا `(user_id, feature_id)` |

پیوت: `feature_user_group` (یکتا `[feature_id, user_group_id]`).
`users.user_group_id` (nullable FK، `nullOnDelete`) — کاربر تازه به‌طور خودکار
`UserGroup::defaultId()` می‌گیرد (مگر ادمین باشد).

---

## ۳. منطق گرنت/ریوک — `User`

```php
$user->grantedFeatureKeys(): array   // کلیدهای گروهِ کاربر ∪ (override با mode=grant) − (override با mode=revoke)
$user->canUseFeature(string $key): bool
    // false اگر Feature نباشد یا is_active=false باشد
    // true اگر is_system باشد، یا کلید در grantedFeatureKeys() باشد
```

هر کنترلری که یک قابلیتِ غیرسیستمی را می‌پوشاند باید صریحاً چک کند — **هیچ
میدل‌ور/گیت عمومی این کار را خودکار انجام نمی‌دهد**:

```php
abort_unless($request->user()->canUseFeature('messengers.send'), 403);
```

نمونهٔ واقعی: [`MessengerController`](../app/Http/Controllers/Dashboard/MessengerController.php)
همین الگو را برای `messengers.send` + `messengers.{channel}` به کار می‌برد
(به [messenger.md](messenger.md) نگاه کنید).

> این جدا است از میدل‌ور `approved` (`app/Http/Middleware/EnsureAccountApproved.php`)
> که کل بخش‌های SMS/خطوط را تا زمانِ تأیید حساب توسط ادمین می‌بندد — آن یک گیت
> درشت‌دانهٔ وضعیت حساب است، نه بخشی از کاتالوگ قابلیت.

---

## ۴. رندر سایدبار

[`resources/views/dashboard/partials/nav-features.blade.php`](../resources/views/dashboard/partials/nav-features.blade.php):

1. همهٔ `Feature`ها (`ordered()`) را می‌خواند، `grantedFeatureKeys()` کاربر را
   حساب می‌کند.
2. ردیف‌هایی که نه سیستمی‌اند و نه گرنت شده‌اند، **کلاً حذف** می‌شوند (حتی به‌شکل
   غیرفعال هم دیده نمی‌شوند).
3. از باقی‌مانده، اگر `is_active` باشد و مقصد قابل‌حل داشته باشد (`url` یا
   `route` معتبر) → لینک واقعی؛ در غیر این صورت → `<span>` غیرفعال با متن
   «بزودی».

یعنی سه حالت داریم: **مخفی کامل** (دسترسی ندارد) / **بزودی** (دسترسی دارد ولی
هنوز فعال/متصل نیست) / **لینک زنده**.

---

## ۵. افزودن قابلیت جدید (چک‌لیست)

1. کلید جدید را زیر گروه مناسب در `FeatureCatalog::GROUPS` اضافه کن (اگر
   همیشه در دسترس است `system: true`، وگرنه بدون آن).
2. `php artisan db:seed --class=FeaturesSeeder` (یا سید کامل) تا ردیف در `features`
   ساخته شود.
3. اگر غیرسیستمی است، در کنترلر مربوطه `abort_unless($user->canUseFeature(...))`
   بگذار.
4. مستند مربوط به آن زیرسیستم (این پوشهٔ `docs/`) را به‌روزرسانی کن.

---

## ۶. Filament (مدیریت ادمین)

- [`Resources/Features`](../app/Filament/Resources/Features) — نام نمایشی «امکانات
  پنل»؛ بدون create (`canCreate() => false`، ردیف‌ها از سیدر می‌آیند). فیلدهای
  ساختاری (`key`, `route`, `is_system`, …) غیرفعال/غیرقابل‌ذخیره‌اند؛ فقط
  `label`, `is_active`, `description`, و چک‌لیست `userGroups` قابل‌ویرایش‌اند.
- [`Resources/UserGroups`](../app/Filament/Resources/UserGroups) — نام نمایشی
  «گروه‌های کاربری»؛ فرم شامل `name` (auto-slug)، `is_default`، `sort`، و
  چک‌لیست جستجوپذیر `features` (برچسب `group_label › label`).
- استثناهای per-user مستقیم روی فرم `UserResource` مدیریت می‌شوند — بخش
  «گروه کاربری و امکانات پنل»: `Select` برای `user_group_id` + `Repeater`
  برای `featureOverrides` (انتخاب `feature_id` + `mode`).

---

## ۷. تست‌ها

[`tests/Feature/Dashboard/PanelFeatureAccessTest.php`](../tests/Feature/Dashboard/PanelFeatureAccessTest.php)
منطق کاتالوگ/گرنت + نمایش سایدبار را پوشش می‌دهد؛
[`tests/Feature/Dashboard/PanelGateTest.php`](../tests/Feature/Dashboard/PanelGateTest.php)
میدل‌ور `approved` را.
