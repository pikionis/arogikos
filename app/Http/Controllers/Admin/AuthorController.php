<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorRequest;
use App\Models\Cms\Author;
use App\Support\MediaUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AuthorController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        return view('admin.authors.index', [
            'authors' => $authorModel::with(['user', 'media'])
                ->withCount('articles')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-content');

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
        $this->authorize('manage-content');

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
        $this->authorize('manage-content');

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
