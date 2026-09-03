# مشخصات فنی و محصول سامانه فروش و خدمات پیامکی

## 1. هدف پروژه

هدف، طراحی و پیاده‌سازی یک وب‌سایت کامل و حرفه‌ای برای ارائه و فروش خدمات پیامکی است.

این وب‌سایت باید علاوه بر معرفی سرویس پیامکی، امکان:

- ثبت‌نام و ورود کاربران
- خرید پنل پیامکی
- خرید و مدیریت پلن‌ها
- خرید اعتبار پیامکی
- خرید خطوط اختصاصی
- پرداخت آنلاین
- ارسال پیامک
- مدیریت مخاطبین
- ارسال پیامک گروهی
- ارسال پیامک زمان‌بندی‌شده
- استفاده از پیامک پترن
- مشاهده گزارش ارسال و Delivery
- مشاهده تراکنش‌های مالی
- ارسال و مدیریت تیکت
- ارتباط با پشتیبانی
- استفاده از API
- مشاهده مستندات API
- مدیریت حساب کاربری
- و مدیریت کامل سیستم توسط مدیر

را فراهم کند.

هدف نهایی این است که یک سامانه مشابه سرویس‌های حرفه‌ای فروش پنل پیامکی ایجاد شود، اما با طراحی مدرن‌تر، ساده‌تر، سریع‌تر و قابل توسعه.

---

# 2. مرجع امکانات

برای امکانات عمومی سامانه پیامکی می‌توان از ساختار و قابلیت‌های سایت ملی پیامک به عنوان Reference استفاده کرد.

صفحه خطوط اختصاصی:

https://www.melipayamak.com/line/

مستندات API:

https://www.melipayamak.com/api/

صفحه اصلی:

https://www.melipayamak.com/

توجه:

هدف، کپی‌کردن ظاهر، متن، تصاویر یا کد سایت مرجع نیست.

فقط ساختار امکانات و تجربه کاربری آن باید به عنوان Reference در نظر گرفته شود و UI/UX کاملاً اختصاصی طراحی شود.

---

# 3. تکنولوژی پیشنهادی

## Backend

پیشنهاد اصلی:

- PHP 8.2+
- Laravel 11 یا نسخه پایدار سازگار با هاست
- MySQL / MariaDB

Laravel انتخاب مناسبی است چون:

- توسعه سریع است
- Authentication آماده دارد
- ORM قدرتمند دارد
- Validation مناسب دارد
- Queue و Scheduler دارد
- ساخت API آسان است
- امنیت مناسبی دارد
- روی هاست اشتراکی قابل Deploy است

از معماری بیش از حد پیچیده خودداری شود.

این پروژه در فاز اول نباید Microservice باشد.

---

# 4. Frontend

برای اینکه پروژه سبک باشد:

- Laravel Blade
- Tailwind CSS یا Bootstrap 5
- JavaScript ساده / Alpine.js در صورت نیاز

در فاز اول از React / Vue / Next.js استفاده نشود مگر در بخشی که واقعاً نیاز باشد.

هدف:

> سایت باید روی هاست اشتراکی با مصرف منابع پایین اجرا شود.

---

# 5. فونت و زبان

زبان اصلی:

فارسی

جهت:

RTL

فونت اصلی:

Vazirmatn / وزیرمتن

فونت باید به شکل Local یا بهینه‌شده استفاده شود تا وابستگی به CDN وجود نداشته باشد.

تمام Interface باید:

- RTL
- فارسی
- Responsive
- Mobile Friendly
- Desktop Friendly

باشد.

---

# 6. طراحی UI/UX

طراحی باید کاملاً مدرن و حرفه‌ای باشد.

سبک پیشنهادی:

- SaaS
- مدرن
- مینیمال
- حرفه‌ای
- مناسب کسب‌وکار
- استفاده از کارت‌ها
- Border Radius مناسب
- Shadow بسیار کم
- فضای سفید مناسب
- Typography حرفه‌ای
- انیمیشن‌های بسیار سبک

از طراحی شلوغ و قدیمی پنل‌های پیامکی جلوگیری شود.

---

# 7. صفحات عمومی سایت

## صفحه اصلی

Homepage باید شامل بخش‌های زیر باشد:

### Hero

عنوان اصلی:

«سامانه حرفه‌ای ارسال پیامک برای کسب‌وکار شما»

زیرعنوان:

ارسال سریع پیامک، پنل اختصاصی، خطوط اختصاصی و API قدرتمند برای کسب‌وکارها

CTA:

- شروع کنید
- مشاهده تعرفه‌ها
- ورود به پنل

---

## معرفی امکانات

کارت‌های امکانات:

- ارسال پیامک
- ارسال انبوه
- پیامک پترن
- دفترچه تلفن
- ارسال زمان‌بندی‌شده
- API
- گزارش Delivery
- خطوط اختصاصی
- پیامک صوتی
- پیامک Smart

---

# 8. صفحه تعرفه‌ها

Route:

`/pricing`

این صفحه بسیار مهم است.

باید امکان تعریف Plan از پنل مدیریت وجود داشته باشد.

مثلاً:

### پلن پایه

- 1,000 پیامک
- 1 خط اختصاصی
- دفترچه تلفن
- گزارش ارسال
- API

قیمت:

`X تومان`

---

### پلن حرفه‌ای

- 10,000 پیامک
- 3 خط اختصاصی
- API
- پترن
- ارسال گروهی
- گزارش کامل

قیمت:

`X تومان`

---

### پلن سازمانی

- 100,000 پیامک
- خطوط اختصاصی
- API اختصاصی
- پشتیبانی ویژه
- امکانات سازمانی

قیمت:

`X تومان`

---

## ویژگی مهم

Planها نباید Hard Code باشند.

مدیر باید بتواند از Admin Panel:

- نام پلن
- Slug
- توضیحات
- قیمت
- قیمت تخفیف‌خورده
- مدت اعتبار
- تعداد پیامک
- تعداد خطوط
- تعداد کاربران
- امکانات
- Badge
- رنگ
- ترتیب نمایش
- وضعیت فعال/غیرفعال

را مدیریت کند.

---

# 9. صفحه خطوط اختصاصی

Route:

`/lines`

این صفحه باید یکی از مهم‌ترین صفحات سایت باشد.

بر اساس ساختار صفحه مرجع، خطوط باید به صورت دسته‌بندی‌شده نمایش داده شوند.

مثلاً:

- خطوط 1000
- خطوط 2000
- خطوط 3000
- خطوط 50001
- خطوط 50004
- خطوط 021
- خطوط 026
- خطوط 041
- خطوط 051
- خطوط 071
- خطوط 217000
- خطوط 9000
- خطوط 9999
- خطوط 998

ساختار اپراتورها باید از Database خوانده شود.

---

# 10. مدیریت خطوط اختصاصی

مدیر بتواند برای هر خط:

- Prefix
- Operator
- تعداد ارقام
- نوع خط
- رند / غیررند
- قیمت
- قیمت نمایندگی
- توضیحات
- امکانات
- وضعیت فروش
- وضعیت فعال
- نیازمند استعلام بودن

را تعریف کند.

---

# 11. خرید خط اختصاصی

کاربر باید بتواند:

1. اپراتور را انتخاب کند
2. تعداد ارقام را انتخاب کند
3. نوع خط را انتخاب کند
4. شماره موردنظر را وارد کند
5. قیمت را مشاهده کند
6. سفارش را ثبت کند
7. پرداخت کند
8. وضعیت سفارش را مشاهده کند

وضعیت سفارش:

- Pending
- Awaiting Payment
- Paid
- Processing
- Completed
- Rejected
- Cancelled

باشد.

---

# 12. اتصال به سرویس پیامکی

سیستم باید دارای یک SMS Provider Layer باشد.

نباید API سرویس پیامکی مستقیماً در Controllerها نوشته شود.

ساختار پیشنهادی:

```text
app/
 └── Services/
      └── Sms/
           ├── SmsProviderInterface.php
           ├── MelipayamakProvider.php
           └── SmsManager.php
```

مثلاً:

```php
interface SmsProviderInterface
{
    public function send(
        string $to,
        string $from,
        string $message
    );

    public function sendPattern(
        string $to,
        string $bodyId,
        array $variables
    );

    public function deliveryStatus(
        string $recId
    );
}
```

این کار باعث می‌شود بعداً بتوان Provider دیگری اضافه کرد.

---

# 13. اتصال به API ملی پیامک

API باید به صورت Service Class پیاده‌سازی شود.

اطلاعات API مانند:

- Username
- Password
- API Key
- Token
- Sender Number

نباید داخل کد Hard Code شوند.

در `.env` قرار گیرند.

نمونه:

```env
SMS_PROVIDER=melipayamak

MELIPAYAMAK_USERNAME=
MELIPAYAMAK_PASSWORD=
MELIPAYAMAK_API_KEY=
MELIPAYAMAK_SENDER=
```

---

# 14. قابلیت‌های API که باید در سیستم استفاده شوند

بر اساس مستندات فعلی سرویس، قابلیت‌های زیر باید در معماری سیستم در نظر گرفته شوند:

### ارسال پیامک

- ارسال یک پیامک
- ارسال به چند مخاطب
- ارسال متناظر
- دریافت شناسه ارسال

### Delivery

- دریافت وضعیت ارسال
- وضعیت تحویل
- وضعیت پیامک‌های ناموفق

### اعتبار

- دریافت اعتبار پیامکی
- دریافت تعرفه

### Pattern

- ارسال OTP
- پیامک خدماتی
- مدیریت Pattern

### دفترچه تلفن

- ایجاد گروه
- افزودن مخاطب
- ویرایش مخاطب
- حذف مخاطب
- بررسی شماره
- مدیریت گروه‌ها

### ارسال انبوه

- ارسال گروهی
- ارسال منطقه‌ای
- ارسال به بانک شماره
- دریافت گزارش

### Scheduling

- ارسال زمان‌بندی‌شده
- مشاهده ارسال‌های زمان‌بندی‌شده
- حذف ارسال زمان‌بندی‌شده

### Voice

در صورت فعال بودن سرویس Provider:

- پیام صوتی
- تماس خودکار
- تماس زمان‌بندی‌شده

### Smart SMS

در صورت فعال بودن سرویس Provider:

- ارسال Smart
- دریافت وضعیت
- دریافت جزئیات

این قابلیت‌ها در مستندات رسمی API نیز ارائه شده‌اند.

---

# 15. پنل کاربری

Route:

`/dashboard`

Dashboard باید شامل:

### خلاصه حساب

- اعتبار ریالی
- تعداد پیامک باقی‌مانده
- تعداد ارسال امروز
- تعداد پیامک موفق
- تعداد پیامک ناموفق
- تعداد خطوط
- تاریخ انقضای پنل

---

## نمودارها

نمودار:

- ارسال روزانه
- ارسال ماهانه
- موفق / ناموفق
- هزینه پیامک
- مصرف اعتبار

---

# 16. ارسال پیامک

Route:

`/dashboard/sms/send`

فرم:

- خط ارسال‌کننده
- شماره گیرنده
- متن
- تعداد پیامک
- هزینه تقریبی
- ارسال

امکانات:

- ارسال تکی
- ارسال گروهی
- انتخاب از دفترچه تلفن
- انتخاب گروه
- ارسال به فایل Excel/CSV
- نمایش تعداد کاراکتر
- نمایش تعداد SMS
- نمایش هزینه

---

# 17. دفترچه تلفن

Route:

`/dashboard/contacts`

امکانات:

- افزودن مخاطب
- ویرایش
- حذف
- جستجو
- Import CSV
- Export CSV
- گروه‌بندی

ساختار:

```text
Contacts
    ├── Name
    ├── Mobile
    ├── Email
    ├── Birthday
    ├── Group
    └── Custom Fields
```

---

# 18. ارسال گروهی

کاربر بتواند:

- گروه را انتخاب کند
- فایل CSV آپلود کند
- شماره‌ها را وارد کند
- پیام را وارد کند
- Sender را انتخاب کند

سیستم باید قبل از ارسال:

- شماره‌های نامعتبر را حذف/علامت‌گذاری کند
- Duplicateها را حذف کند
- تعداد پیامک را محاسبه کند
- هزینه را محاسبه کند

---

# 19. پیامک زمان‌بندی‌شده

Route:

`/dashboard/scheduled`

امکانات:

- انتخاب تاریخ
- انتخاب ساعت
- انتخاب مخاطب
- انتخاب گروه
- متن پیام
- ثبت زمان‌بندی

وضعیت:

- Scheduled
- Processing
- Sent
- Failed
- Cancelled

---

# 20. پیامک پترن

Route:

`/dashboard/patterns`

امکانات:

- مشاهده Patternها
- درخواست Pattern جدید
- مشاهده وضعیت تأیید
- ارسال OTP
- ارسال پیام خدماتی

---

# 21. گزارش پیامک‌ها

Route:

`/dashboard/reports`

فیلتر:

- تاریخ
- شماره گیرنده
- خط فرستنده
- وضعیت
- نوع پیام
- Pattern

وضعیت‌ها:

- Pending
- Sent
- Delivered
- Failed
- Rejected

امکان:

- Search
- Filter
- Export CSV
- Pagination

---

# 22. تراکنش‌های مالی

Route:

`/dashboard/transactions`

نمایش:

- تاریخ
- نوع تراکنش
- مبلغ
- درگاه
- شماره پیگیری
- وضعیت

انواع:

- خرید پلن
- شارژ حساب
- خرید خط
- Refund
- Adjustment

---

# 23. کیف پول

سیستم باید Wallet داشته باشد.

کاربر بتواند حساب خود را شارژ کند.

مثلاً:

```text
موجودی:
1,250,000 تومان

[شارژ حساب]
```

شارژ از طریق درگاه پرداخت انجام شود.

---

# 24. درگاه پرداخت شتابیت

پرداخت آنلاین باید از طریق پکیج/درگاه شتابیت پیاده‌سازی شود.

Payment Service باید مستقل باشد:

```text
app/
 └── Services/
      └── Payment/
           ├── PaymentGatewayInterface.php
           ├── ShetabitGateway.php
           └── PaymentManager.php
```

نمونه Interface:

```php
interface PaymentGatewayInterface
{
    public function purchase(
        int $amount,
        string $callbackUrl,
        array $metadata = []
    );

    public function pay(
        string $transactionId
    );

    public function verify(
        string $transactionId
    );

    public function settle(
        string $transactionId
    );
}
```

---

# 25. نکته بسیار مهم پرداخت

هیچ‌وقت بعد از برگشت کاربر از درگاه، فقط بر اساس URL یا Query String پرداخت را موفق اعلام نکن.

باید:

1. Transaction ایجاد شود
2. کاربر به درگاه منتقل شود
3. Callback دریافت شود
4. Transaction پیدا شود
5. Verify انجام شود
6. مبلغ بررسی شود
7. شماره تراکنش ذخیره شود
8. تراکنش فقط یک بار پردازش شود
9. Wallet یا Order شارژ شود

پرداخت باید Idempotent باشد.

---

# 26. ثبت‌نام

Route:

`/register`

فیلدها:

- نام
- نام خانوادگی
- شماره موبایل
- ایمیل
- رمز عبور
- تأیید رمز عبور

ثبت‌نام با OTP موبایل پیشنهاد می‌شود.

Flow:

```text
Register
   ↓
Send OTP
   ↓
Verify OTP
   ↓
Create Account
   ↓
Login
   ↓
Dashboard
```

## نوع حساب: حقیقی / حقوقی

در ابتدای مرحلهٔ ۱ ویزارد تکمیل اطلاعات، کاربر مشخص می‌کند حساب «شخص حقیقی» است یا «شخص حقوقی».

برای حساب حقوقی، علاوه بر مدارک هویتی نمایندهٔ امضاکننده (نام، کد ملی، تصویر کارت ملی و احراز هویت)، این موارد هم دریافت می‌شود:

- مشخصات و اطلاعات ثبتی شرکت: نام کامل شرکت، نوع شخصیت حقوقی، شناسهٔ ملی (۱۱ رقم)، شمارهٔ ثبت، تاریخ ثبت، کد اقتصادی، تلفن و کد پستی و نشانی شرکت، سمت نماینده.
- مدارک شرکت (روی دیسک خصوصی `local`): آگهی تأسیس/روزنامهٔ رسمی (الزامی)، آگهی آخرین تغییرات، و مدارک اضافهٔ اختیاری (چند فایل — پروانه، مجوز، …).

اطلاعات ثبتیِ شرکت و نوع حساب پس از تأیید نهایی حساب توسط مدیر قفل می‌شوند. `users.name` برای حساب حقوقی نام شرکت است و فیلدهای نام/نام‌خانوادگی نمایندهٔ امضاکننده را توصیف می‌کنند.

---

# 27. ورود

امکانات:

- ورود با موبایل/ایمیل
- Password
- Remember Me
- Forgot Password
- OTP Login

---

# 28. امنیت

موارد زیر الزامی هستند:

- CSRF Protection
- XSS Protection
- SQL Injection Protection
- Rate Limiting
- Password Hashing
- Session Security
- Authorization
- Role Permission
- File Upload Validation
- API Authentication
- Secure `.env`
- جلوگیری از Mass Assignment
- Validation تمام Requestها

---

# 29. تیکت پشتیبانی

Route:

`/dashboard/tickets`

کاربر بتواند:

- تیکت جدید ایجاد کند
- موضوع انتخاب کند
- اولویت انتخاب کند
- متن ارسال کند
- فایل Attach کند
- پاسخ پشتیبانی را ببیند
- به تیکت پاسخ دهد
- تیکت را ببندد

وضعیت:

```text
Open
Pending
Answered
Closed
```

اولویت:

```text
Low
Normal
High
Urgent
```

---

# 30. سیستم تیکت Admin

مدیر بتواند:

- تمام تیکت‌ها را ببیند
- فیلتر کند
- به کارشناس اختصاص دهد
- پاسخ دهد
- وضعیت را تغییر دهد
- اولویت را تغییر دهد
- تیکت را ببندد

---

# 31. صفحه تماس با ما

Route:

`/contact`

شامل:

- شماره تماس
- ایمیل
- آدرس
- ساعات کاری
- فرم تماس
- نقشه
- لینک شبکه‌های اجتماعی

---

# 32. صفحات محتوایی

CMS ساده برای:

- درباره ما
- قوانین
- حریم خصوصی
- سوالات متداول
- وبلاگ
- راهنمای استفاده
- آموزش API

مدیر بتواند صفحات را مدیریت کند.

---

# 33. وبلاگ

Route:

`/blog`

امکانات:

- دسته‌بندی
- مقاله
- تصویر شاخص
- SEO Title
- SEO Description
- Slug
- Tags
- وضعیت انتشار

---

# 34. مستندات API

Route:

`/developers`

صفحه حرفه‌ای برای برنامه‌نویسان.

شامل:

- API Overview
- Authentication
- Send SMS
- Pattern
- Delivery
- Contacts
- Webhooks
- Error Codes
- Examples

زبان‌های نمونه:

```text
PHP
Laravel
JavaScript
Python
cURL
C#
Java
```

مستندات اصلی Provider نیز نمونه کد برای زبان‌های مختلف ارائه می‌کند و REST API مبتنی بر Auth Token دارد.

---

# 35. API اختصاصی سامانه

علاوه بر API Provider، خود سامانه نیز باید API داشته باشد.

مثلاً:

```http
POST /api/v1/sms/send
```

Authentication:

```http
Authorization: Bearer {token}
```

Request:

```json
{
    "from": "5000XXXX",
    "to": "09123456789",
    "message": "سلام"
}
```

Response:

```json
{
    "success": true,
    "message_id": "123456789"
}
```

---

# 36. API Key

هر کاربر بتواند API Key ایجاد کند.

مثلاً:

```text
API Keys

Production
sk_live_xxxxxxxxxxxxx

Test
sk_test_xxxxxxxxxxxxx
```

امکانات:

- Create
- Revoke
- Regenerate
- Last Used
- Created At

کلید Secret فقط یک بار به کاربر نمایش داده شود.

---

# 37. Webhook

امکان تعریف Webhook توسط کاربر:

```text
https://example.com/webhooks/sms
```

برای Eventها:

- SMS Sent
- SMS Delivered
- SMS Failed
- Payment Completed

---

# 38. پنل مدیریت

Route:

`/admin`

Dashboard:

- تعداد کاربران
- فروش امروز
- فروش ماه
- درآمد
- تعداد پیامک‌ها
- مصرف SMS
- سفارش‌ها
- تیکت‌ها
- پرداخت‌ها
- خطوط فروخته‌شده

---

# 39. مدیریت کاربران

Admin:

- لیست کاربران
- جستجو
- مشاهده حساب
- تغییر وضعیت
- تغییر اعتبار
- شارژ دستی
- کسر اعتبار
- مشاهده تراکنش‌ها
- مشاهده ارسال‌ها
- مشاهده تیکت‌ها
- تغییر Plan

وضعیت:

```text
Active
Suspended
Blocked
Pending
```

---

# 40. مدیریت Plan

CRUD کامل:

```text
Plans
 ├── Create
 ├── Edit
 ├── Delete
 ├── Activate
 └── Deactivate
```

---

# 41. مدیریت قیمت پیامک

مدیر بتواند قیمت پیامک را مدیریت کند.

مثلاً:

```text
SMS Price
----------------
Normal: 180 تومان
Pattern: 200 تومان
Bulk: 175 تومان
```

قیمت باید قابلیت Override برای Plan داشته باشد.

---

# 42. سیستم تخفیف

امکان تعریف Coupon:

```text
Code: NEWUSER
Type: Percentage
Value: 20%
Max Usage: 100
Expire Date: ...
```

انواع:

- Percentage
- Fixed Amount

---

# 43. سیستم Referral

در صورت نیاز:

هر کاربر یک کد معرف داشته باشد.

مثلاً:

```text
ali123
```

و برای خریدهای کاربران معرفی‌شده درصد کمیسیون محاسبه شود.

---

# 44. Notification

سیستم Notification داخلی:

- پرداخت موفق
- شارژ حساب
- خرید پلن
- تیکت جدید
- پاسخ تیکت
- پایان اعتبار
- کاهش موجودی
- خطای ارسال

Notificationها:

- داخل پنل
- Email
- SMS

---

# 45. Cron / Scheduler

برای کارهای زمان‌بندی‌شده:

```text
php artisan schedule:run
```

کاربرد:

- ارسال پیامک زمان‌بندی‌شده
- Sync Delivery
- بررسی تراکنش‌ها
- Notification
- پاکسازی Logها
- بررسی Expiration

---

# 46. Queue

برای عملیات سنگین از Queue استفاده شود.

مثلاً:

```text
SendSmsJob
SyncDeliveryJob
SendNotificationJob
ImportContactsJob
ProcessBulkSmsJob
```

اما چون پروژه روی هاست اشتراکی قرار می‌گیرد، معماری Queue باید طوری باشد که بدون Worker دائمی هم قابل استفاده باشد.

در هاست‌هایی که Cron دارند:

```text
* * * * * php /path/to/artisan schedule:run
```

استفاده شود.

در صورت نبود Redis، از Database Queue استفاده شود.

---

# 47. طراحی Database

جداول اصلی:

```text
users

plans
plan_features
features

subscriptions

wallets
wallet_transactions

orders
order_items

payments

sms_messages
sms_batches
sms_recipients

sms_lines
sms_operators

contacts
contact_groups
contact_group_members

patterns

scheduled_messages

api_keys
webhooks

tickets
ticket_messages
ticket_attachments

notifications

coupons
coupon_usages

referrals

pages
blog_posts
blog_categories

settings

audit_logs
```

---

# 48. جدول Users

فیلدهای پیشنهادی:

```text
id
name
mobile
email
password
mobile_verified_at
email_verified_at
status
last_login_at
created_at
updated_at
```

---

# 49. Wallet

```text
id
user_id
balance
currency
created_at
updated_at
```

Balance بهتر است به صورت Integer ذخیره شود.

واحد:

تومان

و از Float استفاده نشود.

---

# 50. Financial Transactions

هر تراکنش باید immutable باشد.

```text
id
user_id
type
amount
balance_before
balance_after
reference_type
reference_id
description
created_at
```

---

# 51. Orders

```text
id
user_id
order_number
type
status
subtotal
discount
tax
total
paid_at
created_at
updated_at
```

---

# 52. Payments

```text
id
user_id
order_id
gateway
authority
transaction_id
amount
status
verified_at
metadata
created_at
updated_at
```

---

# 53. SMS Message

```text
id
user_id
batch_id
from
message
type
provider
provider_message_id
status
cost
sent_at
delivered_at
created_at
updated_at
```

---

# 54. Ticket

```text
id
user_id
assigned_to
subject
priority
status
last_reply_at
closed_at
created_at
updated_at
```

---

# 55. SEO

تمام صفحات عمومی باید SEO Friendly باشند.

برای هر صفحه:

- SEO Title
- Meta Description
- Canonical
- Open Graph
- Twitter Card
- Schema.org
- Sitemap
- Robots.txt

URLها:

```text
/pricing
/lines
/api
/blog
/blog/{slug}
/contact
/about
```

---

# 56. Performance

چون پروژه روی هاست اشتراکی قرار می‌گیرد:

الزامی است:

- Query Optimization
- Database Indexing
- Pagination
- Lazy Loading
- Cache در موارد مناسب
- جلوگیری از N+1
- Minify CSS/JS
- Image Optimization
- استفاده نکردن از Libraryهای سنگین غیرضروری

---

# 57. Shared Hosting Compatibility

این مورد بسیار مهم است.

پروژه باید بدون Docker طراحی شود.

نباید وابسته به:

- Docker
- Kubernetes
- Redis اجباری
- Supervisor اجباری
- Node Runtime در Production

باشد.

Production باید بتواند روی هاست اشتراکی اجرا شود.

---

# 58. ساختار Deployment

پیشنهاد:

```text
/home/user/
    ├── app/
    │    ├── app/
    │    ├── bootstrap/
    │    ├── config/
    │    ├── database/
    │    ├── resources/
    │    ├── routes/
    │    └── vendor/
    │
    └── public_html/
         ├── index.php
         ├── build/
         └── assets/
```

فقط `public` Laravel در معرض Web قرار گیرد.

فایل‌های:

```text
.env
storage
app
config
database
vendor
```

نباید مستقیماً از Web قابل دسترسی باشند.

---

# 59. نصب روی هاست

یک Installation Guide ایجاد شود.

مثلاً:

```bash
composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan storage:link

php artisan config:cache

php artisan route:cache

php artisan view:cache
```

در صورت عدم دسترسی SSH، روش نصب از طریق File Manager و phpMyAdmin نیز مستند شود.

---

# 60. Environment

نمونه:

```env
APP_NAME="SMS Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=database

SMS_PROVIDER=melipayamak

MELIPAYAMAK_USERNAME=
MELIPAYAMAK_PASSWORD=
MELIPAYAMAK_API_KEY=
MELIPAYAMAK_SENDER=

PAYMENT_GATEWAY=shetabit

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```

---

# 61. Admin Role

سیستم Role-Based باشد.

حداقل:

```text
Super Admin
Admin
Support
Finance
Content Manager
User
```

Permissionها قابل مدیریت باشند.

---

# 62. Audit Log

تمام عملیات مهم Admin ثبت شود.

مثلاً:

```text
Admin changed user balance
Admin changed user status
Admin created plan
Admin changed SMS price
Admin verified payment
Admin replied ticket
```

---

# 63. Error Handling

تمام خطاها باید User Friendly باشند.

کاربر نباید Stack Trace یا اطلاعات SQL ببیند.

مثلاً:

```json
{
    "success": false,
    "message": "در حال حاضر امکان انجام عملیات وجود ندارد."
}
```

Log کامل فقط در Server ذخیره شود.

---

# 64. API Error Format

فرمت استاندارد:

```json
{
    "success": false,
    "message": "Invalid API key",
    "code": "INVALID_API_KEY"
}
```

HTTP Status مناسب استفاده شود.

---

# 65. تست

حداقل Feature Test برای:

- Register
- Login
- OTP
- Payment
- Wallet
- Plan Purchase
- SMS Send
- Ticket
- API Authentication
- Admin Authorization

نوشته شود.

---

# 66. Seed Data

پروژه باید Seeder داشته باشد.

مثلاً:

```text
Admin User
Default Plans
SMS Operators
Default Settings
Ticket Departments
Default Features
```

---

# 67. تنظیمات سیستم

Admin بتواند تنظیمات زیر را تغییر دهد:

- نام سایت
- Logo
- Favicon
- شماره تماس
- ایمیل
- آدرس
- شبکه‌های اجتماعی
- SMS Sender
- قیمت پیامک
- حداقل شارژ
- مالیات
- فعال/غیرفعال کردن ثبت‌نام
- فعال/غیرفعال کردن OTP
- Maintenance Mode

---

# 68. Responsive

تمام صفحات باید در:

- Mobile
- Tablet
- Laptop
- Desktop

کاملاً Responsive باشند.

پنل موبایل باید به‌صورت واقعی قابل استفاده باشد، نه فقط کوچک‌شده Desktop.

---

# 69. Header

Header سایت عمومی:

```text
Logo

امکانات
تعرفه‌ها
خطوط اختصاصی
API
وبلاگ
درباره ما
تماس با ما

ورود
ثبت‌نام
```

---

# 70. Footer

Footer شامل:

- لینک‌های مهم
- خدمات
- API
- پشتیبانی
- قوانین
- شبکه‌های اجتماعی
- نمادهای اعتماد در صورت وجود
- Copyright

---

# 71. صفحات Authentication

صفحات:

```text
/register
/login
/forgot-password
/reset-password
/verify-mobile
```

طراحی باید بسیار تمیز و مدرن باشد.

---

# 72. Dashboard Layout

ساختار:

```text
Sidebar

داشبورد
ارسال پیامک
ارسال گروهی
زمان‌بندی
گزارش‌ها
دفترچه تلفن
پترن
خطوط من
خرید پلن
کیف پول
تراکنش‌ها
API
تیکت‌ها
تنظیمات
خروج
```

---

# 73. Admin Layout

```text
Dashboard
Users
Plans
Orders
Payments
Wallets
SMS
SMS Lines
Operators
Patterns
Tickets
Content
Blog
Coupons
Settings
Logs
```

---

# 74. API Documentation UI

صفحه API باید ظاهری شبیه Documentationهای حرفه‌ای داشته باشد.

مثلاً:

```text
Authentication
    API Keys

SMS
    Send SMS
    Send Pattern
    Delivery

Contacts
    Create Contact
    List Contacts

Webhooks
    Events
```

در کنار هر API:

- Endpoint
- Method
- Headers
- Request
- Response
- Error
- Example Code

---

# 75. قابلیت Demo

صفحه اصلی یک Demo کوچک داشته باشد.

مثلاً:

```text
شماره موبایل:
0912...

متن:
سلام! این یک پیام تست است.

[ارسال پیام تست]
```

این قسمت فقط در صورت فعال بودن تنظیمات Admin و با Rate Limit قابل استفاده باشد.

---

# 76. جلوگیری از Abuse

برای ارسال پیامک:

- Rate Limit
- محدودیت تعداد درخواست
- محدودیت تعداد گیرنده
- محدودیت حجم Batch
- جلوگیری از Spam
- محدودیت API

الزامی است.

مثلاً:

```text
API:
60 requests / minute
```

مقادیر باید از Admin قابل تنظیم باشند.

---

# 77. API Rate Limit

برای هر API Key:

```text
Requests Per Minute
Requests Per Day
```

قابل تنظیم باشد.

---

# 78. Logging

Logهای مهم:

```text
API Request
SMS Request
Payment
Authentication
Admin Actions
Webhook
Provider Errors
```

اطلاعات حساس مثل Password و API Secret هرگز Log نشوند.

---

# 79. طراحی صفحه قیمت

صفحه Pricing باید بسیار حرفه‌ای باشد.

هر Plan:

```text
┌──────────────────────────┐
│       حرفه‌ای ⭐          │
│                          │
│       490,000 تومان       │
│                          │
│ ✓ 10,000 پیامک           │
│ ✓ API                    │
│ ✓ پترن                   │
│ ✓ دفترچه تلفن            │
│ ✓ گزارش کامل             │
│                          │
│      [خرید پلن]          │
└──────────────────────────┘
```

پلن پیشنهادی با Badge:

`پیشنهاد ویژه`

---

# 80. طراحی صفحه خطوط

صفحه خطوط باید شامل:

- Tabs اپراتورها
- Filter
- Search
- تعداد رقم
- نوع خط
- قیمت
- خرید

باشد.

مثلاً:

```text
خطوط اختصاصی

[1000] [2000] [3000] [50001] [021] ...

تعداد رقم:
[ همه ]

نوع:
[ همه ]

قیمت:

┌────────────────────┐
│ 1000XXXXXXXX       │
│ 10 رقمی            │
│ 1,050,000 تومان    │
│ [خرید خط]          │
└────────────────────┘
```

---

# 81. اصل مهم معماری

هیچ اطلاعاتی که احتمالاً توسط Admin تغییر می‌کند نباید Hard Code شود.

مواردی مثل:

- قیمت
- پلن
- امکانات
- خطوط
- تعرفه
- متن صفحات
- تنظیمات
- Provider
- محدودیت‌ها

باید از Database/Config خوانده شوند.

---

# 82. اصل مهم توسعه

کد باید:

- تمیز
- ساده
- قابل فهم
- SOLID در حد منطقی
- Modular
- قابل تست
- قابل توسعه

باشد.

از Over Engineering جلوگیری شود.

---

# 83. ساختار پیشنهادی Laravel

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   └── Api/
│   │
│   ├── Requests/
│   └── Middleware/
│
├── Models/
│
├── Services/
│   ├── Sms/
│   ├── Payment/
│   ├── Wallet/
│   ├── Ticket/
│   └── Notification/
│
├── Jobs/
├── Events/
├── Listeners/
├── Policies/
└── Notifications/
```

---

# 84. Routes

Public:

```text
/
 /pricing
 /lines
 /api
 /blog
 /about
 /contact
 /faq
```

Auth:

```text
/login
/register
/forgot-password
```

Dashboard:

```text
/dashboard
/dashboard/sms
/dashboard/contacts
/dashboard/reports
/dashboard/wallet
/dashboard/orders
/dashboard/tickets
/dashboard/api
/dashboard/settings
```

Admin:

```text
/admin
/admin/users
/admin/plans
/admin/orders
/admin/payments
/admin/sms
/admin/lines
/admin/operators
/admin/tickets
/admin/content
/admin/settings
```

---

# 85. Deliverables

خروجی نهایی پروژه باید شامل:

```text
Source Code
Database Migrations
Seeders
README.md
Installation Guide
Shared Hosting Deployment Guide
.env.example
API Documentation
Admin Documentation
User Documentation
Test Suite
```

باشد.

---

# 86. README

README باید شامل:

1. معرفی پروژه
2. Requirements
3. Installation
4. Environment
5. Database
6. Storage
7. Cron
8. Queue
9. SMS Provider
10. Payment Gateway
11. Deployment
12. Troubleshooting

باشد.

---

# 87. Definition of Done

پروژه زمانی Complete محسوب می‌شود که:

- [ ] سایت عمومی کامل باشد
- [ ] Responsive باشد
- [ ] فونت فارسی مناسب داشته باشد
- [ ] Register/Login کار کند
- [ ] OTP کار کند
- [ ] Dashboard کار کند
- [ ] Planها از Admin قابل مدیریت باشند
- [ ] خرید Plan کار کند
- [ ] Wallet کار کند
- [ ] پرداخت آنلاین کار کند
- [ ] Verify پرداخت انجام شود
- [ ] SMS Provider متصل باشد
- [ ] ارسال SMS کار کند
- [ ] Delivery کار کند
- [ ] دفترچه تلفن کار کند
- [ ] ارسال گروهی کار کند
- [ ] ارسال زمان‌بندی‌شده کار کند
- [ ] Pattern کار کند
- [ ] گزارش‌ها کار کنند
- [ ] Ticket کار کند
- [ ] API Key کار کند
- [ ] API اختصاصی کار کند
- [ ] Webhook کار کند
- [ ] Admin Panel کامل باشد
- [ ] Role/Permission پیاده‌سازی شده باشد
- [ ] Audit Log وجود داشته باشد
- [ ] SEO پیاده‌سازی شده باشد
- [ ] Security بررسی شده باشد
- [ ] روی Shared Hosting قابل نصب باشد
- [ ] Docker اجباری نباشد
- [ ] Redis اجباری نباشد
- [ ] Worker دائمی اجباری نباشد
- [ ] مستندات نصب وجود داشته باشد
- [ ] `.env.example` وجود داشته باشد
- [ ] تست‌های اصلی نوشته شده باشند

---

# 88. نکته مهم برای AI Developer

اگر این پروژه توسط AI Coding Agent توسعه داده می‌شود، پروژه نباید یک‌باره و بدون برنامه تولید شود.

پیاده‌سازی باید مرحله‌ای باشد:

## Phase 1

Foundation:

- Laravel
- Database
- Authentication
- Admin
- User
- Roles
- Settings

## Phase 2

Commerce:

- Plans
- Orders
- Wallet
- Payments
- Shetabit

## Phase 3

SMS:

- SMS Provider
- Send SMS
- Delivery
- Contacts
- Bulk SMS
- Scheduled SMS
- Patterns

## Phase 4

Support:

- Tickets
- Notifications
- Contact

## Phase 5

Developer Platform:

- API Keys
- API
- Webhooks
- Documentation

## Phase 6

Content:

- Homepage
- Pricing
- Lines
- Blog
- FAQ
- SEO

## Phase 7

Optimization:

- Security
- Performance
- Caching
- Database indexes
- Shared Hosting deployment

---

# 89. مهم‌ترین محدودیت پروژه

این پروژه برای Cloud / Kubernetes / Docker طراحی نمی‌شود.

هدف اصلی:

> اجرای پایدار روی Shared Hosting

بنابراین هر تکنولوژی باید با این محدودیت انتخاب شود.

اگر بین دو تکنولوژی انتخاب وجود داشت، تکنولوژی ساده‌تر و سازگارتر با Shared Hosting انتخاب شود.

---

# 90. نتیجه نهایی

هدف ساخت یک:

> **SaaS کامل فروش و مدیریت خدمات پیامکی**

است، نه صرفاً یک Landing Page.

سیستم باید سه بخش اصلی داشته باشد:

```text
                 ┌─────────────────┐
                 │    Public Web   │
                 │ فروش و معرفی     │
                 └────────┬────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │    User Panel   │
                 │ ارسال و مدیریت   │
                 └────────┬────────┘
                          │
             ┌────────────┼────────────┐
             ▼            ▼            ▼
       SMS Provider    Payment       Support
       ملی پیامک       شتابیت         Ticket
```

معماری باید به گونه‌ای باشد که در آینده بتوان:

- Provider پیامکی جدید
- درگاه پرداخت جدید
- پلن جدید
- سرویس جدید
- API جدید
- سیستم نمایندگی
- سیستم همکاری در فروش

را بدون بازنویسی کل پروژه اضافه کرد.

---

# 91. ارسال به پیام‌رسان‌ها (بله / ایتا / واتساپ)

سرویسی **مجزا** از پیامک برای **ارسال انبوه** یک پیام به گروهی از گیرندگان — نه ارسال تکی.
گیرنده می‌تواند شمارهٔ موبایل یا شناسهٔ چت/کاربری باشد. هر پیام‌رسانی که ارسال انبوه ندارد با
پرچم قابلیت (`bulk`) کنار گذاشته می‌شود.

- **لایهٔ درایور:** `app/Services/Messenger` — دقیقاً موازی `app/Services/Sms`. اپ فقط با
  `App\Services\Messenger\MessengerManager` کار می‌کند، هرگز مستقیم با API کانال.
  `config/messenger.php` رجیستری کانال‌ها (کدنیم مقصد → کلاس درایور + `bulk` + تعرفه + اعتبارنامه).
  `MESSENGER_DRIVER`: `log` (پیش‌فرض بدون‌اعتبار توسعه، مثل `SMS_PROVIDER=log`)، `null` (تست)،
  `http` (درایورهای واقعی `BaleChannel`/`EitaaChannel`/`WhatsAppChannel`). نام سرویس تجمیع‌کنندهٔ
  واقعی نباید در کانفیگ/کلید env/لاگ دیده شود؛ `label` هر کانال از همان cascade
  (`config` → `.env` → تنظیم `messenger_<key>_label`) می‌آید.
- **جریان:** `/dashboard/messenger` (انتخاب کانال) → `/dashboard/messenger/{channel}` (فرم:
  گروه‌های مخاطبین + فهرست دستی + متن + زمان‌بندی) → `POST /dashboard/messenger/send`.
  کنترلر گیرنده‌ها را یکتا می‌کند، هزینه = `تعداد گیرنده × تعرفهٔ کانال` را از کیف پول کسر می‌کند،
  `MessengerCampaign` + یک `MessengerRecipient` برای هر گیرنده می‌سازد و `SendMessengerCampaignJob`
  را در صف می‌گذارد.
- **جاب:** بدنه را به `sendBulk` کانال می‌سپارد، وضعیت تک‌به‌تک گیرنده‌ها را می‌نویسد، کمپین را به
  `sent`/`partial`/`failed` می‌برد و هزینهٔ بخش **ناموفق** را (`failed_count × تعرفه`) با کلید
  idempotency `messenger:{id}:refund` به کیف پول برمی‌گرداند.
- **داده:** `messenger_campaigns` (خلاصهٔ کمپین + `cost`/`refunded`) و `messenger_recipients`
  (`to`، `type` = `mobile|chat`، `status`، `provider_ref`، `error`).
- **مدیریت:** تنظیمات `messenger_enabled` و `messenger_<key>_enabled` و `messenger_<key>_tariff`
  در پنل؛ منبع Filament فقط‌خواندنی `MessengerCampaigns` برای نظارت.

---

**اولویت پروژه:**

1. سادگی
2. سرعت
3. امنیت
4. قابلیت توسعه
5. سازگاری با Shared Hosting
6. UX حرفه‌ای
7. مدیریت کامل از Admin Panel

و از ساختارهای پیچیده و غیرضروری پرهیز شود.
