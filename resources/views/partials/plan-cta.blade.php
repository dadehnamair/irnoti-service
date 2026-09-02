@php
    /**
     * Plan card call-to-action (docs/starter.md §8). An explicit cta_url wins
     * (e.g. "درخواست مشاوره"); otherwise route the visitor into the purchase
     * flow — straight to checkout when logged in, via registration otherwise.
     */
    $period = $period ?? 'monthly';

    $ctaHref = $plan->cta_url
        ?: (auth()->check()
            ? route('dashboard.plan.checkout', ['plan' => $plan->slug, 'period' => $period])
            : route('register', ['plan' => $plan->slug, 'period' => $period]));
@endphp

<a href="{{ $ctaHref }}" class="btn {{ $plan->cta_style }} full">
    @if ($plan->isFree() && ! $plan->cta_url)
        شروع رایگان
    @else
        {{ $plan->cta_label }}
    @endif
</a>
