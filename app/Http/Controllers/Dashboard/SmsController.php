<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Basic SMS panel for an approved customer (docs/starter.md §12): single send,
 * send history, the panel credit, and the dedicated sender numbers (سرشماره)
 * pulled from the customer's own Melipayamak account ({@see UserSmsGateway}).
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

    /** Pull the account's sender numbers from Melipayamak on demand. */
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
                ->with('sms_status', 'فهرست سرشماره‌ها از ملی‌پیامک به‌روزرسانی شد.');
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
