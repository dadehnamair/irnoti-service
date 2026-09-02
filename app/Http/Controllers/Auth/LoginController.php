<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login (docs/starter.md §27): mobile + password when the user has set one,
 * otherwise "send me a code" which hands off to {@see OtpController}. Filament's
 * /admin login is separate and untouched.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /** Password login. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^0?9\d{9}$/'],
            'password' => ['required', 'string'],
        ], [], ['mobile' => 'شماره موبایل', 'password' => 'رمز عبور']);

        $mobile = $this->normalizeMobile($data['mobile']);
        $remember = $request->boolean('remember');

        if (! Auth::attempt(['mobile' => $mobile, 'password' => $data['password']], $remember)) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل یا رمز عبور نادرست است.',
            ]);
        }

        $user = Auth::user();

        if (in_array($user->status, ['suspended', 'blocked'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'mobile' => 'دسترسی این حساب محدود شده است. با پشتیبانی تماس بگیرید.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** "Send me a one-time code" — start the OTP-login flow. */
    public function requestOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^0?9\d{9}$/'],
        ], [], ['mobile' => 'شماره موبایل']);

        $mobile = $this->normalizeMobile($data['mobile']);
        $user = User::where('mobile', $mobile)->first();

        if (! $user) {
            return redirect()->route('register')
                ->with('auth_status', 'حسابی با این شماره یافت نشد. ابتدا ثبت‌نام کنید.')
                ->withInput(['mobile' => $mobile]);
        }

        RegisterController::sendCode($mobile, 'login');
        $request->session()->put('otp', ['mobile' => $mobile, 'purpose' => 'login']);

        return redirect()->route('otp.show');
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        return str_starts_with($digits, '9') && strlen($digits) === 10 ? '0'.$digits : $digits;
    }
}
