<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Basic SMS panel for an approved customer (docs/starter.md §12): single send,
 * send history, and the panel credit read live from the customer's own
 * Melipayamak account ({@see UserSmsGateway}).
 */
class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $credit = null;
        $creditError = null;

        if ($user->hasSmsPanel()) {
            try {
                $credit = Cache::remember(
                    "sms_credit:{$user->id}",
                    now()->addSeconds(60),
                    fn () => UserSmsGateway::for($user)->credit(),
                );
            } catch (\Throwable $e) {
                Log::warning('SMS credit read failed', ['user' => $user->id, 'error' => $e->getMessage()]);
                $creditError = 'اعتبار پنل در دسترس نیست.';
            }
        }

        return view('dashboard.sms', [
            'user' => $user,
            'hasPanel' => $user->hasSmsPanel(),
            'credit' => $credit,
            'creditError' => $creditError,
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
            'message' => ['required', 'string', 'max:600'],
        ], [
            'to.regex' => 'شمارهٔ گیرنده معتبر نیست (مثال: 09121234567).',
        ], [
            'to' => 'گیرنده',
            'message' => 'متن پیام',
        ]);

        $message = SmsMessage::create([
            'user_id' => $user->id,
            'to' => $data['to'],
            'body' => $data['message'],
            'parts' => max(1, (int) ceil(mb_strlen($data['message']) / 70)),
            'status' => 'queued',
        ]);

        try {
            $recId = UserSmsGateway::for($user)->send($data['to'], $data['message']);
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
}
