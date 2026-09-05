@php
    $brand = config('theme.brand');
    $pageTitle = ($article->seo_title ?: $article->title) . ' — مستندات ' . $brand;
    $pageDescription = $article->seo_description ?: $article->excerpt;
    $siteUrl = rtrim(config('theme.seo.url'), '/');
    $canonical = route('docs.show', [$category->slug, $article->slug]);

    $techArticleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
        'headline' => \Illuminate\Support\Str::limit($article->title, 110, ''),
        'description' => $pageDescription,
        'dateModified' => $article->updated_at?->toIso8601String(),
        'datePublished' => $article->created_at?->toIso8601String(),
        'inLanguage' => 'fa-IR',
        'author' => ['@type' => 'Organization', 'name' => $brand],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $brand,
            'logo' => ['@type' => 'ImageObject', 'url' => $siteUrl . '/logo/logo-text.png'],
        ],
    ];

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'مستندات API', 'item' => route('docs.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $category->title, 'item' => $canonical],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $article->title, 'item' => $canonical],
        ],
    ];
@endphp

@extends('docs.layout')

@push('jsonld')
    <script type="application/ld+json">{!! json_encode($techArticleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('docs')
    <article class="docs-article">
        <nav class="docs-breadcrumb" aria-label="مسیر">
            <a href="{{ route('docs.index') }}">مستندات</a>
            <span aria-hidden="true">/</span>
            <span>{{ $category->title }}</span>
        </nav>

        <header class="docs-article-head">
            <h1>{{ $article->title }}</h1>

            @if ($article->excerpt)
                <p class="docs-lead">{{ $article->excerpt }}</p>
            @endif

            @if ($article->endpoint)
                <div class="docs-endpoint" dir="ltr">
                    @if ($article->http_method)
                        <span class="docs-method docs-method--{{ strtolower($article->http_method) }}">{{ $article->http_method }}</span>
                    @endif
                    <code>{{ $article->endpoint }}</code>
                    <button type="button" class="docs-copy" data-copy="{{ $article->endpoint }}" aria-label="کپی آدرس">کپی</button>
                </div>
            @endif
        </header>

        @if ($article->rendered_body)
            <div class="docs-prose">
                {!! $article->rendered_body !!}
            </div>
        @endif

        @if ($article->parameters->isNotEmpty())
            <section class="docs-section">
                <h2>پارامترها</h2>
                <div class="docs-table-wrap">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>نام</th>
                                <th>نوع</th>
                                <th>الزامی</th>
                                <th>توضیح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($article->parameters as $param)
                                <tr>
                                    <td><code dir="ltr">{{ $param->name }}</code></td>
                                    <td>{{ $param->type ?: '—' }}</td>
                                    <td>
                                        @if ($param->is_required)
                                            <span class="docs-req">بله</span>
                                        @else
                                            <span class="docs-opt">خیر</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $param->description }}
                                        @if ($param->example)
                                            <span class="docs-example" dir="ltr">مثال: {{ $param->example }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($article->codeSamples->isNotEmpty())
            <section class="docs-section">
                <h2>نمونه کد</h2>
                <div class="docs-code-tabs" data-code-tabs>
                    <div class="docs-code-tablist" role="tablist" aria-label="زبان نمونه‌کد">
                        @foreach ($article->codeSamples as $i => $sample)
                            <button type="button"
                                    role="tab"
                                    id="tab-{{ $sample->id }}"
                                    aria-controls="panel-{{ $sample->id }}"
                                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                    class="docs-code-tab @if ($i === 0) is-active @endif">
                                {{ $sample->label ?: $sample->language_label }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($article->codeSamples as $i => $sample)
                        <div role="tabpanel"
                             id="panel-{{ $sample->id }}"
                             aria-labelledby="tab-{{ $sample->id }}"
                             class="docs-code-panel @if ($i === 0) is-active @endif"
                             @if ($i !== 0) hidden @endif>
                            <div class="docs-code-head">
                                <span>{{ $sample->language_label }}</span>
                                <button type="button" class="docs-copy" data-copy-target="code-{{ $sample->id }}" aria-label="کپی کد">کپی</button>
                            </div>
                            <pre class="docs-code"><code id="code-{{ $sample->id }}" class="language-{{ $sample->highlight_language }}" dir="ltr">{{ $sample->code }}</code></pre>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
