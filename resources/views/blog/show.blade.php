@extends('blog.layout')

{{--
    $metaTitle, $metaDescription, $canonical, $ogType, $ogImage, $noindex,
    $published, $modified, $authorName and $jsonLd (BlogPosting + BreadcrumbList)
    are all built in BlogController::show().
--}}

@push('meta')
    <meta property="article:published_time" content="{{ $published }}" />
    <meta property="article:modified_time" content="{{ $modified }}" />
    <meta property="article:author" content="{{ $authorName }}" />
    @if ($article->category)
        <meta property="article:section" content="{{ $article->category->name }}" />
    @endif
    @foreach ($article->tags as $tag)
        <meta property="article:tag" content="{{ $tag->name }}" />
    @endforeach
@endpush

@push('jsonld')
    {!! $jsonLd !!}
@endpush

@section('blog')
    <article class="blog-post">
        <nav class="blog-breadcrumb" aria-label="مسیر">
            <a href="{{ route('home') }}">خانه</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('blog.index') }}">بلاگ</a>
            @if ($article->category)
                <span aria-hidden="true">/</span>
                <a href="{{ route('blog.category', $article->category->slug) }}">{{ $article->category->name }}</a>
            @endif
        </nav>

        <header class="blog-post-head">
            @if ($article->category)
                <a class="blog-post-cat" href="{{ route('blog.category', $article->category->slug) }}">{{ $article->category->name }}</a>
            @endif
            <h1>{{ $article->title }}</h1>
            @if ($article->excerpt)
                <p class="blog-post-lead">{{ $article->excerpt }}</p>
            @endif
            <div class="blog-post-meta">
                <span>{{ $authorName }}</span>
                <span>·</span>
                <time datetime="{{ optional($article->published_date)->toDateString() }}">@jdate($article->published_date)</time>
                <span>·</span>
                <span>{{ $article->reading_minutes }} دقیقه مطالعه</span>
            </div>
        </header>

        @if ($article->cover_url)
            <figure class="blog-post-cover">
                <img src="{{ $article->cover_url }}" alt="{{ $article->title }}" width="1200" height="630" />
            </figure>
        @endif

        <div class="blog-prose">
            {!! $article->rendered_body !!}
        </div>

        @if ($article->tags->isNotEmpty())
            <div class="blog-post-tags">
                @foreach ($article->tags as $tag)
                    <a href="{{ route('blog.tag', $tag->slug) }}">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="blog-share">
            <span>اشتراک‌گذاری:</span>
            <a href="https://t.me/share/url?url={{ urlencode($canonical) }}&text={{ urlencode($article->title) }}" target="_blank" rel="noopener">تلگرام</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonical) }}&text={{ urlencode($article->title) }}" target="_blank" rel="noopener">توییتر</a>
            <a href="https://api.whatsapp.com/send?text={{ urlencode($article->title . ' ' . $canonical) }}" target="_blank" rel="noopener">واتساپ</a>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="blog-related">
            <h2>مطالب مرتبط</h2>
            <div class="blog-grid">
                @foreach ($related as $post)
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
                                <span class="blog-card-cat">{{ $post->category->name }}</span>
                            @endif
                            <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
                            <div class="blog-card-meta">
                                <time datetime="{{ optional($post->published_date)->toDateString() }}">@jdate($post->published_date)</time>
                                <span>·</span>
                                <span>{{ $post->reading_minutes }} دقیقه</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
