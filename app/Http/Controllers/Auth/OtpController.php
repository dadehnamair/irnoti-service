<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Step 2 of registration and the OTP-login path (docs/starter.md §26/§27):
 * verify the code, then log the user in. The pending mobile + purpose live in
 * the session (`otp` key), set by {@see RegisterController} or {@see LoginController}.
 */
class OtpController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return redirect()->route('register');
        }

        $active = OtpCode::active($otp['mobile'], $otp['purpose']);

        return view('auth.verify', [
            'mobile' => $otp['mobile'],
            'purpose' => $otp['purpose'],
            'resendIn' => $active?->secondsUntilResend() ?? 0,
        ]);
    }

    public function verify(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $session = $request->session()->get('otp');

        if (! $session) {
            return redirect()->route('register');
        }

        $request->validate([
            'code' => ['required', 'digits:5'],
        ], [], ['code' => 'کد تأیید']);

        $otp = OtpCode::active($session['mobile'], $session['purpose']);

        if (! $otp || ! $otp->verify($request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => 'کد واردشده نادرست یا منقضی شده است.',
            ]);
        }

        $user = User::where('mobile', $session['mobile'])->first();

        if (! $user) {
            return redirect()->route('register')->withErrors(['mobile' => 'حساب کاربری یافت نشد.']);
        }

        if (in_array($user->status, ['suspended', 'blocked'], true)) {
            $request->session()->forget('otp');

            return redirect()->route('login')->with('auth_error', 'دسترسی این حساب محدود شده است. با پشتیبانی تماس بگیرید.');
        }

        $firstVerification = $user->mobile_verified_at === null;

        $user->forceFill([
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            'status' => $user->status === 'pending' ? 'active' : $user->status,
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget('otp');

        if ($firstVerification && $session['purpose'] === 'register') {
            $notifier->userRegistered($user);
        }

        return redirect()->intended($this->destination());
    }

    public function resend(Request $request): RedirectResponse
    {
        $otp = $request->session()->get('otp');

        if (! $otp) {
            return redirect()->route('register');
        }

        RegisterController::sendCode($otp['mobile'], $otp['purpose']);

        return redirect()->route('otp.show')->with('auth_status', 'کد جدید ارسال شد.');
    }

    /** Where to land after login — the plan chosen on /pricing wins, else the dashboard. */
    private function destination(): string
    {
        if (session('intended_plan') && \Illuminate\Support\Facades\Route::has('dashboard.plan.checkout')) {
            $slug = session('intended_plan')['slug'] ?? null;

            if ($slug && ($plan = \App\Models\Plan::where('slug', $slug)->first())) {
                return route('dashboard.plan.checkout', $plan);
            }
        }

        return route('dashboard');
    }
}
