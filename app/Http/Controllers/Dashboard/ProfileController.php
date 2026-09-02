<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Stepped identity-profile completion (docs/starter.md §26). Each step validates
 * and persists only its own slice, so the user can leave and resume. Step 1 also
 * chooses whether the account is a natural person (حقیقی) or a registered company
 * (حقوقی) — a legal account then fills the company registration data in step 1
 * and uploads the company gazette documents in step 3, on top of the signing
 * representative's own identity fields. Step 3 marks the profile complete and
 * notifies the admin.
 */
class ProfileController extends Controller
{
    private const LAST_STEP = 3;

    private const STEP_LABELS = [
        1 => 'اطلاعات فردی',
        2 => 'موقعیت و آدرس',
        3 => 'احراز هویت',
    ];

    /** Company gazette / extra documents live next to the representative's ID images. */
    private const COMPANY_DOC_MIMES = 'jpg,jpeg,png,webp,pdf';

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
            'accountTypes' => User::ACCOUNT_TYPES,
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

            // Completing the profile only moves the account toward approval — an
            // admin still has to flip it to "active" (docs/starter.md §39).
            $wasAwaiting = $user->status === 'awaiting_approval';
            $user->refreshApprovalState();

            if ($firstTime) {
                $notifier->profileCompleted($user);
            }

            if (! $wasAwaiting && $user->status === 'awaiting_approval') {
                $notifier->awaitingApproval($user);
            }

            return redirect()->route('dashboard')->with('auth_status', 'اطلاعات حساب شما تکمیل شد و در انتظار تأیید کارشناسان است.');
        }

        return redirect()->route('dashboard.profile.step', ['step' => $step + 1])
            ->with('auth_status', 'مرحله ذخیره شد.');
    }

    /** Step 1 — نوع حساب + اطلاعات فردی/نماینده + (برای حقوقی) اطلاعات ثبت شرکت + رمز اختیاری. */
    private function saveIdentity(Request $request, User $user): void
    {
        $locked = $user->identityLocked();

        // The account type can't switch once the account is approved.
        $accountType = $locked
            ? ($user->account_type ?: 'individual')
            : $request->input('account_type', $user->account_type ?: 'individual');
        $isLegal = $accountType === 'legal';

        $rules = [
            'account_type' => ['sometimes', Rule::in(array_keys(User::ACCOUNT_TYPES))],
            // نام/نام‌خانوادگی = خودِ شخص حقیقی یا نمایندهٔ امضاکنندهٔ شرکت.
            'first_name' => [$locked ? 'nullable' : 'required', 'string', 'max:120'],
            'last_name' => [$locked ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:100'],
        ];
        $attributes = [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'phone' => 'شماره تماس',
            'password' => 'رمز عبور',
        ];

        if ($isLegal) {
            $companyRule = $locked ? 'nullable' : 'required';
            $rules += [
                'company' => [$companyRule, 'string', 'max:200'],
                'company_type' => [$companyRule, 'string', 'max:120'],
                'company_national_id' => [$companyRule, 'digits:11'],
                'company_registration_number' => [$companyRule, 'string', 'max:40'],
                'company_registered_at' => ['nullable', 'string', 'max:20'],
                'company_economic_code' => ['nullable', 'string', 'max:30'],
                'company_phone' => ['nullable', 'string', 'max:30'],
                'company_postal_code' => ['nullable', 'digits:10'],
                'company_address' => ['nullable', 'string', 'max:1000'],
                'rep_role' => ['nullable', 'string', 'max:120'],
            ];
            $attributes += [
                'company' => 'نام شرکت',
                'company_type' => 'نوع شخصیت حقوقی',
                'company_national_id' => 'شناسه ملی',
                'company_registration_number' => 'شماره ثبت',
                'company_registered_at' => 'تاریخ ثبت',
                'company_economic_code' => 'کد اقتصادی',
                'company_phone' => 'تلفن شرکت',
                'company_postal_code' => 'کد پستی شرکت',
                'company_address' => 'نشانی شرکت',
                'rep_role' => 'سمت نماینده',
            ];
        } else {
            $rules['company'] = ['nullable', 'string', 'max:160'];
            $attributes['company'] = 'شرکت';
        }

        $data = $request->validate($rules, [], $attributes);

        if (! $locked) {
            $user->account_type = $accountType;
            $user->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]);
        }

        $user->fill([
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? null,
        ]);

        if ($isLegal) {
            // اطلاعات ثبتیِ شرکت پس از تأیید حساب قفل می‌شود؛ اطلاعات تماس شرکت آزاد می‌ماند.
            if (! $locked) {
                $user->fill([
                    'company' => $data['company'] ?? null,
                    'company_type' => $data['company_type'] ?? null,
                    'company_national_id' => $data['company_national_id'] ?? null,
                    'company_registration_number' => $data['company_registration_number'] ?? null,
                    'company_registered_at' => $data['company_registered_at'] ?? null,
                    'company_economic_code' => $data['company_economic_code'] ?? null,
                ]);
            }

            $user->fill([
                'company_phone' => $data['company_phone'] ?? null,
                'company_postal_code' => $data['company_postal_code'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'rep_role' => $data['rep_role'] ?? null,
            ]);
        } else {
            $user->company = $data['company'] ?? null;
        }

        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model cast
        }

        $user->save();
    }

    /** Step 2 — کشور / استان / شهر / آدرس / کد پستی / توضیحات. */
    private function saveLocation(Request $request, User $user): void
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

    /** Step 3 — کد ملی / ش.ش. / تصاویر مدارک نماینده + (برای حقوقی) مدارک شرکت (private disk). */
    private function saveVerification(Request $request, User $user): void
    {
        $identityLocked = $user->identityLocked();
        $docsLocked = $user->documentsLocked();
        $isLegal = $user->isLegal();

        $rules = [
            'national_code' => ['nullable', 'digits:10'],
            'birth_cert_number' => ['nullable', 'string', 'max:20'],
            'national_card_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'national_card_back_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'identity_doc_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
        $attributes = [
            'national_code' => 'کد ملی',
            'birth_cert_number' => 'شماره شناسنامه',
            'national_card_image' => 'تصویر کارت ملی',
            'national_card_back_image' => 'تصویر پشت کارت ملی',
            'identity_doc_image' => 'تصویر احراز هویت',
        ];

        if ($isLegal) {
            // آگهی تأسیس تنها وقتی الزامی است که هنوز آپلود نشده باشد و مدارک قفل نباشد.
            $needsRegistrationDoc = ! $docsLocked && blank($user->company_registration_doc);
            $rules += [
                'company_registration_doc' => [$needsRegistrationDoc ? 'required' : 'nullable', 'file', 'mimes:'.self::COMPANY_DOC_MIMES, 'max:6144'],
                'company_changes_doc' => ['nullable', 'file', 'mimes:'.self::COMPANY_DOC_MIMES, 'max:6144'],
                'company_extra_docs' => ['nullable', 'array', 'max:8'],
                'company_extra_docs.*' => ['file', 'mimes:'.self::COMPANY_DOC_MIMES, 'max:6144'],
            ];
            $attributes += [
                'company_registration_doc' => 'آگهی تأسیس / روزنامه رسمی',
                'company_changes_doc' => 'آگهی آخرین تغییرات',
                'company_extra_docs' => 'مدارک اضافه',
                'company_extra_docs.*' => 'مدرک',
            ];
        }

        $data = $request->validate($rules, [], $attributes);

        // کد ملی / ش.ش. جزو اطلاعات هویتی‌اند و بعد از تأیید قفل می‌شوند (docs/starter.md §39).
        if (! $identityLocked) {
            $user->fill([
                'national_code' => $data['national_code'] ?? $user->national_code,
                'birth_cert_number' => $data['birth_cert_number'] ?? $user->birth_cert_number,
            ]);
        }

        $uploaded = false;

        if (! $docsLocked) {
            foreach (['national_card_image', 'national_card_back_image', 'identity_doc_image'] as $field) {
                if ($request->hasFile($field)) {
                    $user->{$field} = $request->file($field)->store("identity/{$user->id}", 'local');
                    $uploaded = true;
                }
            }

            if ($isLegal) {
                foreach (['company_registration_doc', 'company_changes_doc'] as $field) {
                    if ($request->hasFile($field)) {
                        $user->{$field} = $request->file($field)->store("identity/{$user->id}/company", 'local');
                        $uploaded = true;
                    }
                }

                if ($request->hasFile('company_extra_docs')) {
                    $extra = array_values(array_filter((array) $user->company_extra_docs));
                    foreach ($request->file('company_extra_docs') as $file) {
                        $extra[] = $file->store("identity/{$user->id}/company", 'local');
                    }
                    $user->company_extra_docs = $extra;
                    $uploaded = true;
                }
            }
        }

        // A fresh upload restarts the document review (docs/starter.md §26).
        if ($uploaded) {
            $user->fill([
                'documents_status' => 'pending',
                'documents_reviewed_at' => null,
                'documents_reject_reason' => null,
            ]);
        }

        $user->save();
    }

    private function firstIncompleteStep(User $user): int
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
