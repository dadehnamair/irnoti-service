<?php

namespace Database\Seeders;

use App\Models\SmsLine;
use Illuminate\Database\Seeder;

class SmsLinesSeeder extends Seeder
{
    /**
     * The dedicated-line catalogue that used to be a hard-coded string list in
     * resources/views/landing.blade.php (docs/starter.md §9). Editable from the
     * Filament admin panel afterwards. Safe to re-run: matched by prefix+digits.
     */
    public function run(): void
    {
        $common = ['ارسال از پنل و API', 'تحویل فوری پس از تأیید', 'قابلیت ارسال انبوه'];

        $lines = [
            ['prefix' => '1000', 'operator' => 'مگفا', 'digits' => 8, 'line_type' => 'dedicated', 'price' => 1050000, 'is_rond' => false, 'sort' => 1],
            ['prefix' => '1000', 'operator' => 'مگفا', 'digits' => 9, 'line_type' => 'dedicated', 'price' => 780000, 'is_rond' => false, 'sort' => 2],
            ['prefix' => '2000', 'operator' => 'ایده‌آل', 'digits' => 8, 'line_type' => 'dedicated', 'price' => 2400000, 'is_rond' => true, 'sort' => 3],
            ['prefix' => '3000', 'operator' => 'آسیاتک', 'digits' => 10, 'line_type' => 'dedicated', 'price' => 390000, 'is_rond' => false, 'sort' => 4],
            ['prefix' => '3000', 'operator' => 'آسیاتک', 'digits' => 12, 'line_type' => 'dedicated', 'price' => 190000, 'is_rond' => false, 'sort' => 5],
            ['prefix' => '5000', 'operator' => 'مگفا', 'digits' => 10, 'line_type' => 'dedicated', 'price' => 320000, 'is_rond' => false, 'sort' => 6],
            ['prefix' => '50001', 'operator' => 'مگفا', 'digits' => 13, 'line_type' => 'shared', 'price' => 120000, 'is_rond' => false, 'sort' => 7],
            ['prefix' => '50004', 'operator' => 'مگفا', 'digits' => 14, 'line_type' => 'shared', 'price' => 90000, 'is_rond' => false, 'sort' => 8],
            ['prefix' => '021', 'operator' => 'تلفن ثابت', 'digits' => 8, 'line_type' => 'service', 'price' => 1500000, 'is_rond' => true, 'sort' => 9],
            ['prefix' => '021', 'operator' => 'تلفن ثابت', 'digits' => 4, 'line_type' => 'service', 'price' => 0, 'is_rond' => true, 'requires_inquiry' => true, 'sort' => 10],
            ['prefix' => '026', 'operator' => 'تلفن ثابت', 'digits' => 8, 'line_type' => 'service', 'price' => 850000, 'is_rond' => false, 'sort' => 11],
            ['prefix' => '041', 'operator' => 'تلفن ثابت', 'digits' => 8, 'line_type' => 'service', 'price' => 850000, 'is_rond' => false, 'sort' => 12],
            ['prefix' => '051', 'operator' => 'تلفن ثابت', 'digits' => 8, 'line_type' => 'service', 'price' => 850000, 'is_rond' => false, 'sort' => 13],
            ['prefix' => '071', 'operator' => 'تلفن ثابت', 'digits' => 8, 'line_type' => 'service', 'price' => 850000, 'is_rond' => false, 'sort' => 14],
            ['prefix' => '217000', 'operator' => 'مگفا', 'digits' => 12, 'line_type' => 'shared', 'price' => 150000, 'is_rond' => false, 'sort' => 15],
            ['prefix' => '9000', 'operator' => 'رایتل', 'digits' => 11, 'line_type' => 'dedicated', 'price' => 260000, 'is_rond' => false, 'sort' => 16],
            ['prefix' => '9999', 'operator' => 'همراه اول', 'digits' => 12, 'line_type' => 'dedicated', 'price' => 300000, 'is_rond' => true, 'sort' => 17],
            ['prefix' => '998', 'operator' => 'ایرانسل', 'digits' => 12, 'line_type' => 'shared', 'price' => 110000, 'is_rond' => false, 'sort' => 18],
        ];

        foreach ($lines as $line) {
            SmsLine::updateOrCreate(
                ['prefix' => $line['prefix'], 'digits' => $line['digits']],
                array_merge([
                    'features' => $common,
                    'sale_status' => 'available',
                    'is_active' => true,
                    'requires_inquiry' => false,
                    'description' => 'خط '.$line['prefix'].' مناسب اطلاع‌رسانی و تبلیغات کسب‌وکار.',
                ], $line),
            );
        }
    }
}
