{{--
    Panel mega-menu (docs/starter.md §15). Rendered from the `features` catalogue,
    grouped by `group_label`. An item becomes a real link only when it is switched
    on globally (Feature::is_active) AND granted to the account (group + per-user
    overrides) AND has a routable page — otherwise it shows disabled as «بزودی».
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

    $featureGroups = $featureRows->groupBy('group_key');

    $featureIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>';
    $soonIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
@endphp

@foreach ($featureGroups as $groupKey => $rows)
    @php
        $groupLabel = $rows->first()->group_label;
        $groupOpen = false;
    @endphp
    <details class="account-nav__group account-nav__group--features" @if ($groupOpen) open @endif>
        <summary class="account-nav__label"><span>{{ $groupLabel }}</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            @foreach ($rows as $feature)
                @php
                    $unlocked = $feature->is_active
                        && isset($grantedKeys[$feature->key])
                        && $feature->route
                        && \Illuminate\Support\Facades\Route::has($feature->route);
                @endphp

                @if ($unlocked)
                    <a href="{{ route($feature->route) }}" @class(['is-active' => request()->routeIs($feature->route)])>
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
