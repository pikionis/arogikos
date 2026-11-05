<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Article;
use App\Models\Cms\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Categories, managed inline from the article form.
 *
 * JSON endpoints rather than full page loads: the panel opens beside the
 * category picker while the author is mid-article, and a redirect would throw
 * away everything they had typed.
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('manage-content');

        return response()->json(['categories' => $this->payload()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-content');

        $validated = $request->validate($this->rules());

        $key = $this->makeKey($validated['label']);

        Category::create([
            'key' => $key,
            'label' => $validated['label'],
            'color' => $validated['color'],
            'motif' => $validated['motif'],
            'sort_order' => (int) (Category::max('sort_order') ?? 0) + 10,
        ]);

        return response()->json([
            'categories' => $this->payload(),
            'created' => $key,
            'message' => 'Η κατηγορία προστέθηκε.',
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorize('manage-content');

        $validated = $request->validate($this->rules());

        // `key` is deliberately immutable: it is the value stored on every
        // article that uses this category, so renaming it would orphan them.
        // The visible label is what staff actually want to change.
        $category->update([
            'label' => $validated['label'],
            'color' => $validated['color'],
            'motif' => $validated['motif'],
        ]);

        return response()->json([
            'categories' => $this->payload(),
            'message' => 'Η κατηγορία ενημερώθηκε.',
        ]);
    }

    /**
     * Remove a category.
     *
     * Unused → deleted outright. In use → archived instead: the articles that
     * carry it keep their category exactly as it was chosen, label, colour and
     * motif included. It simply stops being offered for new articles and stops
     * appearing as a filter on /blog.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('manage-content');

        $inUse = $this->articleCount($category->key);

        if ($inUse === 0) {
            $category->delete();

            return response()->json([
                'categories' => $this->payload(),
                'message' => 'Η κατηγορία διαγράφηκε.',
            ]);
        }

        $category->forceFill(['archived_at' => now()])->save();

        return response()->json([
            'categories' => $this->payload(),
            'message' => "Η κατηγορία αποσύρθηκε. Τα {$inUse} άρθρα που την έχουν δεν άλλαξαν.",
        ]);
    }

    /** Bring an archived category back into the picker. */
    public function restore(Category $category): JsonResponse
    {
        $this->authorize('manage-content');

        $category->forceFill(['archived_at' => null])->save();

        return response()->json([
            'categories' => $this->payload(),
            'message' => 'Η κατηγορία επανήλθε.',
        ]);
    }

    /* ------------------------------------------------------------- helpers */

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'color' => ['required', Rule::in(array_keys(Category::COLORS))],
            'motif' => ['required', Rule::in(array_keys(Category::MOTIFS))],
        ];
    }

    /**
     * A URL-safe key from the Greek label, kept unique.
     *
     * Str::slug with the 'el' locale transliterates, so "Εφηβεία" becomes
     * "efiveia" rather than an empty string.
     */
    private function makeKey(string $label): string
    {
        $base = Str::slug($label, '-', 'el') ?: 'katigoria';
        $key = $base;
        $n = 1;

        while (Category::where('key', $key)->exists()) {
            $key = $base.'-'.++$n;
        }

        return $key;
    }

    private function articleCount(string $key): int
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');

        return $articleModel::whereHas(
            'categoryLinks',
            fn ($q) => $q->where('category_key', $key)
        )->count();
    }

    /** @return array<int, array<string, mixed>> */
    private function payload(): array
    {
        return Category::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'key' => $c->key,
                'label' => $c->label,
                'color' => $c->color,
                'motif' => $c->motif,
                'archived' => $c->isArchived(),
                'articles' => $this->articleCount($c->key),
            ])
            ->values()
            ->all();
    }
}
