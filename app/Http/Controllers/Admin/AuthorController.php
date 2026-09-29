<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthorRequest;
use App\Models\Cms\Author;
use App\Models\User;
use App\Support\MediaUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-users');

        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $authors = $authorModel::with(['user', 'media'])
            ->withCount('articles')
            ->ordered()
            ->get();

        // A profile that says "χωρίς πρόσβαση" beside an account of the same
        // name is almost always one nobody got round to linking. Naming them
        // here is what turns that back into a two-click fix.
        $free = $this->freeAccounts()->keyBy(fn (User $user) => Author::normaliseName($user->name));

        return view('admin.authors.index', [
            'authors' => $authors,
            'suggested' => $authors
                ->filter(fn (Author $author) => $author->user_id === null)
                ->mapWithKeys(fn (Author $author) => [
                    $author->getKey() => $free->get(Author::normaliseName($author->name)),
                ])
                ->filter(),
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

        return view('admin.authors.form', [
            'author' => new $authorModel,
            'accounts' => $this->freeAccounts(),
        ]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        $this->authorize('manage-users');

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

        return view('admin.authors.form', [
            'author' => $author,
            // Plus the one already on this profile, which is not free but has
            // to stay selected.
            'accounts' => $this->freeAccounts($author->user),
        ]);
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        $this->authorize('manage-users');

        $this->fill($author, $request);
        $author->save();

        MediaUpload::sync($author, $request, 'photo');

        return back()->with('status', 'Οι αλλαγές αποθηκεύτηκαν.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $this->authorize('manage-users');

        // The byline is a pivot row with cascade on delete, so the articles
        // stay standing and lose this one name — but say so plainly.
        $count = $author->articles()->count();
        $name = $author->name;
        $author->delete();

        return redirect()->route('admin.authors.index')->with(
            'status',
            $count > 0
                ? "Ο συντάκτης «{$name}» διαγράφηκε. {$count} άρθρα χάνουν αυτή την υπογραφή."
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

        // Linking is what lets someone edit their own photo and bio, and what
        // makes the "Λογαριασμός" column tell the truth.
        $author->user_id = $data['user_id'] ?? null;
    }

    /**
     * Accounts that can still be linked to a profile.
     *
     * One account owns at most one profile (authors.user_id is unique), so
     * offering a taken one would only produce a constraint error at the end
     * of a filled-in form.
     *
     * @return Collection<int, User>
     */
    private function freeAccounts(?User $keep = null): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($keep) {
                $query->whereDoesntHave('author');

                if ($keep) {
                    $query->orWhere('id', $keep->getKey());
                }
            })
            ->orderBy('name')
            ->get();
    }
}
