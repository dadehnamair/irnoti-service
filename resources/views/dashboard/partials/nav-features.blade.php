{{--
    Panel sidebar menu (docs/starter.md §15), rendered from the `features`
    catalogue grouped by `group_label`. Only items the account actually has are
    listed: a built-in page (`is_system`) or one granted through its access group
    / per-user overrides. Anything else is hidden entirely (no «بزودی» stub), and
    a group with nothing left to show is dropped. A visible item is a real link
    when it is switched on (`is_active`) AND reachable (has a `url` or a routable
    `route`); otherwise it shows disabled as «بزودی».
    `$navChevron` comes from the parent partial (dashboard.partials.nav).
--}}
@php
    $featureUser = auth()->user();

    $featureRows = rescue(
        fn () => \App\Models\Feature::query()->ordered()->get(),
        collect(),
        false,
    );

    $grantedKeys = $featureUser
        ? rescue(fn () => array_flip($featureUser->grantedFeatureKeys()), [], false)
        : [];

    // Drop features the account doesn't have — system pages plus granted items stay.
    $featureRows = $featureRows->filter(
        fn ($f) => $f->is_system || isset($grantedKeys[$f->key]),
    );

    $featureGroups = $featureRows->groupBy('group_key');

    // Open the group that owns the current page (fall back to the first group).
    $openGroup = null;
    foreach ($featureGroups as $gk => $rows) {
        if ($rows->contains(fn ($f) => $f->route && request()->routeIs($f->route))) {
            $openGroup = $gk;
            break;
        }
    }
    $openGroup ??= $featureGroups->keys()->first();

    $featureIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>';
    $soonIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
@endphp

@foreach ($featureGroups as $groupKey => $rows)
    <details class="account-nav__group account-nav__group--features" @if ($groupKey === $openGroup) open @endif>
        <summary class="account-nav__label"><span>{{ $rows->first()->group_label }}</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            @foreach ($rows as $feature)
                @php
                    $target = $feature->url
                        ?: ($feature->route && \Illuminate\Support\Facades\Route::has($feature->route)
                            ? route($feature->route)
                            : null);

                    // Rows are already filtered to what the account has; here it's
                    // just "launched yet?" — switched on and reachable.
                    $unlocked = $feature->is_active && $target;
                @endphp

                @if ($unlocked)
                    <a href="{{ $target }}" @class(['is-active' => $feature->route && request()->routeIs($feature->route)])>
                        {!! $featureIcon !!}
                        <span>{{ $feature->label }}</span>
                    </a>
                @else
                    <span class="account-nav__soon" aria-disabled="true" title="این امکان بزودی فعال می‌شود">
                        {!! $soonIcon !!}
                        <span>{{ $feature->label }}</span>
                        <em>بزودی</em>
                    </span>
                @endif
            @endforeach
        </div>
    </details>
@endforeach
