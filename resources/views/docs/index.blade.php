@php
    $brand = config('theme.brand');
    $pageTitle = 'مستندات API ' . $brand;
    $siteUrl = rtrim(config('theme.seo.url'), '/');

    $articleItems = [];
    foreach ($tree as $category) {
        foreach ($category->articles as $item) {
            $articleItems[] = ['@type' => 'ListItem', 'position' => count($articleItems) + 1, 'url' => route('docs.show', [$category->slug, $item->slug]), 'name' => $item->title];
        }
        foreach ($category->children as $child) {
            foreach ($child->articles as $item) {
                $articleItems[] = ['@type' => 'ListItem', 'position' => count($articleItems) + 1, 'url' => route('docs.show', [$child->slug, $item->slug]), 'name' => $item->title];
            }
        }
    }

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'مستندات API', 'item' => route('docs.index')],
                ],
            ],
            [
                '@type' => 'CollectionPage',
                'name' => $pageTitle,
                'url' => route('docs.index'),
                'isPartOf' => ['@type' => 'WebSite', 'name' => $brand, 'url' => $siteUrl . '/'],
                'hasPart' => ['@type' => 'ItemList', 'itemListElement' => $articleItems],
            ],
        ],
    ];
@endphp

@extends('docs.layout')

@push('jsonld')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('docs')
    <article class="docs-article">
        <header class="docs-article-head">
            <p class="docs-eyebrow">مستندات توسعه‌دهندگان</p>
            <h1>مستندات API {{ $brand }}</h1>
            <p class="docs-lead">
                با API {{ $brand }} می‌توانید ارسال پیامک، پیامک پترن، دریافت وضعیت تحویل،
                مدیریت دفترچه تلفن و Webhook را مستقیماً از سیستم خود انجام دهید. تمام
                نمونه‌کدها برای زبان‌های مختلف در دسترس است.
            </p>
        </header>

        @if ($firstArticle)
            <a class="btn btn-primary docs-start-btn"
               href="{{ route('docs.show', [$firstArticle->category->slug, $firstArticle->slug]) }}">
                شروع از {{ $firstArticle->title }}
            </a>
        @endif

        @if ($tree->isEmpty())
            <div class="docs-callout">
                هنوز محتوایی برای مستندات ثبت نشده است. از پنل مدیریت،
                بخش «مستندات API» دسته‌بندی و مقاله اضافه کنید.
            </div>
        @else
            <div class="docs-card-grid">
                @foreach ($tree as $category)
                    <section class="docs-card">
                        <h2>{{ $category->title }}</h2>
                        @if ($category->description)
                            <p>{{ $category->description }}</p>
                        @endif
                        <ul>
                            @foreach ($category->articles as $item)
                                <li>
                                    <a href="{{ route('docs.show', [$category->slug, $item->slug]) }}">
                                        @if ($item->http_method)
                                            <span class="docs-method docs-method--{{ strtolower($item->http_method) }}">{{ $item->http_method }}</span>
                                        @endif
                                        {{ $item->title }}
                                    </a>
                                </li>
                            @endforeach
                            @foreach ($category->children as $child)
                                @foreach ($child->articles as $item)
                                    <li>
                                        <a href="{{ route('docs.show', [$child->slug, $item->slug]) }}">
                                            @if ($item->http_method)
                                                <span class="docs-method docs-method--{{ strtolower($item->http_method) }}">{{ $item->http_method }}</span>
                                            @endif
                                            {{ $item->title }}
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </article>
@endsection
