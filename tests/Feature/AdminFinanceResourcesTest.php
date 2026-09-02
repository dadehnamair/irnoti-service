<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminFinanceResourcesTest extends TestCase
{
    use RefreshDatabase;

    public static function financePages(): array
    {
        return [
            ['/admin/sms-packages'],
            ['/admin/bank-accounts'],
            ['/admin/bank-receipts'],
            ['/admin/package-orders'],
            ['/admin/invoices'],
            ['/admin/invoices/create'],
            ['/admin/wallet-transactions'],
        ];
    }

    #[DataProvider('financePages')]
    public function test_admin_finance_pages_load(string $url): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get($url)->assertOk();
    }
}
