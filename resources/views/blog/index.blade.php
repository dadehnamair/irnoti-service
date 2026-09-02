@extends('blog.layout')

@php
    $brand = config('theme.brand');
    $siteUrl = rtrim(config('theme.seo.url'), '/');
@endphp

@push('meta')
    @if ($posts->currentPage() > 1)
        <link rel="prev" href="{{ $posts->previousPageUrl() }}" />
    @endif
    @if ($posts->hasMorePages())
        <link rel="next" href="{{ $posts->nextPageUrl() }}" />
    @endif
@endpush

@push('jsonld')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $heading,
        'description' => $intro,
        'url' => url()->current(),
        'isPartOf' => ['@type' => 'WebSite', 'name' => $brand, 'url' => $siteUrl . '/'],
        'hasPart' => $posts->map(fn ($p) => [
            '@type' => 'BlogPosting',
            'headline' => $p->title,
            'url' => route('blog.show', $p->slug),
            'datePublished' => optional($p->published_date)->toIso8601String(),
        ])->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('blog')
    <nav class="blog-breadcrumb" aria-label="مسیر">
        <a href="{{ route('home') }}">خانه</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('blog.index') }}">بلاگ</a>
        @if ($crumb)
            <span aria-hidden="true">/</span>
            <span>{{ $crumb }}</span>
        @endif
    </nav>

    <header class="blog-hero">
        <h1>{{ $heading }}</h1>
        @if ($intro)
            <p>{{ $intro }}</p>
        @endif
    </header>

    @if ($categories->isNotEmpty())
        <div class="blog-chips">
            <a href="{{ route('blog.index') }}"
               class="blog-chip @if (request()->routeIs('blog.index')) is-active @endif">همه</a>
            @foreach ($categories as $category)
                <a href="{{ route('blog.category', $category->slug) }}"
                   class="blog-chip @if (request()->routeIs('blog.category') && request()->route('category') === $category->slug) is-active @endif">
                    {{ $category->name }}
                    <span>{{ $category->posts_count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($posts->isEmpty())
        <p class="blog-empty">هنوز مقاله‌ای منتشر نشده است.</p>
    @else
        <div class="blog-grid">
            @foreach ($posts as $post)
                <article class="blog-card">
                    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card-media" aria-hidden="true" tabindex="-1">
                        @if ($post->cover_url)
                            <img src="{{ $post->cover_url }}" alt="" loading="lazy" width="640" height="360" />
                        @else
                            <span class="blog-card-fallback">{{ mb_substr($post->title, 0, 1) }}</span>
                        @endif
                    </a>
                    <div class="blog-card-body">
                        @if ($post->category)
                            <a class="blog-card-cat" href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
                        @endif
                        <h2><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h2>
                        @if ($post->excerpt)
                            <p>{{ $post->excerpt }}</p>
                        @endif
                        <div class="blog-card-meta">
                            <time datetime="{{ optional($post->published_date)->toDateString() }}">@jdate($post->published_date)</time>
                            <span>·</span>
                            <span>{{ $post->reading_minutes }} دقیقه مطالعه</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="blog-pagination">
            {{ $posts->onEachSide(1)->links() }}
        </div>
    @endif
@endsection
