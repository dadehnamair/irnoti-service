<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Filament admin pages for the site-content subsystem (docs/site-content.md)
 * and the adjacent sales-representation subsystem (docs/sales-representation.md).
 */
class AdminSiteContentResourcesTest extends TestCase
{
    use RefreshDatabase;

    public static function listAndCreatePages(): array
    {
        return [
            ['/admin/faqs'],
            ['/admin/faqs/create'],
            ['/admin/site-features'],
            ['/admin/site-features/create'],
            ['/admin/pages'],
            ['/admin/pages/create'],
            ['/admin/representation-tiers'],
            ['/admin/representation-tiers/create'],
            ['/admin/representation-applications'],
            ['/admin/contact-messages'],
        ];
    }

    #[DataProvider('listAndCreatePages')]
    public function test_admin_site_content_pages_load(string $url): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_representation_application_cannot_be_created_from_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/representation-applications/create')->assertNotFound();
    }

    public function test_contact_message_cannot_be_created_from_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/contact-messages/create')->assertNotFound();
    }
}
