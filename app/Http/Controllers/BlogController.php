<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Article;
use App\Support\Categories;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        // Chips offer the active categories; an archived one is no longer a
        // filter, but articles carrying it still render their own label.
        $categories = app(Categories::class)->active();

        $category = $request->query('category');
        if (! is_string($category) || ! array_key_exists($category, $categories)) {
            $category = null;
        }

        $articles = $articleModel::published()
            ->inCategory($category)
            ->with(['author', 'media'])
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('pages.blog.index', compact('articles', 'categories', 'category'));
    }

    public function show(string $slug): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        $article = $articleModel::published()
            ->with(['author', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $article->related(3);

        return view('pages.blog.show', compact('article', 'related'));
    }
}
