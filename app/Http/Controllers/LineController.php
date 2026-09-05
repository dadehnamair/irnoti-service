<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dashboard\LineOrderController;
use App\Models\LineBundle;
use App\Models\LineGroup;
use App\Models\LineOrder;
use App\Models\Setting;
use App\Models\SmsLine;
use App\Support\HandlesGatewayPayment;
use App\Support\OperationNotifier;
use App\Support\PayableSettlement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class LineController extends Controller
{
    use HandlesGatewayPayment;

    /**
     * Public dedicated-lines catalogue ("/lines") — docs/starter.md §9 / §80.
     * Lines are grouped by prefix into tabs; filtering by digits / type / rond
     * happens client-side. Everything is DB-driven (SmsLineResource in admin).
     */
    public function index(): View
    {
        $lines = SmsLine::query()->active()->ordered()->get();
        $groups = self::groupLines($lines);
        $canonical = route('lines');

        return view('lines', [
            'groups' => $groups,
            'digitOptions' => $lines->pluck('digits')->unique()->sort()->values(),
            'typeOptions' => $lines->pluck('line_type')->unique()->values(),
            'jsonLd' => $this->linesJsonLd($groups, $canonical),
            // prefix → landing slug, so each catalogue group can link to its page.
            'landings' => LineGroup::query()->active()->pluck('slug', 'prefix'),
        ]);
    }

    /**
     * Per-prefix landing page ("/lines/{group}") — docs/lines-landing.md.
     * Marketing copy + SEO + FAQ from the {@see LineGroup}, next to that
     * prefix's own purchase variants ({@see SmsLine}) and {@see LineBundle}s.
     */
    public function group(LineGroup $group): View
    {
        abort_unless($group->is_active, 404);

        $lines = $group->lines()->active()->ordered()->get();
        $bundles = $group->bundles()->active()->ordered()->get();
        $canonical = route('lines.group', $group);

        return view('line-group', [
            'group' => $group,
            'lines' => $lines,
            'bundles' => $bundles,
            'canonical' => $canonical,
            'jsonLd' => $this->groupJsonLd($group, $lines, $canonical),
        ]);
    }

    /** JSON-LD graph for a landing page: BreadcrumbList + Product/Offer per line + FAQPage. */
    private function groupJsonLd(LineGroup $group, Collection $lines, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'خطوط اختصاصی', 'item' => route('lines')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $group->title, 'item' => $canonical],
                ],
            ],
        ];

        foreach ($lines as $line) {
            $graph[] = [
                '@type' => 'Product',
                'name' => 'خط '.$line->prefix.' ('.$line->digits.' رقمی) '.$brand,
                'description' => $line->description ?: ($group->tagline ?: 'خط اختصاصی پیامک با پیش‌شماره '.$line->prefix),
                'brand' => ['@type' => 'Brand', 'name' => $brand],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $line->price,
                    'priceCurrency' => 'IRR',
                    'availability' => $line->sale_status === 'available'
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                    'url' => $canonical,
                ],
            ];
        }

        if ($faqs = $group->faq_list) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($row) => [
                    '@type' => 'Question',
                    'name' => $row['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $row['a']],
                ], $faqs),
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Checkout for a «باندل اختصاصی خط» (docs/lines-landing.md) — same contact
     * form as {@see checkout()}, posting to the shared {@see order()} endpoint.
     */
    public function bundleCheckout(LineGroup $group, LineBundle $bundle): View
    {
        abort_unless($group->is_active && $bundle->is_active && $bundle->line_group_id === $group->id, 404);

        return view('line-bundle-checkout', [
            'group' => $group,
            'bundle' => $bundle,
            'onlinePayment' => $this->onlinePaymentEnabled() && $bundle->price > 0,
        ]);
    }

    /** JSON-LD graph: BreadcrumbList + one Product/Offer per dedicated line. */
    private function linesJsonLd(Collection $groups, string $canonical): string
    {
        $brand = config('theme.brand');
        $url = rtrim(config('theme.seo.url'), '/');

        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $url.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'خطوط اختصاصی', 'item' => $canonical],
                ],
            ],
        ];

        foreach ($groups as $group) {
            foreach ($group['lines'] as $line) {
                $graph[] = [
                    '@type' => 'Product',
                    'name' => 'خط '.$line->prefix.' ('.$line->digits.' رقمی) '.$brand,
                    'description' => $line->description ?: ('خط اختصاصی پیامک با پیش‌شماره '.$line->prefix),
                    'brand' => ['@type' => 'Brand', 'name' => $brand],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $line->price,
                        'priceCurrency' => 'IRR',
                        'availability' => $line->sale_status === 'available'
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                        'url' => $canonical,
                    ],
                ];
            }
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Group a line collection by prefix into the tab structure the catalogue
     * views expect. Shared by the public /lines page and the in-panel version
     * ({@see LineOrderController}).
     *
     * @param  Collection<int, SmsLine>  $lines
     */
    public static function groupLines($lines): Collection
    {
        return $lines->groupBy('prefix')
            ->map(fn ($items, $prefix) => [
                'prefix' => $prefix,
                'label' => 'خطوط '.$prefix,
                'lines' => $items,
            ])
            ->values();
    }

    /**
     * Checkout page for one line (docs/starter.md §11 steps 4–6): the buyer sees
     * the number and price, then fills the contact form.
     */
    public function checkout(SmsLine $line): View
    {
        abort_unless($line->is_active, 404);

        return view('line-checkout', [
            'line' => $line,
            'onlinePayment' => $this->onlinePaymentEnabled() && ! $line->requires_inquiry && $line->price > 0,
        ]);
    }

    /**
     * Capture the purchase request (docs/starter.md §11). When online payment is
     * enabled and the line has a price, the buyer is sent to the gateway;
     * otherwise the order lands for the admin to process.
     */
    public function order(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'sms_line_id' => ['required_without:line_bundle_id', 'nullable', Rule::exists('sms_lines', 'id')->where('is_active', true)],
            'line_bundle_id' => ['required_without:sms_line_id', 'nullable', Rule::exists('line_bundles', 'id')->where('is_active', true)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'desired_number' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'customer_name' => 'نام',
            'customer_phone' => 'موبایل',
            'customer_email' => 'ایمیل',
        ]);

        $attributes = [
            'user_id' => $request->user()?->id,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'company' => $data['company'] ?? null,
            'desired_number' => $data['desired_number'] ?? null,
            'note' => $data['note'] ?? null,
        ];

        if (! empty($data['line_bundle_id'])) {
            // «باندل اختصاصی خط» — snapshot the bundle onto the order (docs/lines-landing.md).
            $bundle = LineBundle::with('smsLine', 'group')->findOrFail($data['line_bundle_id']);
            $line = $bundle->smsLine;
            $requiresInquiry = (bool) $line?->requires_inquiry;

            $attributes += [
                'sms_line_id' => $line?->id,
                'line_bundle_id' => $bundle->id,
                'line_label' => $line ? trim($line->group_label.' — '.$line->display_number) : ('خط '.$bundle->group->prefix),
                'bundle_label' => $bundle->title,
                'price' => $bundle->price,
                'sms_credit' => $bundle->sms_credit,
                'validity_days' => $bundle->validity_days,
                'status' => $requiresInquiry ? 'pending' : 'awaiting_payment',
            ];

            $payOnline = $this->onlinePaymentEnabled() && ! $requiresInquiry && $bundle->price > 0;
        } else {
            $line = SmsLine::findOrFail($data['sms_line_id']);

            $attributes += [
                'sms_line_id' => $line->id,
                'line_label' => trim($line->group_label.' — '.$line->display_number),
                'price' => $line->price,
                'status' => $line->requires_inquiry ? 'pending' : 'awaiting_payment',
            ];

            $payOnline = $this->onlinePaymentEnabled() && ! $line->requires_inquiry && $line->price > 0;
        }

        $order = LineOrder::create($attributes);

        // Notify the buyer + admin that a request was captured (docs/starter.md §44).
        $notifier->lineOrderCreated($order);

        if ($payOnline) {
            return redirect()->route('lines.pay', $order);
        }

        return redirect()
            ->route('lines.track', $order)
            ->with('order_created', true);
    }

    /**
     * Send the order to the payment gateway (shetabit/multipay). Falls back to
     * the tracking page when the order is not payable or online pay is off.
     */
    public function pay(LineOrder $order)
    {
        if (! $this->onlinePaymentEnabled() || ! $order->isPayable()) {
            return redirect()->route('lines.track', $order);
        }

        try {
            return $this->purchaseViaGateway(
                (int) $order->price,
                route('lines.payment.callback'),
                fn ($transactionId) => $order->update([
                    'transaction_id' => $transactionId,
                    'payment_driver' => config('payment.default'),
                ]),
            );
        } catch (\Throwable $e) {
            Log::error('Line payment purchase failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()
                ->route('lines.track', $order)
                ->with('payment_error', 'اتصال به درگاه پرداخت ممکن نشد. لطفاً بعداً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.');
        }
    }

    /** Gateway callback — verify the transaction and mark the order paid. */
    public function paymentCallback(Request $request, PayableSettlement $settlement): RedirectResponse
    {
        $transactionId = $this->gatewayTransactionId($request);

        $order = LineOrder::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->latest('id')
            ->first();

        if (! $order) {
            return redirect()->route('lines')->with('payment_error', 'سفارش مربوط به این پرداخت پیدا نشد.');
        }

        if ($this->gatewayPaymentCancelled($request)) {
            return redirect()
                ->route('lines.track', $order)
                ->with('payment_error', 'پرداخت توسط شما لغو شد. می‌توانید دوباره تلاش کنید.');
        }

        try {
            $receipt = $this->verifyViaGateway((int) $order->price, $order->transaction_id);

            // One idempotent settlement path — also grants any bundled SMS
            // credit for a «باندل اختصاصی خط» (docs/lines-landing.md).
            $settlement->settle($order, [
                'method' => 'gateway',
                'transaction_id' => $order->transaction_id,
                'reference_id' => $receipt->getReferenceId(),
                'payment_driver' => $order->payment_driver ?? config('payment.default'),
            ]);

            return redirect()
                ->route('lines.track', $order)
                ->with('payment_success', true);
        } catch (InvalidPaymentException $e) {
            return redirect()
                ->route('lines.track', $order)
                ->with('payment_error', $e->getMessage() ?: 'پرداخت ناموفق بود یا لغو شد.');
        } catch (\Throwable $e) {
            Log::error('Line payment verify failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()
                ->route('lines.track', $order)
                ->with('payment_error', 'تأیید پرداخت با خطا مواجه شد. اگر مبلغ از حساب شما کسر شده، با پشتیبانی تماس بگیرید.');
        }
    }

    /** Public order-status page, keyed by the order token (docs/starter.md §11). */
    public function track(LineOrder $order): View
    {
        return view('line-order', [
            'order' => $order,
            'justCreated' => (bool) session('order_created'),
            'canPayOnline' => $this->onlinePaymentEnabled() && $order->isPayable(),
        ]);
    }

    /** DB-backed toggle set from the admin panel (settings table). */
    private function onlinePaymentEnabled(): bool
    {
        return (bool) Setting::get('line_payment_online', false);
    }
}
