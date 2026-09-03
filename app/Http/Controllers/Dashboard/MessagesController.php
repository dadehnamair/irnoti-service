<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Sms\UserSmsGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The «پیام‌ها» menu (docs/starter.md §14): the customer's message archive exactly
 * as the SMS provider reports it — دریافتی (incoming to the account's سرشماره‌ها)
 * and ارسالی (everything the account has sent). Read live through the customer's
 * own panel credentials ({@see UserSmsGateway::messages()}); nothing is stored
 * locally, so paging is a simple index/count window over the provider list.
 */
class MessagesController extends Controller
{
    /** Rows per page — also the provider `count` window we ask for. */
    private const PER_PAGE = 25;

    /** دریافتی — messages people sent to the account's dedicated lines. */
    public function inbox(Request $request): View
    {
        return $this->archive($request, 'inbox', location: 1);
    }

    /** ارسالی — messages sent from the account. */
    public function sent(Request $request): View
    {
        return $this->archive($request, 'sent', location: 2);
    }

    private function archive(Request $request, string $box, int $location): View
    {
        $user = $request->user();
        $page = max(1, (int) $request->integer('page', 1));

        $messages = [];
        $error = null;
        $hasMore = false;

        if ($user->hasSmsPanel()) {
            try {
                $messages = UserSmsGateway::for($user)->messages(
                    $location,
                    ($page - 1) * self::PER_PAGE,
                    self::PER_PAGE,
                );

                // No total count from the provider — a full page means "try next".
                $hasMore = count($messages) === self::PER_PAGE;
            } catch (\Throwable $e) {
                Log::warning('SMS message archive read failed', [
                    'user' => $user->id,
                    'box' => $box,
                    'error' => $e->getMessage(),
                ]);

                $error = $e->getMessage();
            }
        }

        return view('dashboard.messages', [
            'box' => $box,
            'hasPanel' => $user->hasSmsPanel(),
            'messages' => $messages,
            'error' => $error,
            'page' => $page,
            'hasMore' => $hasMore,
        ]);
    }
}
