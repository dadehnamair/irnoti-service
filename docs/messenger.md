# راهنمای «پیام‌رسان‌ها» (Messenger Service)

> مکمل [starter.md](starter.md) §91 و [panel-features.md](panel-features.md).
> این زیرسیستم موازیِ لایهٔ SMS است — ارسال انبوه به بله/ایتا/واتساپ به‌جای مخابرات،
> با قیمت‌گذاری کیف‌پولی و استرداد بخش ناموفق.

---

## ۱. لایهٔ درایور — `app/Services/Messenger/`

| فایل | نقش |
| --- | --- |
| [`MessengerChannelInterface`](../app/Services/Messenger/MessengerChannelInterface.php) | قرارداد: `key()`, `label()`, `supportsBulk()`, `sendBulk(recipients, body, options): ChannelSendResult` — خطای per-recipient نباید throw شود، فقط در نتیجه ثبت شود؛ فقط خطای کل کانال (auth/شبکه) throw می‌شود. |
| [`ChannelSendResult`](../app/Services/Messenger/ChannelSendResult.php) | DTO: آرایهٔ `{to, ok, ref, error}` + `batchId` اختیاری؛ `successCount()`/`failedCount()`. |
| [`MessengerManager`](../app/Services/Messenger/MessengerManager.php) | فاساد singleton (بایند در `AppServiceProvider::bindMessengerManager()`). `enabled()`, `channelEnabled($key)`, `channel($key)`, `tariffFor($key)`, `classify($recipient)`. |
| `Channels/AbstractHttpChannel` | پایهٔ مشترک درایورهای واقعی HTTP؛ حلقهٔ `sendBulk` که خطای هر گیرنده را جدا می‌گیرد. |
| `Channels/BaleChannel` / `EitaaChannel` / `WhatsAppChannel` | درایورهای واقعی هر شبکه. |
| `Channels/LogChannel` | درایور توسعه بدون اعتبار (`MESSENGER_DRIVER=log`، پیش‌فرض) — فقط لاگ می‌کند، همیشه موفق. |
| `Channels/NullChannel` | فقط تست (`MESSENGER_DRIVER=null`) — همه چیز failed برمی‌گردد؛ برای تست مسیر استرداد. |

### قانون fallback درایور (مهم)

```php
match ($this->driver) {
    'null' => new NullChannel(...),
    'http' => $this->httpChannel(...),   // فقط مقدار صریح 'http' درایورهای واقعی را می‌آورد
    default => new LogChannel(...),      // هر چیز دیگر/خالی/نامعتبر → امن به log
};
```

و در `AppServiceProvider::bindMessengerManager()`:
`config('messenger.driver') ?: 'log'` (نه `config(..., 'log')`) — چون مقدار env
خالیِ صریح باید هم به `log` بیفتد، نه این‌که تلاش کند به یک درایور واقعیِ ناقص وصل
شود. همین الگو دقیقاً کپیِ `AppServiceProvider::bindSmsProvider()` است.

---

## ۲. مدل داده

| مدل | جدول | نکته |
| --- | --- | --- |
| [`MessengerCampaign`](../app/Models/MessengerCampaign.php) | `messenger_campaigns` | `channel, body, recipients_count/success_count/failed_count, status (queued|sending|sent|partial|failed), batch_id, cost, refunded, scheduled_at, sent_at` |
| [`MessengerRecipient`](../app/Models/MessengerRecipient.php) | `messenger_recipients` | `to, type (mobile|chat), status (queued|sent|failed), provider_ref, error`؛ یکتا `(messenger_campaign_id, to)` |

---

## ۳. جریان ارسال

1. **`GET /dashboard/messenger`** → `MessengerController::index` — لیست کانال‌های
   مجاز + کمپین‌های اخیر.
2. **`GET /dashboard/messenger/{channel}`** → `create` — فرم انتخاب گروه/چسباندن شماره.
3. **`POST /dashboard/messenger/send`** (`throttle:10,1`) → `send()`:
   - گیرنده‌ها را از عضو گروه‌های انتخابی + شماره‌های دستی ادغام، نرمال، یکتا،
     و تا سقف `BULK_CAP = 5000` می‌کند.
   - `cost = count(گیرنده) × tariffFor($channel)` (تعرفه از تنظیم
     `messenger_<key>_tariff`).
   - `wallet->hasSufficient($cost)` را چک می‌کند.
   - داخل یک `DB::transaction`: `MessengerCampaign` (status=`queued`) +
     یک `MessengerRecipient` به‌ازای هر گیرنده می‌سازد، و کیف پول را با کلید
     ایدمپوتنت `messenger:{id}:charge` بدهکار می‌کند.
   - [`SendMessengerCampaignJob`](../app/Jobs/SendMessengerCampaignJob.php) را
     دیسپچ می‌کند (با `->delay()` اگر زمان‌بندی‌شده باشد).

4. **جاب** کانال را از `MessengerManager` می‌گیرد، `sendBulk()` را صدا می‌زند،
   وضعیت هر گیرنده را می‌نویسد، و `finalize()` را صدا می‌زند:
   - وضعیت کمپین = `sent` (صفر ناموفق) / `failed` (صفر موفق) / `partial` (ترکیبی).
   - **استرداد**: `refundDue = min(failed_count × tariff, campaign.cost)`؛ اگر
     `> 0` و `campaign.refunded === 0`، کیف پول با کلید ایدمپوتنت
     `messenger:{id}:refund` بستانکار می‌شود. این گارد یعنی retry جاب هرگز
     دوبار استرداد نمی‌زند.

---

## ۴. دروازهٔ دسترسی — نه میدل‌ور، همان کاتالوگ قابلیت

طبق [panel-features.md](panel-features.md)، هیچ گیت اختصاصی برای پیام‌رسان‌ها
نیست؛ `FeatureCatalog` گروه `messengers` را این‌طور تعریف می‌کند: `messengers.send`
(باز کردن کل بخش، `route: dashboard.messenger`) + یک کلید جدا برای هر شبکه
(`messengers.bale`, `messengers.eitaa`, `messengers.whatsapp`) — **هیچ‌کدام
`system` نیستند**. یعنی ادمین باید هم قابلیت را کلاً فعال کند (Filament →
«قابلیت‌ها») و هم به گروه/کاربر گرنت بدهد.

`MessengerController` در `index()`, `create()`, `send()`:

```php
abort_unless($user->canUseFeature('messengers.send'), 403);
abort_unless($user->canUseFeature("messengers.{$channel}"), 403);
```

جدا از این، `messenger_<key>_enabled` یک کلیدسوییچ سراسری در تنظیمات است
(چک‌شده در `MessengerManager::channelEnabled()`) که اگر خاموش باشد **404**
می‌دهد (نه 403) — یعنی «این شبکه اصلاً وجود ندارد» در برابر «شما دسترسی ندارید».

---

## ۵. تنظیمات (`SettingsSeeder`, گروه `messenger`)

| کلید | پیش‌فرض | معنی |
| --- | --- | --- |
| `messenger_enabled` | `1` | کلید کلی همهٔ پیام‌رسان‌ها |
| `messenger_bale_enabled` / `_eitaa_enabled` / `_whatsapp_enabled` | `1`/`1`/`0` | کلید هر شبکه |
| `messenger_bale_tariff` / `_eitaa_tariff` / `_whatsapp_tariff` | `0` | تعرفهٔ هر گیرنده (تومان) |

---

## ۶. Filament

[`Resources/MessengerCampaigns`](../app/Filament/Resources/MessengerCampaigns) —
فقط‌نمایش (`canCreate() => false`، کمپین فقط از داشبورد مشتری متولد می‌شود)،
زیر گروه ناوبری «پیام‌رسان‌ها»؛ فیلتر بر اساس کانال و وضعیت.

## ۷. تست‌ها

[`tests/Feature/Dashboard/MessengerCampaignTest.php`](../tests/Feature/Dashboard/MessengerCampaignTest.php)
— گیت بخش/کانال، کلیدسوییچ سراسری، بدهی/استرداد کیف پول، و مسیر شادِ ارسال را
پوشش می‌دهد. کمک‌تابع `grantMessenger()` نمونهٔ درست گرنت‌کردن قابلیت در تست است.
