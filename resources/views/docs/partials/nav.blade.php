@php
    /** @var \Illuminate\Support\Collection $tree */
    $activeArticleId = $activeArticleId ?? null;
@endphp

<ul class="docs-nav">
    @foreach ($tree as $category)
        <li class="docs-nav-group">
            <span class="docs-nav-title">{{ $category->title }}</span>

            @if ($category->articles->isNotEmpty())
                <ul>
                    @foreach ($category->articles as $item)
                        <li>
                            <a href="{{ route('docs.show', [$category->slug, $item->slug]) }}"
                               class="docs-nav-link @if ($activeArticleId === $item->id) is-active @endif"
                               @if ($activeArticleId === $item->id) aria-current="page" @endif>
                                @if ($item->http_method)
                                    <span class="docs-method docs-method--{{ strtolower($item->http_method) }}">{{ $item->http_method }}</span>
                                @endif
                                <span>{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @foreach ($category->children as $child)
                <span class="docs-nav-subtitle">{{ $child->title }}</span>
                <ul>
                    @foreach ($child->articles as $item)
                        <li>
                            <a href="{{ route('docs.show', [$child->slug, $item->slug]) }}"
                               class="docs-nav-link @if ($activeArticleId === $item->id) is-active @endif"
                               @if ($activeArticleId === $item->id) aria-current="page" @endif>
                                @if ($item->http_method)
                                    <span class="docs-method docs-method--{{ strtolower($item->http_method) }}">{{ $item->http_method }}</span>
                                @endif
                                <span>{{ $item->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </li>
    @endforeach
</ul>
