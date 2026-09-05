<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BusinessCard;
use App\Models\Domain;
use App\Models\Setting;
use App\Support\HandlesGatewayPayment;
use App\Support\OperationNotifier;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

/**
 * Self-service digital business cards: create, edit, buy from the customer
 * panel. "standard" tier is a flat price; "vip" lets the owner pick a Domain
 * and a custom code, priced by that domain's code_price_tiers.
 */
class BusinessCardController extends Controller
{
    use HandlesGatewayPayment;

    public function index(Request $request): View
    {
        return view('dashboard.cards.index', [
            'cards' => $request->user()->businessCards()->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.cards.create', [
            'domains' => Domain::query()->active()->ordered()->get(),
            'standardPrice' => (int) Setting::get('business_card_standard_price', 0),
        ]);
    }

    /** Live price quote used by the create form while the owner types a vip code. */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain_id' => ['required', Rule::exists('domains', 'id')->where('is_active', true)],
            'code' => ['required', 'string', 'max:32'],
        ]);

        $domain = Domain::findOrFail($data['domain_id']);
        $tier = $domain->tierForCode($data['code']);

        if (! $tier) {
            return response()->json(['ok' => false, 'message' => 'برای این طول/نوع کد در دامنه انتخابی تعرفه‌ای تعریف نشده است.']);
        }

        $taken = BusinessCard::query()->where('domain_id', $domain->id)->where('code', $data['code'])->exists();

        if ($taken) {
            return response()->json(['ok' => false, 'message' => 'این کد در این دامنه قبلاً گرفته شده است.']);
        }

        return response()->json(['ok' => true, 'price' => (int) $tier['price']]);
    }

    public function store(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request);

        [$domain, $code, $price] = $this->resolvePricing($data);

        $card = $user->businessCards()->create([
            'domain_id' => $domain->id,
            'tier' => $data['tier'],
            'code' => $code,
            'price' => $price,
            'status' => $price > 0 ? 'awaiting_payment' : 'active',
            ...$this->attributes($data, $request),
        ]);

        if ($price === 0) {
            $notifier->businessCardPaid($card);
        }

        return redirect()->route('dashboard.cards.edit', $card)->with('status', $price > 0
            ? 'کارت ساخته شد. برای فعال‌سازی، پرداخت را تکمیل کنید.'
            : 'کارت ویزیت دیجیتال شما فعال شد.');
    }

    public function edit(Request $request, BusinessCard $card): View
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        return view('dashboard.cards.edit', [
            'card' => $card,
            'walletBalance' => $request->user()->wallet()->balance,
        ]);
    }

    public function update(Request $request, BusinessCard $card): RedirectResponse
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        $data = $this->validated($request, forContent: true);

        $card->update($this->attributes($data, $request, $card));

        return redirect()->route('dashboard.cards.edit', $card)->with('status', 'اطلاعات کارت ذخیره شد.');
    }

    public function payFromWallet(Request $request, BusinessCard $card, PayableSettlement $settlement): RedirectResponse
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        if (! $card->isPayable()) {
            return redirect()->route('dashboard.cards.edit', $card);
        }

        $wallet = $request->user()->wallet();

        if (! $wallet->hasSufficient((int) $card->price)) {
            return redirect()->route('dashboard.cards.edit', $card)
                ->with('payment_error', 'موجودی کیف پول کافی نیست. ابتدا حساب خود را شارژ کنید.');
        }

        $wallet->debit((int) $card->price, 'business_card_purchase', $card, 'خرید کارت ویزیت دیجیتال', "business_card:{$card->id}");
        $settlement->settle($card, ['method' => 'wallet']);

        return redirect()->route('dashboard.cards.edit', $card)->with('payment_success', true);
    }

    public function pay(Request $request, BusinessCard $card)
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        if (! $this->onlinePaymentEnabled() || ! $card->isPayable()) {
            return redirect()->route('dashboard.cards.edit', $card);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $card->price,
                route('cards.payment.callback'),
                fn ($transactionId) => $card->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Business card payment purchase failed', ['card' => $card->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.cards.edit', $card)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً دوباره تلاش کنید.');
        }
    }

    public function paymentCallback(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $card = BusinessCard::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $card) {
            return redirect()->route('dashboard.cards')->with('payment_error', 'کارت مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()->route('dashboard.cards.edit', $card)
                ->with('payment_error', 'پرداخت توسط شما لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $card->price, $card->transaction_id);

            $card->update([
                'status' => 'active',
                'reference_id' => $receipt->getReferenceId(),
                'paid_at' => now(),
            ]);

            $notifier->businessCardPaid($card);

            return redirect()->route('dashboard.cards.edit', $card)->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()->route('dashboard.cards.edit', $card)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Business card payment verify failed', ['card' => $card->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.cards.edit', $card)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ کسر شده با پشتیبانی تماس بگیرید.');
        }
    }

    /** @return array{0: Domain, 1: string, 2: int} */
    private function resolvePricing(array $data): array
    {
        $domain = Domain::findOrFail($data['domain_id']);

        if ($data['tier'] === 'standard') {
            return [$domain, $data['code'], (int) Setting::get('business_card_standard_price', 0)];
        }

        $tier = $domain->tierForCode($data['code']);

        if (! $tier) {
            throw ValidationException::withMessages([
                'code' => 'برای این طول/نوع کد در دامنه انتخابی تعرفه‌ای تعریف نشده است.',
            ]);
        }

        return [$domain, $data['code'], (int) $tier['price']];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $forContent = false): array
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'telegram' => ['nullable', 'string', 'max:100'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'theme_color' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ];

        if (! $forContent) {
            $rules['tier'] = ['required', Rule::in(array_keys(BusinessCard::TIERS))];
            $rules['domain_id'] = ['required', Rule::exists('domains', 'id')->where('is_active', true)];
            $rules['code'] = [
                'required', 'string', 'min:1', 'max:32', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('business_cards', 'code')->where('domain_id', $request->input('domain_id')),
            ];
        }

        return $request->validate($rules, [], [
            'title' => 'عنوان', 'position' => 'سمت', 'company' => 'شرکت', 'bio' => 'بیوگرافی',
            'phone' => 'تلفن', 'mobile' => 'موبایل', 'website' => 'وبسایت', 'email' => 'ایمیل',
            'address' => 'آدرس', 'domain_id' => 'دامنه', 'code' => 'کد اختصاصی',
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function attributes(array $data, ?Request $request = null, ?BusinessCard $card = null): array
    {
        $attributes = [
            'title' => $data['title'] ?? null,
            'position' => $data['position'] ?? null,
            'company' => $data['company'] ?? null,
            'bio' => $data['bio'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'telegram' => $data['telegram'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'theme_color' => $data['theme_color'] ?? null,
        ];

        if ($request?->hasFile('avatar')) {
            $attributes['avatar_path'] = $request->file('avatar')->store('cards/'.$request->user()->id, 'public');
        }

        if ($request?->hasFile('cover')) {
            $attributes['cover_path'] = $request->file('cover')->store('cards/'.$request->user()->id, 'public');
        }

        return $attributes;
    }

    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('business_card_payment_online', false);
    }
}
