<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;

/**
 * One placeholder company bank account for the "ثبت فیش" screen (docs/starter.md
 * §22). Real values are set by the admin in Filament. Idempotent.
 */
class BankAccountsSeeder extends Seeder
{
    public function run(): void
    {
        BankAccount::updateOrCreate(
            ['bank_name' => env('BANK_NAME', 'بانک ملت')],
            [
                'owner_name' => env('BANK_OWNER', config('theme.brand', 'irnoti')),
                'card_number' => env('BANK_CARD', ''),
                'sheba' => env('BANK_SHEBA', ''),
                'account_number' => env('BANK_ACCOUNT', ''),
                'note' => 'پس از واریز، مشخصات فیش را از پنل کاربری ثبت کنید.',
                'is_active' => true,
                'sort' => 0,
            ],
        );
    }
}
