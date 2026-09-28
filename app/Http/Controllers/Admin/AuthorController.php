<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorRequest;
use App\Models\Cms\Author;
use App\Support\MediaUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-users');

        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        return view('admin.authors.index', [
            'authors' => $authorModel::with(['user', 'media'])
                ->withCount('articles')
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * Save the order the rows were dragged into.
     *
     * Arrives as one comma-separated list of ids rather than an array of
     * inputs, because each row already carries its own delete form and a
     * form cannot be nested inside another one.
     */
    public function order(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $ids = collect(explode(',', (string) $request->input('order')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back();
        }

        // Only ids that are still there: a tab left open since yesterday must
        // not write a position for a profile someone has deleted since.
        $known = $authorModel::query()->whereIn('id', $ids)->pluck('id')->flip();

        DB::transaction(function () use ($ids, $known, $authorModel) {
            $position = 0;

            foreach ($ids as $id) {
                if (! $known->has($id)) {
                    continue;
                }

                $authorModel::query()->whereKey($id)->update(['position' => ++$position]);
            }
        });

        return back()->with('status', 'Η σειρά αποθηκεύτηκε.');
    }

    public function create(): View
    {
        $this->authorize('manage-users');

        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        return view('admin.authors.form', ['author' => new $authorModel]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $author = new $authorModel;
        $this->fill($author, $request);
        $author->save();

        MediaUpload::sync($author, $request, 'photo');

        return redirect()
            ->route('admin.authors.edit', $author)
            ->with('status', 'Ο συντάκτης δημιουργήθηκε.');
    }

    public function edit(Author $author): View
    {
        $this->authorize('manage-users');

        return view('admin.authors.form', compact('author'));
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        $this->fill($author, $request);
        $author->save();

        MediaUpload::sync($author, $request, 'photo');

        return back()->with('status', 'Οι αλλαγές αποθηκεύτηκαν.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $this->authorize('manage-users');

        // articles.author_id is nullOnDelete, so articles survive without a
        // byline rather than disappearing — but say so plainly.
        $count = $author->articles()->count();
        $name = $author->name;
        $author->delete();

        return redirect()->route('admin.authors.index')->with(
            'status',
            $count > 0
                ? "Ο συντάκτης «{$name}» διαγράφηκε. {$count} άρθρα έμειναν χωρίς συντάκτη."
                : "Ο συντάκτης «{$name}» διαγράφηκε."
        );
    }

    private function fill(Author $author, AuthorRequest $request): void
    {
        $data = $request->validated();

        $author->name = $data['name'];
        $author->role_title = $data['role_title'] ?? null;
        $author->bio = $data['bio'] ?? null;
        $author->is_active = $data['is_active'] ?? false;
    }
}
