<?php

namespace App\Http\Controllers;

use App\Models\DocArticle;
use App\Models\DocCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

        return view('docs.index', [
            'tree' => $tree,
            'firstArticle' => $first,
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

        return view('docs.show', [
            'tree' => $this->navigationTree(),
            'category' => $categoryModel,
            'article' => $articleModel,
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
}
