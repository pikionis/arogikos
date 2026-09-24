<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlbumRequest;
use App\Models\Cms\Album;
use App\Models\Cms\Photo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Album> $albumModel */
        $albumModel = cms_model('album');

        return view('admin.gallery.index', [
            'albums' => $albumModel::withCount('photos')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Album> $albumModel */
        $albumModel = cms_model('album');

        return view('admin.gallery.form', ['album' => new $albumModel]);
    }

    public function store(AlbumRequest $request): RedirectResponse
    {
        /** @var class-string<Album> $albumModel */
        $albumModel = cms_model('album');

        $album = new $albumModel;
        $this->fill($album, $request);
        $album->save();

        return redirect()
            ->route('admin.gallery.edit', $album)
            ->with('status', 'Το άλμπουμ δημιουργήθηκε. Προσθέστε φωτογραφίες.');
    }

    public function edit(Album $album): View
    {
        $this->authorize('manage-content');

        return view('admin.gallery.form', [
            'album' => $album->load(['photos.media']),
        ]);
    }

    public function update(AlbumRequest $request, Album $album): RedirectResponse
    {
        $this->fill($album, $request);
        $album->save();

        return back()->with('status', 'Οι αλλαγές αποθηκεύτηκαν.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        $this->authorize('manage-content');

        // photos.album_id is cascadeOnDelete, so the photo rows go with it.
        // The view warns with the count before submitting.
        $title = $album->title;
        $album->delete();

        return redirect()
            ->route('admin.gallery.index')
            ->with('status', "Το άλμπουμ «{$title}» διαγράφηκε.");
    }

    /* -------------------------------------------------------------- photos */

    public function storePhotos(Request $request, Album $album): RedirectResponse
    {
        $this->authorize('manage-content');

        $validated = $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:max_width=5000,max_height=5000',
            ],
        ], [
            'photos.required' => 'Επιλέξτε τουλάχιστον μία εικόνα.',
            'photos.max' => 'Έως 20 εικόνες τη φορά.',
            'photos.*.image' => 'Επιτρέπονται μόνο εικόνες JPG, PNG ή WebP.',
            'photos.*.mimes' => 'Επιτρέπονται μόνο εικόνες JPG, PNG ή WebP.',
            'photos.*.max' => 'Κάθε εικόνα πρέπει να είναι έως 4 MB.',
            'photos.*.dimensions' => 'Κάθε εικόνα πρέπει να είναι έως 5000×5000.',
        ]);

        /** @var class-string<Photo> $photoModel */
        $photoModel = cms_model('photo');

        $next = (int) $album->photos()->max('sort_order');

        foreach ($validated['photos'] as $file) {
            $photo = $photoModel::create([
                'album_id' => $album->getKey(),
                'sort_order' => ++$next,
            ]);

            $photo->addMedia($file)
                ->sanitizingFileName(fn (string $name) => Str::slug(
                    pathinfo($name, PATHINFO_FILENAME), '-', 'el'
                ).'.'.pathinfo($name, PATHINFO_EXTENSION))
                ->toMediaCollection('image');
        }

        $count = count($validated['photos']);

        return back()->with('status', "Προστέθηκαν {$count} φωτογραφίες.");
    }

    public function updatePhoto(Request $request, Photo $photo): RedirectResponse
    {
        $this->authorize('manage-content');

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $photo->caption = $validated['caption'] ?? null;
        $photo->sort_order = $validated['sort_order'] ?? $photo->sort_order;
        $photo->save();

        return back()->with('status', 'Η φωτογραφία ενημερώθηκε.');
    }

    public function destroyPhoto(Photo $photo): RedirectResponse
    {
        $this->authorize('manage-content');

        $photo->delete();

        return back()->with('status', 'Η φωτογραφία διαγράφηκε.');
    }

    private function fill(Album $album, AlbumRequest $request): void
    {
        $data = $request->validated();

        $album->title = $data['title'];
        $album->description = $data['description'] ?? null;
        $album->sort_order = $data['sort_order'] ?? 0;

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug !== '') {
            $album->slug = Str::slug($slug, '-', 'el');
        }
    }
}
