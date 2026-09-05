@php
    // $pageTitle and $jsonLd (BreadcrumbList + CollectionPage) come from DocsController::index().
    $brand = config('theme.brand');
@endphp

@extends('docs.layout')

@push('jsonld')
    {!! $jsonLd !!}
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
