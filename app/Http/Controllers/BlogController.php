<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->published()
            ->with(['category', 'author'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $heading = 'بلاگ '.config('theme.brand');
        $intro = 'راهنماها و تحلیل‌های کاربردی دربارهٔ بازاریابی پیامکی، افزایش فروش و ارتباط با مشتری.';

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => $heading,
            'intro' => $intro,
            'crumb' => null,
            'jsonLd' => $this->collectionJsonLd($heading, $intro, $posts),
        ]);
    }

    public function category(string $category): View
    {
        $model = BlogCategory::query()->visible()->where('slug', $category)->firstOrFail();

        $posts = $model->posts()
            ->published()
            ->with(['category', 'author'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $intro = $model->description;

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => $model->name,
            'intro' => $intro,
            'crumb' => $model->name,
            'metaTitle' => $model->meta_title ?: ('مقالات '.$model->name),
            'metaDescription' => $model->meta_description ?: $model->description,
            'jsonLd' => $this->collectionJsonLd($model->name, $intro, $posts),
        ]);
    }

    public function tag(string $tag): View
    {
        $model = BlogTag::query()->where('slug', $tag)->firstOrFail();

        $posts = $model->posts()
            ->published()
            ->with(['category', 'author'])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $heading = 'برچسب: '.$model->name;
        $intro = 'پست‌های برچسب‌خورده با «'.$model->name.'» در بلاگ '.config('theme.brand').'.';

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => $heading,
            'intro' => $intro,
            'crumb' => $model->name,
            'metaTitle' => $model->meta_title ?: ('پست‌های برچسب‌خورده با '.$model->name),
            'metaDescription' => $model->meta_description ?: $intro,
            'ogImage' => $model->og_image_url,
            'jsonLd' => $this->collectionJsonLd($heading, $intro, $posts),
        ]);
    }

    public function show(string $post): View
    {
        $article = BlogPost::query()
            ->published()
            ->where('slug', $post)
            ->with(['category', 'author', 'tags'])
            ->firstOrFail();

        // Lightweight view counter (no session/no dedupe — good enough for "popular").
        BlogPost::whereKey($article->getKey())->update(['views' => $article->views + 1]);

        $related = BlogPost::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->when($article->blog_category_id, fn ($q) => $q->where('blog_category_id', $article->blog_category_id))
            ->with('category')
            ->limit(3)
            ->get();

        if ($related->count() < 3) {
            $related = $related->merge(
                BlogPost::query()->published()
                    ->where('id', '!=', $article->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->latest('published_at')
                    ->limit(3 - $related->count())
                    ->get()
            );
        }

        $canonical = $article->canonical_url ?: route('blog.show', $article->slug);
        $published = optional($article->published_date)->toIso8601String();
        $modified = optional($article->updated_at)->toIso8601String();
        $authorName = $article->author?->name ?: config('theme.brand');

        return view('blog.show', [
            'article' => $article,
            'related' => $related,
            'metaTitle' => $article->meta_title_value,
            'metaDescription' => $article->meta_description_value,
            'canonical' => $canonical,
            'ogType' => 'article',
            'ogImage' => $article->og_image_url ?: (rtrim(config('theme.seo.url'), '/').config('theme.seo.image')),
            'noindex' => $article->noindex,
            'published' => $published,
            'modified' => $modified,
            'authorName' => $authorName,
            'jsonLd' => $this->articleJsonLd($article, $canonical, $published, $modified, $authorName),
        ]);
    }

    public function feed(): Response
    {
        $posts = BlogPost::query()->published()->with(['author', 'category'])->limit(30)->get();
        $brand = config('theme.brand');
        $site = rtrim(config('theme.seo.url'), '/');
        $self = route('blog.feed');
        $updated = optional(optional($posts->first())->published_date)->toRfc2822String();

        $items = $posts->map(function (BlogPost $post) {
            $url = route('blog.show', $post->slug);
            $desc = htmlspecialchars($post->meta_description_value, ENT_QUOTES);
            $date = optional($post->published_date)->toRfc2822String();
            $authorName = htmlspecialchars($post->author?->name ?: config('theme.brand'), ENT_QUOTES);
            $category = $post->category
                ? '<category>'.htmlspecialchars($post->category->name, ENT_QUOTES).'</category>'."\n                    "
                : '';

            return <<<XML
                <item>
                    <title>{$post->title}</title>
                    <link>{$url}</link>
                    <guid isPermaLink="true">{$url}</guid>
                    <pubDate>{$date}</pubDate>
                    <description>{$desc}</description>
                    {$category}<dc:creator>{$authorName}</dc:creator>
                </item>
            XML;
        })->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
        <channel>
            <title>بلاگ {$brand}</title>
            <link>{$site}/blog</link>
            <atom:link href="{$self}" rel="self" type="application/rss+xml" />
            <description>راهنماها و تحلیل‌های بازاریابی پیامکی {$brand}</description>
            <language>fa-IR</language>
            <lastBuildDate>{$updated}</lastBuildDate>
        {$items}
        </channel>
        </rss>
        XML;

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function sidebarCategories()
    {
        return BlogCategory::query()
            ->visible()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->posts_count > 0)
            ->values();
    }

    /** JSON-LD: a CollectionPage listing the current page of posts. */
    private function collectionJsonLd(string $heading, ?string $intro, LengthAwarePaginator $posts): string
    {
        $brand = config('theme.brand');
        $siteUrl = rtrim(config('theme.seo.url'), '/');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $heading,
            'description' => $intro,
            'url' => url()->current(),
            'isPartOf' => ['@type' => 'WebSite', 'name' => $brand, 'url' => $siteUrl.'/'],
            'hasPart' => $posts->map(fn ($p) => [
                '@type' => 'BlogPosting',
                'headline' => $p->title,
                'url' => route('blog.show', $p->slug),
                'datePublished' => optional($p->published_date)->toIso8601String(),
            ])->all(),
        ];

        return '<script type="application/ld+json">'
            .json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .'</script>';
    }

    /** JSON-LD: a BlogPosting plus a BreadcrumbList script, concatenated (two <script> tags). */
    private function articleJsonLd(BlogPost $article, string $canonical, ?string $published, ?string $modified, string $authorName): string
    {
        $brand = config('theme.brand');
        $siteUrl = rtrim(config('theme.seo.url'), '/');

        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $article->meta_description_value,
            'datePublished' => $published,
            'dateModified' => $modified ?: $published,
            'author' => ['@type' => 'Person', 'name' => $authorName],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $brand,
                'logo' => ['@type' => 'ImageObject', 'url' => $siteUrl.'/logo/logo-text.png'],
            ],
            'inLanguage' => 'fa-IR',
        ];

        if ($article->cover_url) {
            $articleSchema['image'] = [Str::startsWith($article->cover_url, 'http') ? $article->cover_url : $siteUrl.$article->cover_url];
        }
        if ($article->tags->isNotEmpty()) {
            $articleSchema['keywords'] = $article->tags->pluck('name')->implode('، ');
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl.'/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'بلاگ', 'item' => route('blog.index')],
                $article->category
                    ? ['@type' => 'ListItem', 'position' => 3, 'name' => $article->category->name, 'item' => route('blog.category', $article->category->slug)]
                    : null,
                ['@type' => 'ListItem', 'position' => $article->category ? 4 : 3, 'name' => $article->title, 'item' => $canonical],
            ])),
        ];

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        return '<script type="application/ld+json">'.json_encode($articleSchema, $flags).'</script>'
            .'<script type="application/ld+json">'.json_encode($breadcrumbSchema, $flags).'</script>';
    }
}
