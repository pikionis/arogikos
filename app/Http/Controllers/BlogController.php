<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Article;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\Author;
use App\Support\Categories;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        // The picker offers an active category only once something published
        // is filed under it: an archived one is no longer a filter, and a
        // brand new one that nothing uses yet would lead to an empty list,
        // which is the same dead end the author picker already avoids.
        // Articles carrying an archived category still render its label.
        $filed = ArticleCategory::query()
            ->whereIn('article_id', $articleModel::published()->select('id'))
            ->distinct()
            ->pluck('category_key')
            ->all();

        $categories = array_intersect_key(
            app(Categories::class)->active(),
            array_flip($filed)
        );

        $category = $request->query('category');
        if (! is_string($category) || ! array_key_exists($category, $categories)) {
            $category = null;
        }

        // Only authors who actually have something published: a chip that
        // leads to an empty list is a dead end, and an author who has written
        // nothing yet is not news to a reader.
        $authors = Author::query()
            ->whereHas('articles', fn ($q) => $q->published())
            // Photo and role come along: the picker shows who someone is,
            // not just a name they may not recognise.
            ->with('media')
            ->ordered()
            ->get(['id', 'name', 'role_title', 'position']);

        $author = $request->integer('author') ?: null;
        if ($author !== null && ! $authors->contains('id', $author)) {
            $author = null;
        }

        $articles = $articleModel::published()
            ->inCategory($category)
            ->when($author, fn ($q, $id) => $q->whereHas('authors', fn ($a) => $a->whereKey($id)))
            ->with(['authors', 'categoryLinks', 'media'])
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('pages.blog.index', compact('articles', 'categories', 'category', 'authors', 'author'));
    }

    public function show(string $slug): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        $article = $articleModel::published()
            ->with(['authors', 'categoryLinks', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $article->related();

        return view('pages.blog.show', compact('article', 'related'));
    }
}
