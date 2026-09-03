<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessengerCampaignJob;
use App\Models\ContactGroup;
use App\Models\MessengerCampaign;
use App\Services\Messenger\MessengerManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Bulk send to a messenger network — بله / ایتا / واتساپ (docs/starter.md §91).
 * A service parallel to {@see SmsController}: pick a channel, pick contact groups
 * and/or paste a list of numbers/ids, write one message. The cost
 * (recipients × the channel tariff) is taken from the wallet up front;
 * {@see SendMessengerCampaignJob} does the delivery and refunds whatever fails.
 */
class MessengerController extends Controller
{
    /** Hard ceiling on recipients per campaign. */
    private const BULK_CAP = 5000;

    public function __construct(private readonly MessengerManager $messenger) {}

    public function index(Request $request): View
    {
        abort_unless($this->messenger->enabled(), 404);

        $user = $request->user();

        // «ارسال به پیام‌رسان» is a gated panel capability (docs/starter.md §91).
        abort_unless($user->canUseFeature('messengers.send'), 403);

        // Only the networks the system has enabled AND this account is granted
        // the per-channel capability for.
        $channels = array_values(array_filter(array_map(fn (string $key) => [
            'key' => $key,
            'label' => $this->messenger->label($key),
            'tariff' => $this->messenger->tariffFor($key),
        ], $this->messenger->availableChannels()), fn (array $c) => $user->canUseFeature("messengers.{$c['key']}")));

        return view('dashboard.messenger.index', [
            'channels' => $channels,
            'walletBalance' => $user->walletBalance(),
            'campaigns' => MessengerCampaign::forUser($user->id)->latest()->limit(20)->get(),
        ]);
    }

    public function create(Request $request, string $channel): View
    {
        abort_unless($this->messenger->enabled() && $this->messenger->channelEnabled($channel), 404);

        $user = $request->user();

        abort_unless(
            $user->canUseFeature('messengers.send') && $user->canUseFeature("messengers.{$channel}"),
            403,
        );

        return view('dashboard.messenger.create', [
            'channel' => $channel,
            'channelLabel' => $this->messenger->label($channel),
            'tariff' => $this->messenger->tariffFor($channel),
            'walletBalance' => $user->walletBalance(),
            'groups' => $user->contactGroups()->ordered()->withCount('contacts')->get(),
            'cap' => self::BULK_CAP,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        abort_unless($this->messenger->enabled(), 404);

        $user = $request->user();
        $channel = (string) $request->input('channel');

        abort_unless($this->messenger->channelEnabled($channel), 404);
        abort_unless(
            $user->canUseFeature('messengers.send') && $user->canUseFeature("messengers.{$channel}"),
            403,
        );

        $data = $request->validate([
            'channel' => ['required', 'string'],
            'groups' => ['array'],
            'groups.*' => [Rule::exists('contact_groups', 'id')->where('user_id', $user->id)],
            'recipients' => ['nullable', 'string', 'max:50000'],
            'message' => ['required', 'string', 'max:1000'],
            'schedule_at' => ['nullable', 'date'],
        ], [], [
            'message' => 'متن پیام',
            'schedule_at' => 'زمان ارسال',
        ]);

        $groups = $user->contactGroups()->whereKey($data['groups'] ?? [])->with('contacts')->get();

        $recipients = $groups
            ->flatMap(fn (ContactGroup $group) => $group->contacts->pluck('mobile'))
            ->merge($this->parseRecipients($data['recipients'] ?? ''))
            ->map(fn ($raw) => $this->messenger->normalizeRecipient((string) $raw))
            ->filter()
            ->reject(fn (string $r) => $this->messenger->classify($r) === 'mobile' && ! preg_match('/^09\d{9}$/', $r))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'حداقل یک گروه یا فهرستی از شماره‌ها/شناسه‌ها وارد کنید.');
        }

        if ($recipients->count() > self::BULK_CAP) {
            return back()->withInput()->with('error', sprintf(
                'تعداد گیرندگان (%s) بیش از حد مجاز است (%s).',
                number_format($recipients->count()),
                number_format(self::BULK_CAP),
            ));
        }

        $tariff = $this->messenger->tariffFor($channel);
        $cost = $recipients->count() * $tariff;
        $wallet = $user->wallet();

        if ($cost > 0 && ! $wallet->hasSufficient($cost)) {
            return back()->withInput()->with('error', sprintf(
                'موجودی کیف پول کافی نیست؛ هزینهٔ این ارسال %s تومان است.',
                number_format($cost),
            ));
        }

        $campaign = DB::transaction(function () use ($user, $channel, $data, $recipients, $cost, $wallet) {
            $campaign = MessengerCampaign::create([
                'user_id' => $user->id,
                'channel' => $channel,
                'body' => $data['message'],
                'recipients_count' => $recipients->count(),
                'status' => 'queued',
                'cost' => $cost,
                'scheduled_at' => $this->scheduleAt($data['schedule_at'] ?? null),
            ]);

            $now = now();

            $campaign->recipients()->insert($recipients->map(fn (string $to) => [
                'messenger_campaign_id' => $campaign->id,
                'to' => $to,
                'type' => $this->messenger->classify($to),
                'status' => 'queued',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            if ($cost > 0) {
                $wallet->debit(
                    $cost,
                    'messenger_send',
                    $campaign,
                    'ارسال به '.$campaign->channel_label,
                    "messenger:{$campaign->id}:charge",
                );
            }

            return $campaign;
        });

        $dispatch = SendMessengerCampaignJob::dispatch($campaign->id);

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $dispatch->delay($campaign->scheduled_at);
        }

        return redirect()->route('dashboard.messenger')->with('status', sprintf(
            'ارسال به %s برای %s گیرنده در صف قرار گرفت؛ وضعیت در جدول کمپین‌ها نمایش داده می‌شود.',
            $campaign->channel_label,
            number_format($recipients->count()),
        ));
    }

    /** @return array<int, string> */
    private function parseRecipients(string $raw): array
    {
        return array_values(array_filter(preg_split('/[\s,;]+/', trim($raw)) ?: []));
    }

    private function scheduleAt(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
