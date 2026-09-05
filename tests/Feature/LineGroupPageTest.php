<?php

namespace Tests\Feature;

use App\Models\LineBundle;
use App\Models\LineGroup;
use App\Models\LineOrder;
use App\Models\Setting;
use App\Models\SmsLine;
use App\Models\User;
use App\Support\PayableSettlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LineGroupPageTest extends TestCase
{
    use RefreshDatabase;

    private function group(array $overrides = []): LineGroup
    {
        return LineGroup::create(array_merge([
            'prefix' => '3000',
            'title' => 'خط اختصاصی ۳۰۰۰',
            'tagline' => 'محبوب‌ترین خط اختصاصی پیامک',
            'body' => 'متن توضیحات خط سه‌هزار.',
            'features' => ['ارسال انبوه', 'API'],
            'use_cases' => ['فروشگاه‌ها'],
            'faqs' => [['q' => 'زمان فعال‌سازی؟', 'a' => 'همان روز کاری.']],
            'is_active' => true,
        ], $overrides));
    }

    private function line(LineGroup $group, array $overrides = []): SmsLine
    {
        return SmsLine::create(array_merge([
            'line_group_id' => $group->id,
            'prefix' => $group->prefix,
            'operator' => 'آسیاتک',
            'digits' => 10,
            'line_type' => 'dedicated',
            'price' => 390000,
            'sale_status' => 'available',
            'is_active' => true,
        ], $overrides));
    }

    private function bundle(LineGroup $group, ?SmsLine $line = null, array $overrides = []): LineBundle
    {
        return LineBundle::create(array_merge([
            'line_group_id' => $group->id,
            'sms_line_id' => $line?->id,
            'title' => 'باندل شروع خط ۳۰۰۰',
            'description' => 'خط + ۵٬۰۰۰ پیامک',
            'sms_credit' => 5000,
            'validity_days' => 365,
            'price' => 690000,
            'is_active' => true,
        ], $overrides));
    }

    private function setOnlinePayment(bool $on): void
    {
        Setting::updateOrCreate(['key' => 'line_payment_online'], [
            'value' => $on ? '1' : '0',
            'type' => 'bool',
            'group' => 'commerce',
        ]);
        Cache::forget(Setting::CACHE_KEY);
    }

    public function test_landing_page_renders_with_lines_bundles_and_jsonld(): void
    {
        $group = $this->group();
        $line = $this->line($group);
        $this->bundle($group, $line);

        $html = $this->get('/lines/3000')->assertOk()
            ->assertSee('خط اختصاصی ۳۰۰۰')
            ->assertSee('باندل شروع خط ۳۰۰۰')
            ->assertSee('گونه‌ها و قیمت خط 3000')
            ->getContent();

        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
    }

    public function test_inactive_group_is_not_found(): void
    {
        $this->group(['is_active' => false]);

        $this->get('/lines/3000')->assertNotFound();
    }

    public function test_bundle_checkout_renders(): void
    {
        $group = $this->group();
        $bundle = $this->bundle($group, $this->line($group));

        $this->get(route('lines.bundle.checkout', [$group, $bundle]))
            ->assertOk()
            ->assertSee('خرید باندل شروع خط ۳۰۰۰');
    }

    public function test_bundle_order_snapshots_bundle_and_awaits_payment(): void
    {
        $this->setOnlinePayment(false);
        $group = $this->group();
        $bundle = $this->bundle($group, $this->line($group));

        $response = $this->post('/lines/order', [
            'line_bundle_id' => $bundle->id,
            'customer_name' => 'علی تست',
            'customer_phone' => '09120000000',
        ]);

        $order = LineOrder::firstOrFail();
        $response->assertRedirect(route('lines.track', $order));
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertSame($bundle->id, $order->line_bundle_id);
        $this->assertSame('باندل شروع خط ۳۰۰۰', $order->bundle_label);
        $this->assertSame(5000, $order->sms_credit);
        $this->assertSame(690000, $order->price);
    }

    public function test_settling_a_bundle_order_credits_sms_once(): void
    {
        $group = $this->group();
        $bundle = $this->bundle($group, $this->line($group));
        $user = User::factory()->create(['sms_credit' => 100]);

        $order = LineOrder::create([
            'user_id' => $user->id,
            'sms_line_id' => $bundle->sms_line_id,
            'line_bundle_id' => $bundle->id,
            'line_label' => 'خطوط 3000',
            'bundle_label' => $bundle->title,
            'price' => $bundle->price,
            'sms_credit' => $bundle->sms_credit,
            'customer_name' => 'x',
            'customer_phone' => '0912',
            'status' => 'awaiting_payment',
        ]);

        $settlement = app(PayableSettlement::class);
        $settlement->settle($order, ['method' => 'wallet']);
        $settlement->settle($order, ['method' => 'wallet']); // idempotent

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(5100, $user->fresh()->sms_credit);
    }

    public function test_sitemap_lists_line_group(): void
    {
        $this->group();

        $this->get('/sitemap.xml')->assertOk()->assertSee(route('lines.group', '3000'));
    }
}
