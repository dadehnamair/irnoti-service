<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\OtpCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

/**
 * Step 1 of registration (docs/starter.md §26): the visitor gives only a mobile
 * number. We create a `pending` account, text a one-time code, and hand off to
 * {@see OtpController} for verification. The identity profile is completed later
 * from the dashboard.
 */
class RegisterController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->registrationOpen()) {
            return redirect()->route('home')->with('auth_error', 'ثبت‌نام موقتاً غیرفعال است.');
        }

        // Carry a plan chosen on /pricing through the OTP flow (docs/starter.md §8).
        if ($request->filled('plan')) {
            session(['intended_plan' => [
                'slug' => (string) $request->string('plan'),
                'period' => in_array($request->string('period')->toString(), ['monthly', 'yearly'], true)
                    ? $request->string('period')->toString()
                    : 'monthly',
            ]]);
        }

        return view('auth.register', [
            'intendedPlan' => session('intended_plan'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->registrationOpen()) {
            return redirect()->route('home')->with('auth_error', 'ثبت‌نام موقتاً غیرفعال است.');
        }

        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^0?9\d{9}$/'],
        ], [], ['mobile' => 'شماره موبایل']);

        $mobile = $this->normalizeMobile($data['mobile']);

        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            ['status' => 'pending', 'country' => 'ایران'],
        );

        if ($user->mobile_verified_at !== null) {
            return redirect()->route('login')
                ->with('auth_status', 'این شماره قبلاً ثبت شده است. برای ورود از کد یک‌بارمصرف استفاده کنید.')
                ->withInput(['mobile' => $mobile]);
        }

        $this->sendCode($mobile, 'register');

        session(['otp' => ['mobile' => $mobile, 'purpose' => 'register']]);

        return redirect()->route('otp.show');
    }

    /**
     * Issue a fresh code (or reuse the throttle window) and queue the SMS.
     * Shared by registration and OTP-login resends.
     */
    public static function sendCode(string $mobile, string $purpose): void
    {
        $active = OtpCode::active($mobile, $purpose);

        if ($active && $active->secondsUntilResend() > 0) {
            throw ValidationException::withMessages([
                'code' => "تا ارسال مجدد کد {$active->secondsUntilResend()} ثانیه صبر کنید.",
            ]);
        }

        [$plain] = OtpCode::issue($mobile, $purpose);

        $brand = config('theme.brand', 'irnoti');
        dispatch(SendSmsJob::text($mobile, "کد ورود شما به {$brand}: {$plain}"));

        // Dev convenience: the log driver already logs it; also flash it locally.
        if (App::environment('local')) {
            session()->flash('otp_debug', $plain);
        }
    }

    private function registrationOpen(): bool
    {
        return (bool) Setting::get('registration_enabled', true);
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        return str_starts_with($digits, '9') && strlen($digits) === 10 ? '0'.$digits : $digits;
    }
}
