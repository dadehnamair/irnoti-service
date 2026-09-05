# راهنمای زیرسیستم مالی (کیف پول / دفتر کل / فیش / فاکتور)

> مکمل [starter.md](starter.md). این سند مدل داده، نقطهٔ تسویهٔ مرکزی، و مسیرهای
> پرداخت زیرسیستم مالی را توضیح می‌دهد — پایه‌ای که خرید خط (`LineOrder`)، اشتراک
> (`Subscription`)، بستهٔ پیامک (`PackageOrder`)، کارت ویزیت (`BusinessCard`) و نصب
> بازارچه (`MarketplaceInstallation`) همه روی آن سوار می‌شوند.

---

## ۱. مفهوم

هر کاربر یک **کیف پول** (`Wallet`) دارد که فقط با تراکنش‌های دفتر کلِ **غیرقابل‌ویرایش**
(`WalletTransaction`) تغییر می‌کند. موجودی روی خودِ ردیف کیف پول کش می‌شود (نه این‌که هر بار
از جمع دفتر کل محاسبه شود)، اما هر تغییری الزاماً یک ردیف دفتر کل هم می‌سازد — یعنی
موجودی همیشه قابل ردیابی و اثبات است.

چهار جریان «قابل‌پرداخت» (Payable) — خرید پلن، خرید خط، خرید بستهٔ پیامک، خرید کارت
ویزیت، نصب افزونهٔ بازارچه، فاکتور مدیر — همه به یک نقطهٔ تسویهٔ واحد ختم می‌شوند:
[`App\Support\PayableSettlement`](../app/Support/PayableSettlement.php). پرداخت می‌تواند از
سه مسیر برسد: **درگاه آنلاین**، **کیف پول**، یا **فیش بانکی** — نتیجه‌اش برای همهٔ
Payable ها یکسان است.

---

## ۲. مدل داده

### `wallets`

| ستون | توضیح |
| --- | --- |
| `user_id` | یکتا — هر کاربر یک کیف پول |
| `balance` | موجودی جاری (تومان، عدد صحیح، **کش‌شده و مرجع**) |

مدل: [`app/Models/Wallet.php`](../app/Models/Wallet.php) — `User::wallet()` با
`firstOrCreate` می‌سازدش. `credit()`/`debit()` (هر دو از `move()` عبور می‌کنند):
ردیف را `lockForUpdate()` می‌کنند، داخل یک تراکنش دیتابیس `before`/`after` را حساب
می‌کنند، یک `WalletTransaction` می‌نویسند، و `idempotency_key` می‌پذیرند تا صدا زدن
دوباره (retry کال‌بک درگاه، تأیید دوبارهٔ فیش) بی‌اثر باشد نه دوبار-شارژ.

> پول همیشه **تومان صحیح** است، هرگز float. اگر مقداری از سمت وب‌سرویس پنل پیامک
> (که ریال برمی‌گرداند) می‌آید، از `rial_to_toman()` عبور می‌کند.

### `wallet_transactions` — دفتر کل، تغییرناپذیر

مدل: [`app/Models/WalletTransaction.php`](../app/Models/WalletTransaction.php) —
در `booted()` به‌طور کامل جلوی `updating`/`deleting` را می‌گیرد. ستون‌های کلیدی:
`type`، `direction`، `amount`، `balance_before`/`balance_after` (عکس لحظه‌ای)،
مورف `reference` (اختیاری — کدام Payable باعث این ردیف شد)، `idempotency_key`
(یکتا).

### سایر جدول‌ها

| مدل | جدول | نقش |
| --- | --- | --- |
| [`WalletTopup`](../app/Models/WalletTopup.php) | `wallet_topups` | درخواست شارژ کیف پول (route key = `token`) |
| [`BankReceipt`](../app/Models/BankReceipt.php) | `bank_receipts` | فیش واریز آفلاین؛ مورف چندریختی `receiptable` به `WalletTopup`/`Subscription`/`LineOrder`/`PackageOrder`/`Invoice` یا `null` (شارژ ساده)؛ `status`: pending/approved/rejected؛ `reviewed_by`/`reviewed_at`/`admin_note` |
| [`SmsPackage`](../app/Models/SmsPackage.php) | `sms_packages` | بستهٔ اعتبار پیامکی مدیرساخته (`price`/`sms_count`، route key = `slug`) |
| [`PackageOrder`](../app/Models/PackageOrder.php) | `package_orders` | خرید یک `SmsPackage`؛ تسویه → `users.sms_credit` را زیاد می‌کند |
| [`Invoice`](../app/Models/Invoice.php) | `invoices` | فاکتور مدیرصادره؛ شمارهٔ `INV-{سال‌شمسی}-{ترتیب}` (`Jalalian::now()->getYear()`) |
| [`InvoiceItem`](../app/Models/InvoiceItem.php) | `invoice_items` | ردیف فاکتور؛ ذخیره/حذفش `subtotal`/`total` فاکتور والد را خودکار دوباره حساب می‌کند |

---

## ۳. نقطهٔ تسویهٔ مرکزی — `PayableSettlement`

```php
PayableSettlement::settle(Model $payable, array $payment);
```

یک `match(true)` روی نوع `$payable` است — یک شاخهٔ **idempotent** به ازای هر نوع
(`WalletTopup`, `Subscription`, `LineOrder`, `BusinessCard`, `PackageOrder`,
`MarketplaceInstallation`, `Invoice`). هر شاخه اول وضعیت فعلی را چک می‌کند (اگر
قبلاً تسویه شده، کاری نمی‌کند)، سپس اثر دامنه‌ای را اعمال می‌کند (شارژ کیف پول،
فعال‌سازی پلن، افزودن `sms_credit`، `active` کردن فاکتور/کارت ویزیت/نصب، …) و در
آخر از طریق [`OperationNotifier`](../app/Support/OperationNotifier.php) پیامک اطلاع‌رسانی می‌فرستد.

سه نقطهٔ فراخوانی:

1. **کال‌بک درگاه آنلاین** — کنترلرهای `WalletController::callback`،
   `InvoiceController::callback`، `PackageOrderController` و مشابه، از طریق trait
   مشترک [`HandlesGatewayPayment`](../app/Support/HandlesGatewayPayment.php).
2. **پرداخت از کیف پول** — اول `Wallet::debit()` مستقیم، بعد `settle()` با
   `method=wallet`.
3. **تأیید فیش بانکی** — [`App\Support\BankReceiptService::approve()`](../app/Support/BankReceiptService.php)،
   هم از فرم مشتری و هم از اکشن «تأیید» در ریسورس Filament `BankReceipts` صدا زده
   می‌شود. اگر فیش به هیچ Payable وصل نباشد (شارژ سادهٔ کیف پول)، مستقیم کیف پول را
   شارژ می‌کند؛ در غیر این صورت `settle($receipt->receiptable, ...)`.

---

## ۴. کمک‌تابع‌های تاریخ/پول

[`app/Support/helpers.php`](../app/Support/helpers.php):

- `jalali_date($date, $format='Y/m/d', $default='—')` / `jalali_datetime()` — روی
  `Morilog\Jalali\Jalalian::fromCarbon()`.
- `toman($amount, $withUnit=false)` — عدد صحیح با جداکنندهٔ هزارگان.
- `rial_to_toman($rial)` — تبدیل خروجی وب‌سرویس پنل پیامک.
- `fa_digits()` — تبدیل ارقام لاتین به فارسی.

برای جدول‌های ادمین، `AppServiceProvider::registerFilamentColumnMacros()`
(`app/Providers/AppServiceProvider.php`) همین‌ها را به‌صورت ماکرو روی
`Filament\Tables\Columns\TextColumn` می‌گذارد: `->jalaliDate()`،
`->jalaliDateTime()`، `->toman()` — و `registerBladeDirectives()` معادل‌های Blade
(`@jdate`, `@jdatetime`, `@toman`) را ثبت می‌کند. همین ترکیب (`ariaieboy/filament-jalali`
با یک `DateTimePicker::configureUsing(...->jalali())` سراسری در `AppServiceProvider::boot()`)
همهٔ فیلدهای تاریخ پنل ادمین را بدون تنظیم دستی در هر فرم، شمسی می‌کند. در فرم‌های
داشبورد مشتری (خارج از Filament)، کتابخانهٔ `@majidh1/jalalidatepicker` (بارگذاری در
`resources/js/account.js`) روی هر `<input data-jdp>` فعال می‌شود و مقدار میلادی را در
یک اینپوت مخفی کنار آن (`data-jdp-target-value-input`) می‌نویسد — سرور همیشه فقط
میلادی می‌بیند.

---

## ۵. مسیرهای مشتری

| کنترلر | مسیرها |
| --- | --- |
| [`Dashboard\WalletController`](../app/Http/Controllers/Dashboard/WalletController.php) | `/dashboard/wallet`، `/dashboard/wallet/topup`، `/dashboard/transactions`، `wallet.topup.pay`/`wallet.topup.callback` |
| [`Dashboard\InvoiceController`](../app/Http/Controllers/Dashboard/InvoiceController.php) | `/dashboard/invoices`، `invoices.pay`/`invoices.wallet`/`invoices.callback` |
| [`Dashboard\PackageOrderController`](../app/Http/Controllers/Dashboard/PackageOrderController.php) | `/dashboard/packages` + پرداخت/کال‌بک |
| [`Dashboard\BankReceiptController`](../app/Http/Controllers/Dashboard/BankReceiptController.php) | `/dashboard/receipts`، `/dashboard/receipts/create` |

ریسورس‌های Filament مرتبط: `WalletTransactions` (فقط نمایش)، `BankReceipts` (اکشن
تأیید/رد)، `Invoices`، `PackageOrders`، `SmsPackages`، `BankAccounts`.
