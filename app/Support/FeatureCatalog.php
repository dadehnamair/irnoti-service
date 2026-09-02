<?php

namespace App\Support;

/**
 * The customer dashboard mega-menu (docs/starter.md §15). Single source of truth
 * for the panel-feature catalogue: FeaturesSeeder writes one `features` row per
 * item, the dashboard sidebar renders them grouped, and the admin panel toggles
 * / grants them. Items ship disabled (`is_active = false`) — «بزودی فعال می‌شوند».
 *
 * `route` (when set) is the Laravel route name to link to once the item is
 * switched on and granted; leave it null for a not-yet-built page.
 */
class FeatureCatalog
{
    /**
     * @var array<string, array{label: string, items: array<string, string|array{label: string, route?: string}>}>
     */
    public const GROUPS = [
        'sms' => [
            'label' => 'پیامک',
            'items' => [
                'sms.send' => 'ارسال پیامک',
                'sms.targeted' => 'منطقه‌ای، هدفمند',
                'sms.gradual' => 'ارسال تدریجی',
                'sms.peyamap' => 'پیامپ',
                'sms.link_tracker' => 'لینک ترکر جدید',
                'sms.filtered' => 'پیامک‌های فیلتر شده',
                'sms.bulk' => 'ارسال انبوه',
                'sms.smart' => 'ارسال هوشمند',
                'sms.postal_code' => 'ارسال کدپستی جدید جدید',
                'sms.matched' => 'ارسال متناظر',
                'sms.postal_mci' => 'کد پستی همراه اول',
                'sms.postal_irancell' => 'کد پستی ایرانسل',
                'sms.lba' => 'ارسال پیامک زنده (LBA) جدید',
                'sms.bts' => 'BTS',
                'sms.favorites' => 'علاقه‌مندی‌ها',
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
                'messages.inbox' => 'دریافتی',
                'messages.sent' => 'ارسالی',
                'messages.trash' => 'حذف شده',
            ],
        ],
        'tools' => [
            'label' => 'امکانات',
            'items' => [
                'tools.phonebook' => 'دفترچه تلفن',
                'tools.treasury' => 'گنجینه پیامک',
                'tools.list_add' => 'اضافه به لیست',
                'tools.list_remove' => 'حذف از لیست',
                'tools.exam' => 'آزمون',
                'tools.survey' => 'نظرسنجی',
                'tools.rating' => 'امتیازدهی',
                'tools.contest' => 'مسابقه',
                'tools.code_assign' => 'کد دهی',
                'tools.code_reader' => 'کدخوان',
                'tools.number_assign' => 'شماره دهی',
                'tools.content' => 'تولیدمحتوا',
                'tools.special_list' => 'لیست ویژه',
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
                'pro.quran' => 'ختم قرآن',
                'pro.fax' => 'پیامک فکس',
                'pro.subusers' => 'کاربران',
                'pro.business_card' => 'کارت ویزیت',
            ],
        ],
        'developers' => [
            'label' => 'توسعه دهندگان',
            'items' => [
                'developers.webservice_log' => 'لاگ فراخوانی وبسرویس',
                'developers.api' => 'وب سرویس و API',
                'developers.webservice_settings' => 'تنظیمات وبسرویس',
                'developers.traffic_transfer' => 'انتقال ترافیک',
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
                'settings.report_detailed' => 'گزارش ارسال تفکیکی',
                'settings.report_summary' => 'گزارش کلی ارسال و هزینه جدید',
                'settings.package_change' => 'ارتقا/کاهش بسته',
                'settings.quick_texts' => 'متون سریع',
                'settings.messenger' => 'پیام‌رسان جدید',
            ],
        ],
    ];

    /**
     * Flat list of every feature, ready for FeaturesSeeder.
     *
     * @return list<array{key: string, group_key: string, group_label: string, label: string, route: ?string, sort: int}>
     */
    public static function all(): array
    {
        $rows = [];
        $sort = 0;

        foreach (self::GROUPS as $groupKey => $group) {
            foreach ($group['items'] as $key => $item) {
                $rows[] = [
                    'key' => $key,
                    'group_key' => $groupKey,
                    'group_label' => $group['label'],
                    'label' => is_array($item) ? $item['label'] : $item,
                    'route' => is_array($item) ? ($item['route'] ?? null) : null,
                    'sort' => $sort,
                ];
                $sort += 10;
            }
        }

        return $rows;
    }
}
