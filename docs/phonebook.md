# راهنمای دفترچه تلفن (Phonebook)

> مکمل [starter.md](starter.md). مخاطبین و گروه‌ها با پنل پیامکی خودِ هر مشتری
> **دوطرفه** همگام می‌شوند — این سند مدل داده و لایهٔ همگام‌سازی را توضیح می‌دهد.

---

## ۱. مفهوم

هر مشتری می‌تواند مخاطب/گروه را از داخل داشبورد بسازد، **یا** آن‌ها را از پنل SMS
شخصی خودش (که با `sms_username`/`sms_password` خودش وصل است) وارد کند. رکورد محلی و
رکورد روی سرویس پیامک با یک `remote_id` به‌هم مرتبط می‌مانند و یک ستون وضعیت
(`sync_status`: `local`/`synced`/`error`) نشان می‌دهد رکورد کجاست.

اگر مشتری اصلاً پنل پیامک متصل نداشته باشد (`hasSmsPanel()` نادرست)، همه‌چیز محلی
(`local`) می‌ماند — هیچ تماس بیرونی زده نمی‌شود. هر عملیات همگام‌سازی
**best-effort** است: خطا لاگ و روی خودِ ردیف (`sync_status=error`, `sync_error`)
ثبت می‌شود، اما هرگز ذخیرهٔ محلی را بلاک نمی‌کند.

---

## ۲. مدل داده

| مدل | جدول | ستون‌های کلیدی |
| --- | --- | --- |
| [`Contact`](../app/Models/Contact.php) | `contacts` | `remote_id`, `sync_status`/`sync_error`/`synced_at`, یکتا `(user_id, remote_id)` |
| [`ContactGroup`](../app/Models/ContactGroup.php) | `contact_groups` | `remote_id`, `contact_count`, `contacts_synced_at`, همان ستون‌های وضعیت |

رابطهٔ چندبه‌چند از پیوت `contact_contact_group` (یکتا `contact_id`+`contact_group_id`).
هر دو مدل ستون `marketplace_installation_id` + `source` هم دارند — وقتی یک افزونهٔ
بازارچه (مثلاً ایرپلاس) مالک ردیف است، `source` کلید هندلر همان افزونه را می‌گیرد
(به [marketplace.md](marketplace.md) نگاه کنید).

---

## ۳. لایهٔ همگام‌سازی

- [`App\Support\PhonebookSync`](../app/Support/PhonebookSync.php) — منطق
  `pushGroup()`, `pushContact()`, `deleteContactRemote()`, `importGroups()`,
  `importGroupContacts()`؛ همه fail-soft.
- [`App\Services\Sms\Phonebook\PhonebookClientInterface`](../app/Services/Sms/Phonebook/PhonebookClientInterface.php) —
  قرارداد: `groups()`, `contacts()`, `createGroup()`, `createContact()`,
  `updateContact()`, `deactivateContact()`, `checkMobile()`, `sendToGroups()`.
- [`UserPhonebook::for($user)`](../app/Services/Sms/Phonebook/UserPhonebook.php) —
  کارخانهٔ ساخت کلاینت به‌ازای هر مشتری؛ روی اعتبار خودِ مشتری
  `PasargadPhonebookClient` می‌سازد (همان کدنام داخلی «pasargad» که در
  [sms-provider abstraction](../CLAUDE.md) استفاده می‌شود)، یا اگر پنلی وصل نیست
  `SmsPanelNotConfiguredException` می‌اندازد.
- `PasargadPhonebookClient` با وب‌سرویس واقعی حرف می‌زند: `Contacts.asmx`
  (POST برای CRUD) و `newbulks.asmx/SendSmsToContact` (GET برای ارسال گروهی).
  `LogPhonebookClient`/`NullPhonebookClient` معادل توسعه/تست هستند.

**محدودیت‌های شناخته‌شدهٔ سرویس بیرونی:** گروهی که یک‌بار همگام شد، از راه دور
قابل تغییرنام/حذف نیست؛ عضویت مخاطب در گروه فقط لحظهٔ ساخت قابل تنظیم است (نه بعداً).

### جهت Push (محلی → پنل)

از داخل `ContactController`/`ContactGroupController`، بلافاصله بعد از ذخیرهٔ محلی،
به‌صورت همزمان صدا زده می‌شود.

### جهت Pull (پنل → محلی)

- `importGroups()` — همزمان، یک فراخوانی، از `/dashboard/contacts/import`.
- `importGroupContacts()` — صفحه‌بندی‌شده (تا ۱۰۰ در هر صفحه)، داخل جاب صف‌شدهٔ
  [`ImportGroupContactsJob`](../app/Jobs/ImportGroupContactsJob.php)، با قفل
  کش به‌ازای هر گروه (`lockKey()`) تا هم‌پوشانی رخ ندهد؛ از
  `ContactGroupController::pullContacts()` دیسپچ می‌شود.

---

## ۴. ارسال گروهی پیامک

[`Dashboard\SmsController::bulk()`/`sendBulk()`](../app/Http/Controllers/Dashboard/SmsController.php)
در `/dashboard/contacts/send` دو حالت دارد:

- **`local`** — گروه + شماره‌های دستی‌چسبانده‌شده را یکتاسازی می‌کند (سقف ۲۰۰ گیرنده)،
  یک `SmsMessage` + یک `SendUserSmsJob` به‌ازای هر گیرنده می‌سازد.
- **`remote`** — تا ۵ گروهِ کاملاً همگام‌شده؛ کار را به جاب صف‌شدهٔ
  [`SendContactGroupSmsJob`](../app/Jobs/SendContactGroupSmsJob.php) می‌سپارد که
  از اندپوینت ارسال گروهی خودِ پنل پیامک استفاده می‌کند.

هر دو مسیر اول ردیف‌های `SmsMessage` را می‌سازند و بعد جاب دیسپچ می‌کنند تا
درخواست HTTP هرگز منتظر درگاه نماند.

---

## ۵. نظارت ادمین

ریسورس‌های Filament `Contacts` و `ContactGroups` (`app/Filament/Resources/`) —
هر دو `canCreate() => false` (رکورد فقط از سایت متولد می‌شود)، عمدتاً فقط‌خواندنی،
زیر گروه ناوبری «کاربران».
