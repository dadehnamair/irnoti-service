<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProviderMessagesJob;
use App\Models\ProviderMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The «پیام‌ها» menu (docs/starter.md §14): the customer's message archive —
 * دریافتی (incoming to the account's سرشماره‌ها) and ارسالی (everything the
 * account has sent). The pages read only the local {@see ProviderMessage} mirror;
 * opening one debounce-dispatches {@see SyncProviderMessagesJob} to refresh that
 * mirror from the provider in the background, and «بروزرسانی» forces a refresh.
 */
class MessagesController extends Controller
{
    private const PER_PAGE = 30;

    /** Skip re-dispatching the sync if one was queued within this window (seconds). */
    private const SYNC_DEBOUNCE = 90;

    /** دریافتی — messages people sent to the account's dedicated lines. */
    public function inbox(Request $request): View
    {
        return $this->box($request, 'inbox');
    }

    /** ارسالی — messages sent from the account. */
    public function sent(Request $request): View
    {
        return $this->box($request, 'sent');
    }

    private function box(Request $request, string $direction): View
    {
        $user = $request->user();

        if ($user->hasSmsPanel()) {
            $this->queueSync($user);
        }

        $messages = ProviderMessage::query()
            ->forBox($user->id, $direction)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('dashboard.messages', [
            'box' => $direction,
            'hasPanel' => $user->hasSmsPanel(),
            'messages' => $messages,
            'syncedAt' => Cache::get("provider_msgs_synced_at:{$user->id}"),
        ]);
    }

    /** Manual «بروزرسانی» — force a fresh pull from the provider. */
    public function refresh(Request $request, string $box): RedirectResponse
    {
        abort_unless(in_array($box, ['inbox', 'sent'], true), 404);

        $user = $request->user();
        $route = $box === 'sent' ? 'dashboard.messages.sent' : 'dashboard.messages.inbox';

        if (! $user->hasSmsPanel()) {
            return redirect()->route($route)->with('sms_error', 'پنل پیامک شما هنوز فعال نشده است.');
        }

        $this->queueSync($user, force: true);

        return redirect()->route($route)
            ->with('sms_status', 'بروزرسانی پیام‌ها در صف قرار گرفت؛ چند لحظه بعد صفحه را تازه کنید.');
    }

    private function queueSync(User $user, bool $force = false): void
    {
        $lock = "provider_msgs_sync_lock:{$user->id}";

        if (! $force && Cache::has($lock)) {
            return;
        }

        Cache::put($lock, true, now()->addSeconds(self::SYNC_DEBOUNCE));

        SyncProviderMessagesJob::dispatch($user->id);
    }
}
