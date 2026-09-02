<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendSmsJob;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Mobile-first registration + OTP login (docs/starter.md §26 / §27).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_screens_render(): void
    {
        $this->get('/register')->assertOk()->assertSee('ساخت حساب کاربری');
        $this->get('/login')->assertOk()->assertSee('ورود به حساب کاربری');
    }

    public function test_submitting_mobile_creates_pending_user_and_queues_code(): void
    {
        Bus::fake();

        $this->post('/register', ['mobile' => '09121112233'])
            ->assertRedirect(route('otp.show'));

        $user = User::where('mobile', '09121112233')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->status);
        $this->assertNull($user->mobile_verified_at);

        $this->assertDatabaseHas('otp_codes', ['mobile' => '09121112233', 'purpose' => 'register']);
        Bus::assertDispatched(SendSmsJob::class);
    }

    public function test_leading_zero_is_optional_on_input(): void
    {
        $this->post('/register', ['mobile' => '9121112244'])
            ->assertRedirect(route('otp.show'));

        $this->assertDatabaseHas('users', ['mobile' => '09121112244']);
    }

    public function test_correct_code_verifies_activates_and_logs_in(): void
    {
        $user = User::factory()->pending()->create(['mobile' => '09120000001']);
        [$code] = OtpCode::issue('09120000001', 'register');

        $response = $this->withSession(['otp' => ['mobile' => '09120000001', 'purpose' => 'register']])
            ->post('/verify', ['code' => $code]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_wrong_code_is_rejected(): void
    {
        User::factory()->pending()->create(['mobile' => '09120000002']);
        OtpCode::issue('09120000002', 'register');

        $this->withSession(['otp' => ['mobile' => '09120000002', 'purpose' => 'register']])
            ->post('/verify', ['code' => '00000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        User::factory()->pending()->create(['mobile' => '09120000003']);
        [$code, $otp] = OtpCode::issue('09120000003', 'register');
        $otp->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withSession(['otp' => ['mobile' => '09120000003', 'purpose' => 'register']])
            ->post('/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_resend_is_throttled_within_the_window(): void
    {
        User::factory()->pending()->create(['mobile' => '09120000004']);
        OtpCode::issue('09120000004', 'register'); // last_sent_at = now()

        $this->withSession(['otp' => ['mobile' => '09120000004', 'purpose' => 'register']])
            ->post('/verify/resend')
            ->assertSessionHasErrors('code');

        $this->assertSame(1, OtpCode::where('mobile', '09120000004')->count());
    }

    public function test_password_login_works_when_set(): void
    {
        $user = User::factory()->create(['mobile' => '09120000005', 'password' => bcrypt('secret123')]);

        $this->post('/login', ['mobile' => '09120000005', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_otp_login_request_starts_the_code_flow(): void
    {
        Bus::fake();
        User::factory()->create(['mobile' => '09120000006']);

        $this->post('/login/otp', ['mobile' => '09120000006'])
            ->assertRedirect(route('otp.show'))
            ->assertSessionHas('otp', ['mobile' => '09120000006', 'purpose' => 'login']);

        Bus::assertDispatched(SendSmsJob::class);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
