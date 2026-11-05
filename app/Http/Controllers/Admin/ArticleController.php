<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Models\Cms\Article;
use App\Support\AuthorPicker;
use App\Support\Categories;
use App\Support\MediaUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $articles = $articleModel::query()
            ->with(['authors', 'categoryLinks', 'media'])
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($inner) => $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
            ))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.articles.index', compact('articles', 'status', 'search'));
    }

    public function create(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        return view('admin.articles.form', [
            'article' => new $articleModel,
            'authorGroups' => AuthorPicker::groups(),
            'categories' => app(Categories::class)->forPicker(),
        ]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        $article = new $articleModel;
        $this->fill($article, $request);
        $article->save();
        $this->signBy($article, $request);
        $this->fileUnder($article, $request);

        MediaUpload::sync($article, $request, 'cover');

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status', 'Το άρθρο δημιουργήθηκε.');
    }

    public function edit(Article $article): View
    {
        $this->authorize('manage-content');

        return view('admin.articles.form', [
            'article' => $article,
            'authorGroups' => AuthorPicker::groups(),
            // Includes this article's own categories even if one was
            // withdrawn, so saving never drops it.
            'categories' => app(Categories::class)->forPicker(...$article->categoryKeys()),
        ]);
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->fill($article, $request);
        $article->save();
        $this->signBy($article, $request);
        $this->fileUnder($article, $request);

        MediaUpload::sync($article, $request, 'cover');

        return back()->with('status', 'Οι αλλαγές αποθηκεύτηκαν.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('manage-content');

        $title = $article->title;
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('status', "Το άρθρο «{$title}» διαγράφηκε.");
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * What it is about.
     *
     * The order the boxes were ticked in is kept: the first category colours
     * the card and chooses the drawing an article without a cover falls back
     * to, so it is worth being able to decide which one that is.
     */
    private function fileUnder(Article $article, ArticleRequest $request): void
    {
        $keys = array_values($request->validated()['category_keys'] ?? []);

        $article->categoryLinks()->delete();

        foreach ($keys as $i => $key) {
            $article->categoryLinks()->create(['category_key' => $key, 'position' => $i + 1]);
        }

        $article->unsetRelation('categoryLinks');
    }

    /**
     * Who signs it.
     *
     * After the save, not inside fill(): a brand new article has no id to
     * hang the pivot rows off until it has been written once.
     */
    private function signBy(Article $article, ArticleRequest $request): void
    {
        $ids = array_map('intval', $request->validated()['author_ids'] ?? []);

        $article->authors()->sync(array_values(array_unique($ids)));
        $article->unsetRelation('authors');
    }

    private function fill(Article $article, ArticleRequest $request): void
    {
        $data = $request->validated();

        $article->title = $data['title'];
        $article->excerpt = $data['excerpt'] ?? null;
        $article->body = $data['body'] ?? null;
        // Empty means "no ready-made scene", which is a null and not a '' —
        // the accessor looks the key up in the registry either way.
        $article->cover_preset = filled($data['cover_preset'] ?? null) ? $data['cover_preset'] : null;
        $article->seo_title = $data['seo_title'] ?? null;
        $article->seo_description = $data['seo_description'] ?? null;
        $article->is_published = $data['is_published'] ?? false;

        // HasSlug generates one on create and deliberately never regenerates
        // on update (doNotGenerateSlugsOnUpdate), so an explicit value from the
        // form is the only way to change a published article's URL.
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug !== '') {
            $article->slug = Str::slug($slug, '-', 'el');
        }

        // Publishing without a date would make the article invisible: the
        // published() scope requires published_at to be set and in the past.
        $publishedAt = $data['published_at'] ?? null;
        if ($publishedAt) {
            $article->published_at = $publishedAt;
        } elseif ($article->is_published && ! $article->published_at) {
            $article->published_at = now();
        }
    }
}
