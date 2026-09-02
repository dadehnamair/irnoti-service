<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => 'بلاگ '.config('theme.brand'),
            'intro' => 'راهنماها و تحلیل‌های کاربردی دربارهٔ بازاریابی پیامکی، افزایش فروش و ارتباط با مشتری.',
            'crumb' => null,
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

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => $model->name,
            'intro' => $model->description,
            'crumb' => $model->name,
            'metaTitle' => $model->meta_title ?: ('مقالات '.$model->name),
            'metaDescription' => $model->meta_description ?: $model->description,
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

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->sidebarCategories(),
            'heading' => 'برچسب: '.$model->name,
            'intro' => null,
            'crumb' => $model->name,
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

        return view('blog.show', [
            'article' => $article,
            'related' => $related,
        ]);
    }

    public function feed(): Response
    {
        $posts = BlogPost::query()->published()->with('author')->limit(30)->get();
        $brand = config('theme.brand');
        $site = rtrim(config('theme.seo.url'), '/');
        $self = route('blog.feed');
        $updated = optional(optional($posts->first())->published_date)->toRfc2822String();

        $items = $posts->map(function (BlogPost $post) {
            $url = route('blog.show', $post->slug);
            $desc = htmlspecialchars($post->meta_description_value, ENT_QUOTES);
            $date = optional($post->published_date)->toRfc2822String();

            return <<<XML
                <item>
                    <title>{$post->title}</title>
                    <link>{$url}</link>
                    <guid isPermaLink="true">{$url}</guid>
                    <pubDate>{$date}</pubDate>
                    <description>{$desc}</description>
                </item>
            XML;
        })->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
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
}
