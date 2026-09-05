<?php

namespace App\Http\Controllers;

use App\Models\DocArticle;
use App\Models\DocCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DocsController extends Controller
{
    /**
     * Docs landing page — API overview plus the full navigation tree.
     */
    public function index(): View
    {
        $tree = $this->navigationTree();
        $first = $this->firstArticle($tree);
        $brand = config('theme.brand');
        $pageTitle = 'مستندات API '.$brand;

        return view('docs.index', [
            'tree' => $tree,
            'firstArticle' => $first,
            'pageTitle' => $pageTitle,
            'jsonLd' => $this->indexJsonLd($tree, $pageTitle),
        ]);
    }

    /**
     * A category with no article of its own just forwards to its first article.
     */
    public function category(string $category): RedirectResponse
    {
        $model = DocCategory::query()
            ->published()
            ->where('slug', $category)
            ->firstOrFail();

        $article = $model->articles()->where('is_published', true)->first()
            ?? $model->children()->with(['articles' => fn ($q) => $q->where('is_published', true)])
                ->get()
                ->flatMap->articles
                ->first();

        abort_unless($article, Response::HTTP_NOT_FOUND);

        return redirect()->route('docs.show', [$model->slug, $article->slug]);
    }

    public function show(string $category, string $article): View
    {
        $categoryModel = DocCategory::query()
            ->published()
            ->where('slug', $category)
            ->firstOrFail();

        $articleModel = $categoryModel->articles()
            ->where('is_published', true)
            ->where('slug', $article)
            ->with(['codeSamples', 'parameters'])
            ->firstOrFail();

        $brand = config('theme.brand');
        $canonical = route('docs.show', [$categoryModel->slug, $articleModel->slug]);

        return view('docs.show', [
            'tree' => $this->navigationTree(),
            'category' => $categoryModel,
            'article' => $articleModel,
            'pageTitle' => ($articleModel->seo_title ?: $articleModel->title).' — مستندات '.$brand,
            'pageDescription' => $articleModel->seo_description ?: $articleModel->excerpt,
            'jsonLd' => $this->showJsonLd($articleModel, $categoryModel, $canonical),
        ]);
    }

    /**
     * Published top-level categories, each with published children and articles,
     * ready for the sidebar. Ordered by `sort`.
     */
    protected function navigationTree()
    {
        return DocCategory::query()
            ->published()
            ->whereNull('parent_id')
            ->with([
                'articles' => fn ($q) => $q->where('is_published', true),
                'children' => fn ($q) => $q->where('is_published', true),
                'children.articles' => fn ($q) => $q->where('is_published', true),
            ])
            ->orderBy('sort')
            ->orderBy('title')
            ->get();
    }

    protected function firstArticle($tree): ?DocArticle
    {
        foreach ($tree as $category) {
            if ($article = $category->articles->first()) {
                return $article;
            }

            foreach ($category->children as $child) {
                if ($article = $child->articles->first()) {
                    return $article;
                }
            }
        }

        return null;
    }

    /** JSON-LD graph: BreadcrumbList + CollectionPage/ItemList of every article. */
    private function indexJsonLd(Collection $tree, string $pageTitle): string
    {
        $brand = config('theme.brand');
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

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl.'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'مستندات API', 'item' => route('docs.index')],
                    ],
                ],
                [
                    '@type' => 'CollectionPage',
                    'name' => $pageTitle,
                    'url' => route('docs.index'),
                    'isPartOf' => ['@type' => 'WebSite', 'name' => $brand, 'url' => $siteUrl.'/'],
                    'hasPart' => ['@type' => 'ItemList', 'itemListElement' => $articleItems],
                ],
            ],
        ];

        return '<script type="application/ld+json">'
            .json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .'</script>';
    }

    /** JSON-LD: a TechArticle plus a BreadcrumbList script, concatenated (two <script> tags). */
    private function showJsonLd(DocArticle $article, DocCategory $category, string $canonical): string
    {
        $brand = config('theme.brand');
        $siteUrl = rtrim(config('theme.seo.url'), '/');
        $pageDescription = $article->seo_description ?: $article->excerpt;

        $techArticleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'TechArticle',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $pageDescription,
            'dateModified' => $article->updated_at?->toIso8601String(),
            'datePublished' => $article->created_at?->toIso8601String(),
            'inLanguage' => 'fa-IR',
            'author' => ['@type' => 'Organization', 'name' => $brand],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $brand,
                'logo' => ['@type' => 'ImageObject', 'url' => $siteUrl.'/logo/logo-text.png'],
            ],
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => $siteUrl.'/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'مستندات API', 'item' => route('docs.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $category->title, 'item' => $canonical],
                ['@type' => 'ListItem', 'position' => 4, 'name' => $article->title, 'item' => $canonical],
            ],
        ];

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        return '<script type="application/ld+json">'.json_encode($techArticleSchema, $flags).'</script>'
            .'<script type="application/ld+json">'.json_encode($breadcrumbSchema, $flags).'</script>';
    }
}
