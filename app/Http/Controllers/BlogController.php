<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Article;
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

        // Chips offer the active categories; an archived one is no longer a
        // filter, but articles carrying it still render their own label.
        $categories = app(Categories::class)->active();

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
            ->orderBy('name')
            ->get(['id', 'name', 'role_title']);

        $author = $request->integer('author') ?: null;
        if ($author !== null && ! $authors->contains('id', $author)) {
            $author = null;
        }

        $articles = $articleModel::published()
            ->inCategory($category)
            ->when($author, fn ($q, $id) => $q->where('author_id', $id))
            ->with(['author', 'media'])
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
            ->with(['author', 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $article->related(3);

        return view('pages.blog.show', compact('article', 'related'));
    }
}
