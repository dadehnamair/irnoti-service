{{--
    Installed «بازارچه» add-ons that have their own page (integrations like
    ایرپلاس). Feature-unlock add-ons don't appear here — their sidebar rows light
    up through the normal feature grants. `$navChevron` comes from the parent.
--}}
@php
    $mpUser = auth()->user();

    $mpInstalls = $mpUser
        ? rescue(
            fn () => $mpUser->marketplaceInstallations()
                ->where('status', 'active')
                ->with('app')
                ->get()
                ->filter(fn ($i) => $i->app && $i->handler()->panelView($i) !== null),
            collect(),
            false,
        )
        : collect();
@endphp

@if ($mpInstalls->isNotEmpty())
    <details class="account-nav__group" open>
        <summary class="account-nav__label"><span>افزونه‌های من</span>{!! $navChevron !!}</summary>
        <div class="account-nav__panel">
            @foreach ($mpInstalls as $install)
                <a href="{{ route('marketplace.manage', $install) }}"
                   @class(['is-active' => request()->routeIs('marketplace.manage') && request()->route('installation')?->id === $install->id])>
                    <span class="mk-ico sm" aria-hidden="true"
                        @if ($install->app->accent_color) style="--mk-accent: {{ $install->app->accent_color }}" @endif
                    >{!! $install->app->icon_html !!}</span>
                    <span>{{ $install->app->name }}</span>
                </a>
            @endforeach
        </div>
    </details>
@endif
