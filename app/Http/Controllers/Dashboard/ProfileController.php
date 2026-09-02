<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Stepped identity-profile completion (docs/starter.md §26). Each step validates
 * and persists only its own slice, so the user can leave and resume. Step 3
 * marks the profile complete and notifies the admin.
 */
class ProfileController extends Controller
{
    private const LAST_STEP = 3;

    private const STEP_LABELS = [
        1 => 'اطلاعات فردی',
        2 => 'موقعیت و آدرس',
        3 => 'احراز هویت',
    ];

    /** Send the user to the first step that still needs work. */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard.profile.step', ['step' => $this->firstIncompleteStep($request->user())]);
    }

    public function edit(Request $request, int $step): View|RedirectResponse
    {
        if ($step < 1 || $step > self::LAST_STEP) {
            return redirect()->route('dashboard.profile');
        }

        return view('dashboard.profile', [
            'user' => $request->user(),
            'step' => $step,
            'lastStep' => self::LAST_STEP,
            'stepLabels' => self::STEP_LABELS,
            'countries' => config('geo.countries'),
            'provinces' => config('geo.provinces'),
        ]);
    }

    public function update(Request $request, int $step, OperationNotifier $notifier): RedirectResponse
    {
        $user = $request->user();

        match ($step) {
            1 => $this->saveIdentity($request, $user),
            2 => $this->saveLocation($request, $user),
            3 => $this->saveVerification($request, $user),
            default => abort(404),
        };

        if ($step === self::LAST_STEP) {
            $firstTime = $user->profile_completed_at === null;
            $user->forceFill(['profile_completed_at' => now()])->save();

            if ($user->status === 'pending') {
                $user->forceFill(['status' => 'active'])->save();
            }

            if ($firstTime) {
                $notifier->profileCompleted($user);
            }

            return redirect()->route('dashboard')->with('auth_status', 'اطلاعات حساب شما تکمیل شد.');
        }

        return redirect()->route('dashboard.profile.step', ['step' => $step + 1])
            ->with('auth_status', 'مرحله ذخیره شد.');
    }

    /** Step 1 — اطلاعات فردی (+ optional password). */
    private function saveIdentity(Request $request, $user): void
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:100'],
        ], [], [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'phone' => 'شماره تماس',
            'password' => 'رمز عبور',
        ]);

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model cast
        }

        $user->save();
    }

    /** Step 2 — کشور / استان / شهر / آدرس / کد پستی / توضیحات. */
    private function saveLocation(Request $request, $user): void
    {
        $data = $request->validate([
            'country' => ['required', 'string', Rule::in(config('geo.countries'))],
            'province' => ['nullable', 'string', Rule::in(config('geo.provinces'))],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'digits:10'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'country' => 'کشور',
            'province' => 'استان',
            'city' => 'شهر',
            'address' => 'آدرس',
            'postal_code' => 'کد پستی',
            'description' => 'توضیحات',
        ]);

        $user->fill($data)->save();
    }

    /** Step 3 — کد ملی / ش.ش. / تصاویر مدارک (private disk). */
    private function saveVerification(Request $request, $user): void
    {
        $data = $request->validate([
            'national_code' => ['nullable', 'digits:10'],
            'birth_cert_number' => ['nullable', 'string', 'max:20'],
            'national_card_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'national_card_back_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'identity_doc_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], [
            'national_code' => 'کد ملی',
            'birth_cert_number' => 'شماره شناسنامه',
            'national_card_image' => 'تصویر کارت ملی',
            'national_card_back_image' => 'تصویر پشت کارت ملی',
            'identity_doc_image' => 'تصویر احراز هویت',
        ]);

        $user->fill([
            'national_code' => $data['national_code'] ?? $user->national_code,
            'birth_cert_number' => $data['birth_cert_number'] ?? $user->birth_cert_number,
        ]);

        foreach (['national_card_image', 'national_card_back_image', 'identity_doc_image'] as $field) {
            if ($request->hasFile($field)) {
                $user->{$field} = $request->file($field)->store("identity/{$user->id}", 'local');
            }
        }

        $user->save();
    }

    private function firstIncompleteStep($user): int
    {
        if (blank($user->first_name) || blank($user->last_name)) {
            return 1;
        }

        if (blank($user->province) && blank($user->address)) {
            return 2;
        }

        return $user->profile_completed_at ? 1 : 3;
    }
}
