<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ContactGroup;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\Phonebook\UserPhonebook;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Basic SMS panel for an approved customer (docs/starter.md §12): single send,
 * send history, the panel credit, and the dedicated sender numbers (سرشماره)
 * pulled from the customer's own SMS panel account ({@see UserSmsGateway}).
 */
class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Live panel credit (cached 60s); shared with the account sidebar card.
        ['sms' => $credit, 'rial' => $creditRial, 'error' => $creditError] = $user->smsPanelCredit();

        if ($user->hasSmsPanel()) {
            // Refresh the cached سرشماره list in the background when it's stale;
            // the page must still render if the read fails.
            if ($user->smsNumbersAreStale()) {
                try {
                    $this->syncNumbers($user);
                } catch (\Throwable $e) {
                    Log::warning('SMS numbers sync failed', ['user' => $user->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return view('dashboard.sms', [
            'user' => $user,
            'hasPanel' => $user->hasSmsPanel(),
            'credit' => $credit,
            'creditRial' => $creditRial,
            'creditError' => $creditError,
            'numbers' => $user->availableSmsNumbers(),
            'defaultSender' => $user->sms_sender,
            'numbersSyncedAt' => $user->sms_numbers_synced_at,
            'messages' => SmsMessage::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasSmsPanel()) {
            return redirect()->route('dashboard.sms')
                ->with('sms_error', 'پنل پیامک شما هنوز فعال نشده است.');
        }

        $data = $request->validate([
            'to' => ['required', 'string', 'regex:/^(0|\+?98)?9\d{9}$/'],
            'from' => ['nullable', 'string', Rule::in($user->availableSmsNumbers())],
            'message' => ['required', 'string', 'max:600'],
        ], [
            'to.regex' => 'شمارهٔ گیرنده معتبر نیست (مثال: 09121234567).',
            'from.in' => 'سرشمارهٔ انتخاب‌شده متعلق به پنل شما نیست.',
        ], [
            'to' => 'گیرنده',
            'from' => 'سرشماره فرستنده',
            'message' => 'متن پیام',
        ]);

        $from = $data['from'] ?? $user->sms_sender;

        $message = SmsMessage::create([
            'user_id' => $user->id,
            'to' => $data['to'],
            'from' => $from,
            'body' => $data['message'],
            'parts' => max(1, (int) ceil(mb_strlen($data['message']) / 70)),
            'status' => 'queued',
        ]);

        try {
            $recId = UserSmsGateway::for($user)->send($data['to'], $data['message'], $from);
            $message->update(['status' => 'sent', 'rec_id' => $recId]);
            Cache::forget("sms_credit:{$user->id}");

            return redirect()->route('dashboard.sms')->with('sms_status', 'پیامک ارسال شد.');
        } catch (SmsPanelNotConfiguredException $e) {
            $message->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.sms')->with('sms_error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Customer SMS send failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            $message->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);

            return redirect()->route('dashboard.sms')
                ->with('sms_error', 'ارسال پیامک ناموفق بود: '.$e->getMessage());
        }
    }

    /* ------------------------- group send (docs/starter.md §17/§18) ------------------------- */

    /** Max recipients per "local" group send — anything larger must use the provider's group send. */
    private const LOCAL_BULK_CAP = 200;

    public function bulk(Request $request): View
    {
        abort_unless((bool) Setting::get('phonebook_enabled', true), 404);

        $user = $request->user();

        return view('dashboard.contacts.send', [
            'groups' => $user->contactGroups()->ordered()->withCount('contacts')->get(),
            'numbers' => $user->availableSmsNumbers(),
            'defaultSender' => $user->sms_sender,
            'hasPanel' => $user->hasSmsPanel(),
            'localCap' => self::LOCAL_BULK_CAP,
        ]);
    }

    public function sendBulk(Request $request): RedirectResponse
    {
        abort_unless((bool) Setting::get('phonebook_enabled', true), 404);

        $user = $request->user();

        if (! $user->hasSmsPanel()) {
            return redirect()->route('dashboard.contacts.send')
                ->with('sms_error', 'پنل پیامک شما هنوز فعال نشده است.');
        }

        $data = $request->validate([
            'mode' => ['required', Rule::in(['local', 'remote'])],
            'groups' => ['array'],
            'groups.*' => [Rule::exists('contact_groups', 'id')->where('user_id', $user->id)],
            'numbers' => ['nullable', 'string', 'max:20000'],
            'message' => ['required', 'string', 'max:600'],
            'from' => ['nullable', 'string', Rule::in($user->availableSmsNumbers())],
            'schedule_at' => ['nullable', 'date'],
        ], [
            'from.in' => 'سرشمارهٔ انتخاب‌شده متعلق به پنل شما نیست.',
        ], [
            'message' => 'متن پیام',
            'from' => 'سرشماره فرستنده',
            'schedule_at' => 'زمان ارسال',
        ]);

        $groups = $user->contactGroups()->whereKey($data['groups'] ?? [])->withCount('contacts')->get();
        $from = $data['from'] ?? $user->sms_sender;

        if ($groups->isEmpty() && blank($data['numbers'] ?? null)) {
            return back()->withInput()->with('sms_error', 'حداقل یک گروه یا فهرستی از شماره‌ها را وارد کنید.');
        }

        return $data['mode'] === 'remote'
            ? $this->sendBulkViaProvider($user, $groups, $data, $from)
            : $this->sendBulkLocally($user, $groups, $data, $from);
    }

    /** Resolve groups + pasted numbers to a de-duped list and send one-by-one via our gateway. */
    private function sendBulkLocally(User $user, $groups, array $data, ?string $from): RedirectResponse
    {
        $recipients = $groups
            ->flatMap(fn (ContactGroup $g) => $g->contacts()->pluck('mobile'))
            ->merge($this->parseNumbers($data['numbers'] ?? ''))
            ->map(fn ($m) => normalize_mobile($m))
            ->filter(fn ($m) => preg_match('/^09\d{9}$/', $m))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('sms_error', 'هیچ شمارهٔ معتبری برای ارسال پیدا نشد.');
        }

        if ($recipients->count() > self::LOCAL_BULK_CAP) {
            return back()->withInput()->with('sms_error', sprintf(
                'تعداد گیرندگان (%d) بیش از حد مجاز برای ارسال محلی است (%d). از حالت ارسال گروهی استفاده کنید.',
                $recipients->count(),
                self::LOCAL_BULK_CAP,
            ));
        }

        $parts = max(1, (int) ceil(mb_strlen($data['message']) / 70));
        $sent = 0;
        $failed = 0;

        try {
            $gateway = UserSmsGateway::for($user);
        } catch (SmsPanelNotConfiguredException $e) {
            return back()->withInput()->with('sms_error', $e->getMessage());
        }

        foreach ($recipients as $mobile) {
            $message = SmsMessage::create([
                'user_id' => $user->id,
                'to' => $mobile,
                'from' => $from,
                'body' => $data['message'],
                'parts' => $parts,
                'status' => 'queued',
            ]);

            try {
                $recId = $gateway->send($mobile, $data['message'], $from);
                $message->update(['status' => 'sent', 'rec_id' => $recId]);
                $sent++;
            } catch (\Throwable $e) {
                $message->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);
                $failed++;
            }
        }

        Cache::forget("sms_credit:{$user->id}");

        return redirect()->route('dashboard.contacts.send')->with(
            $failed > 0 ? 'warning' : 'sms_status',
            sprintf('ارسال گروهی انجام شد: %d موفق، %d ناموفق.', $sent, $failed),
        );
    }

    /** Hand the whole job to the provider's SendSmsToContact (remote group ids). */
    private function sendBulkViaProvider(User $user, $groups, array $data, ?string $from): RedirectResponse
    {
        $unsynced = $groups->whereNull('remote_id');

        if ($groups->isEmpty()) {
            return back()->withInput()->with('sms_error', 'برای ارسال گروهی باید حداقل یک گروه انتخاب کنید.');
        }

        if ($unsynced->isNotEmpty()) {
            return back()->withInput()->with('sms_error', 'همهٔ گروه‌های انتخاب‌شده باید با '.sms_provider_label().' همگام شده باشند: '.$unsynced->pluck('name')->implode('، '));
        }

        if ($groups->count() > 5) {
            return back()->withInput()->with('sms_error', 'حداکثر ۵ گروه در هر ارسال گروهی مجاز است.');
        }

        $message = SmsMessage::create([
            'user_id' => $user->id,
            'to' => 'گروه: '.$groups->pluck('name')->implode('، '),
            'from' => $from,
            'body' => $data['message'],
            'parts' => max(1, (int) ceil(mb_strlen($data['message']) / 70)),
            'status' => 'queued',
        ]);

        try {
            $bulkId = UserPhonebook::for($user)->sendToGroups(
                $groups->pluck('remote_id')->all(),
                $data['message'],
                $from,
                null,
                $this->providerSchedule($data['schedule_at'] ?? null),
            );

            $message->update(['status' => 'sent', 'rec_id' => $bulkId]);
            Cache::forget("sms_credit:{$user->id}");

            return redirect()->route('dashboard.contacts.send')
                ->with('sms_status', 'ارسال گروهی به '.sms_provider_label().' سپرده شد. کد پیگیری: '.$bulkId);
        } catch (\Throwable $e) {
            Log::error('[phonebook] group send failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            $message->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 250)]);

            return back()->withInput()->with('sms_error', 'ارسال گروهی ناموفق بود: '.$e->getMessage());
        }
    }

    /** @return array<int, string> */
    private function parseNumbers(string $raw): array
    {
        return array_values(array_filter(preg_split('/[\s,;]+/', trim($raw)) ?: []));
    }

    private function providerSchedule(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Pull the account's sender numbers from the SMS provider on demand. */
    public function refreshNumbers(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasSmsPanel()) {
            return redirect()->route('dashboard.sms')
                ->with('sms_error', 'پنل پیامک شما هنوز فعال نشده است.');
        }

        try {
            $this->syncNumbers($user);

            return redirect()->route('dashboard.sms')
                ->with('sms_status', 'فهرست سرشماره‌ها از '.sms_provider_label().' به‌روزرسانی شد.');
        } catch (\Throwable $e) {
            Log::warning('SMS numbers refresh failed', ['user' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.sms')
                ->with('sms_error', 'دریافت سرشماره‌ها ناموفق بود: '.$e->getMessage());
        }
    }

    /** Save the سرشماره the customer picked as their default sender line. */
    public function setDefaultSender(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'from' => ['required', 'string', Rule::in($user->availableSmsNumbers())],
        ], [
            'from.required' => 'یک سرشماره را انتخاب کنید.',
            'from.in' => 'سرشمارهٔ انتخاب‌شده متعلق به پنل شما نیست.',
        ], [
            'from' => 'سرشماره پیش‌فرض',
        ]);

        $user->forceFill(['sms_sender' => $data['from']])->save();

        return redirect()->route('dashboard.sms')
            ->with('sms_status', 'سرشمارهٔ پیش‌فرض ذخیره شد.');
    }

    /**
     * Fetch the account's sender numbers and cache them on the user row. Keeps
     * `sms_sender` pointing at a line the account still owns.
     */
    private function syncNumbers(User $user): void
    {
        $list = UserSmsGateway::for($user)->numbers();

        $user->forceFill([
            'sms_numbers' => array_values($list),
            'sms_numbers_synced_at' => now(),
        ]);

        if ($list !== [] && ! in_array($user->sms_sender, $list, true)) {
            $user->sms_sender = $list[0];
        }

        $user->save();
    }
}
