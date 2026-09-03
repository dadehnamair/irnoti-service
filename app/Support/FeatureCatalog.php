<?php

namespace App\Support;

/**
 * The complete customer dashboard sidebar (docs/starter.md §15) — single source
 * of truth. FeaturesSeeder writes one `features` row per item, the sidebar
 * (dashboard.partials.nav-features) renders them grouped in this order, and the
 * admin panel toggles / grants them.
 *
 * Item value is either a plain label string, or an array:
 *   'route'  — Laravel route name to link to
 *   'url'    — literal href (for anchors / external), wins over `route`
 *   'system' — built-in page: always shown once active, not gated by groups.
 *              Implies the row ships enabled.
 *
 * Everything WITHOUT `system` ships disabled (`is_active = false`) — «بزودی
 * فعال می‌شوند» — and only appears as a real link once switched on in the admin
 * panel AND granted to the account.
 */
class FeatureCatalog
{
    /**
     * @var array<string, array{label: string, items: array<string, string|array<string, mixed>>}>
     */
    public const GROUPS = [
        'account' => [
            'label' => 'حساب کاربری',
            'items' => [
                'account.summary' => ['label' => 'خلاصه حساب', 'route' => 'dashboard', 'system' => true],
                'account.profile' => ['label' => 'تکمیل اطلاعات', 'route' => 'dashboard.profile', 'system' => true],
                'account.plan' => ['label' => 'پلن و اشتراک', 'route' => 'dashboard.plans', 'system' => true],
            ],
        ],

        'marketplace' => [
            'label' => 'بازارچه',
            'items' => [
                'marketplace.browse' => ['label' => 'بازارچه افزونه‌ها', 'route' => 'dashboard.marketplace', 'system' => true],
            ],
        ],

        'sms' => [
            'label' => 'ارسال پیامک',
            'items' => [
                'sms.send' => ['label' => 'ارسال پیامک', 'route' => 'dashboard.sms', 'system' => true],
                'sms.bulk' => ['label' => 'ارسال انبوه', 'route' => 'dashboard.contacts.send', 'system' => true],
                'sms.targeted' => 'منطقه‌ای، هدفمند',
                'sms.gradual' => 'ارسال تدریجی',
                'sms.smart' => 'ارسال هوشمند',
                'sms.matched' => 'ارسال متناظر',
                'sms.peyamap' => 'پیامپ',
                'sms.link_tracker' => 'لینک ترکر',
                'sms.filtered' => 'پیامک‌های فیلتر شده',
                'sms.postal_code' => 'ارسال کدپستی',
                'sms.postal_mci' => 'کد پستی همراه اول',
                'sms.postal_irancell' => 'کد پستی ایرانسل',
                'sms.lba' => 'ارسال پیامک زنده (LBA)',
                'sms.bts' => 'BTS',
                'sms.favorites' => 'علاقه‌مندی‌ها',
            ],
        ],

        'messengers' => [
            'label' => 'پیام‌رسان‌ها',
            'items' => [
                // Built-in page (MessengerController): bulk send to بله / ایتا /
                // واتساپ (docs/starter.md §91). Always available like the core
                // SMS menu items; individual channels are toggled in تنظیمات.
                'messengers.send' => ['label' => 'ارسال به پیام‌رسان', 'route' => 'dashboard.messenger', 'system' => true],
                'messengers.bale' => 'ارسال بله',
                'messengers.eitaa' => 'ارسال ایتا',
                'messengers.whatsapp' => 'ارسال واتساپ',
            ],
        ],

        'scheduled' => [
            'label' => 'ارسال زماندار',
            'items' => [
                'scheduled.sms' => 'پیامک زماندار',
                'scheduled.smart' => 'هوشمند زماندار',
                'scheduled.matched' => 'متناظر زماندار',
                'scheduled.due' => 'پیامک سر رسید',
                'scheduled.credit' => 'پیامک اعتباری',
                'scheduled.reply' => 'پاسخ زماندار',
                'scheduled.reminder' => 'یادآور زماندار',
            ],
        ],

        'messages' => [
            'label' => 'پیام‌ها',
            'items' => [
                // Built-in pages (MessagesController): the account's provider-side
                // archive — دریافتی + ارسالی — always available like the other
                // core menu items. Read live through the customer's own panel.
                'messages.inbox' => ['label' => 'دریافتی', 'route' => 'dashboard.messages.inbox', 'system' => true],
                'messages.sent' => ['label' => 'ارسالی', 'route' => 'dashboard.messages.sent', 'system' => true],
                'messages.trash' => 'حذف شده',
            ],
        ],

        'contacts' => [
            'label' => 'مخاطبین',
            'items' => [
                'contacts.book' => ['label' => 'دفترچه تلفن', 'route' => 'dashboard.contacts', 'system' => true],
                'contacts.groups' => ['label' => 'گروه‌های مخاطبین', 'route' => 'dashboard.contacts.groups', 'system' => true],
                'contacts.treasury' => 'گنجینه پیامک',
                'contacts.list_add' => 'اضافه به لیست',
                'contacts.list_remove' => 'حذف از لیست',
                'contacts.special_list' => 'لیست ویژه',
            ],
        ],

        'tools' => [
            'label' => 'ابزارها',
            'items' => [
                'tools.exam' => 'آزمون',
                'tools.survey' => 'نظرسنجی',
                'tools.rating' => 'امتیازدهی',
                'tools.contest' => 'مسابقه',
                'tools.code_assign' => 'کد دهی',
                'tools.code_reader' => 'کدخوان',
                'tools.number_assign' => 'شماره دهی',
                'tools.content' => 'تولید محتوا',
                'tools.international' => 'بین‌المللی',
            ],
        ],

        'pro' => [
            'label' => 'ابزار ویژه',
            'items' => [
                'pro.pattern_webservice' => 'وبسرویس خدماتی (الگو)',
                'pro.smart_secretary' => 'منشی هوشمند',
                'pro.occasion' => 'پیامک مناسبت',
                'pro.secretary' => 'منشی',
                'pro.forward' => 'انتقال پیامک',
                'pro.analyzer' => 'تحلیلگر',
                'pro.email_sms' => 'ایمیل پیامک',
                'pro.mobile_send' => 'ارسال از موبایل',
                'pro.fax' => 'پیامک فکس',
                'pro.subusers' => 'کاربران (زیرمجموعه)',
                'pro.business_card' => 'کارت ویزیت',
            ],
        ],

        'voice' => [
            'label' => 'پیام صوتی',
            'items' => [
                'voice.tts' => 'پیام صوتی متنی',
                'voice.send' => 'ارسال پیام صوتی',
                'voice.files' => 'فایل‌های صوتی',
            ],
        ],

        'developers' => [
            'label' => 'توسعه دهندگان',
            'items' => [
                'developers.api' => 'وب سرویس و API',
                'developers.webservice_settings' => 'تنظیمات وبسرویس',
                'developers.webservice_log' => 'لاگ فراخوانی وبسرویس',
                'developers.traffic_transfer' => 'انتقال ترافیک',
            ],
        ],

        'commerce' => [
            'label' => 'خرید و بسته‌ها',
            'items' => [
                'commerce.line' => ['label' => 'خرید خط اختصاصی', 'route' => 'dashboard.lines', 'system' => true],
                'commerce.package' => ['label' => 'بسته پیامکی', 'route' => 'dashboard.packages', 'system' => true],
                'commerce.package_change' => 'ارتقا / کاهش بسته',
            ],
        ],

        'finance' => [
            'label' => 'مالی',
            'items' => [
                'finance.wallet' => ['label' => 'کیف پول', 'route' => 'dashboard.wallet', 'system' => true],
                'finance.transactions' => ['label' => 'سوابق مالی', 'route' => 'dashboard.transactions', 'system' => true],
                'finance.invoices' => ['label' => 'صورت‌حساب‌ها', 'route' => 'dashboard.invoices', 'system' => true],
                'finance.receipts' => ['label' => 'فیش‌های بانکی', 'route' => 'dashboard.receipts', 'system' => true],
            ],
        ],

        'reports' => [
            'label' => 'گزارش‌ها',
            'items' => [
                'reports.detailed' => 'گزارش ارسال تفکیکی',
                'reports.summary' => 'گزارش کلی ارسال و هزینه',
            ],
        ],

        'support' => [
            'label' => 'پشتیبانی',
            'items' => [
                'support.ticket' => 'ارسال درخواست (تیکت)',
                'support.guide' => 'راهنمای سامانه',
                'support.remote' => 'کنترل از راه دور',
                'support.link_activation' => 'فعالسازی لینک',
                'support.calls' => 'تماس‌ها',
            ],
        ],

        'settings' => [
            'label' => 'تنظیمات',
            'items' => [
                'settings.general' => 'تنظیمات',
                'settings.password' => 'تغییر رمز',
                'settings.quick_texts' => 'متون سریع',
                'settings.messenger' => 'پیام‌رسان جدید',
            ],
        ],
    ];

    /**
     * Flat list of every feature, ready for FeaturesSeeder.
     *
     * @return list<array{key: string, group_key: string, group_label: string, label: string, route: ?string, url: ?string, system: bool, sort: int}>
     */
    public static function all(): array
    {
        $rows = [];
        $sort = 0;

        foreach (self::GROUPS as $groupKey => $group) {
            foreach ($group['items'] as $key => $item) {
                $item = is_array($item) ? $item : ['label' => $item];

                $rows[] = [
                    'key' => $key,
                    'group_key' => $groupKey,
                    'group_label' => $group['label'],
                    'label' => $item['label'],
                    'route' => $item['route'] ?? null,
                    'url' => $item['url'] ?? null,
                    'system' => (bool) ($item['system'] ?? false),
                    'sort' => $sort,
                ];

                $sort += 10;
            }
        }

        return $rows;
    }

    /** All feature keys defined here — used to prune stale `features` rows. */
    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }
}
