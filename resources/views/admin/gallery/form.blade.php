@extends('admin.layouts.app')

@php $isNew = ! $album->exists; @endphp

@section('title', $isNew ? 'Νέο άλμπουμ' : 'Επεξεργασία άλμπουμ')
@section('heading', $isNew ? 'Νέο άλμπουμ' : $album->title)

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;gap:18px;max-width:900px">

        <form method="POST"
              action="{{ $isNew ? route('admin.gallery.store') : route('admin.gallery.update', $album) }}">
            @csrf
            @unless ($isNew) @method('PUT') @endunless

            <section class="a-card">
                <div class="a-field">
                    <label class="a-label" for="title">Τίτλος άλμπουμ</label>
                    <input class="a-input" id="title" name="title" type="text" required maxlength="200"
                           value="{{ old('title', $album->title) }}">
                    @error('title')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label class="a-label" for="description">Περιγραφή</label>
                    <textarea class="a-textarea" id="description" name="description" maxlength="1000"
                              style="min-height:70px">{{ old('description', $album->description) }}</textarea>
                    @error('description')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="sort_order">Σειρά εμφάνισης</label>
                        <input class="a-input" id="sort_order" name="sort_order" type="number" min="0" max="9999"
                               value="{{ old('sort_order', $album->sort_order ?? 0) }}">
                        <span class="a-hint">Μικρότερος αριθμός = πιο μπροστά.</span>
                        @error('sort_order')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="slug">Slug</label>
                        <input class="a-input" id="slug" name="slug" type="text" maxlength="200"
                               value="{{ old('slug', $album->slug) }}"
                               placeholder="Αυτόματο από τον τίτλο">
                        @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div style="margin-top:18px">
                    <button class="a-btn a-btn-primary" type="submit">
                        {{ $isNew ? 'Δημιουργία άλμπουμ' : 'Αποθήκευση' }}
                    </button>
                </div>
            </section>
        </form>

        @unless ($isNew)
            <section class="a-card">
                <h2 style="font-size:1rem;margin:0 0 14px">Προσθήκη φωτογραφιών</h2>

                <form method="POST" action="{{ route('admin.gallery.photos.store', $album) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="a-field">
                        <input class="a-input" type="file" name="photos[]" multiple required
                               accept="image/jpeg,image/png,image/webp"
                               style="padding:7px 9px;font-size:.85rem">
                        <span class="a-hint">JPG, PNG ή WebP · έως 4 MB η καθεμία · έως 20 τη φορά.</span>
                        @error('photos')<span class="a-error">{{ $message }}</span>@enderror
                        @error('photos.*')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                    <button class="a-btn a-btn-primary" type="submit">Μεταφόρτωση</button>
                </form>
            </section>

            <section class="a-card">
                <h2 style="font-size:1rem;margin:0 0 14px">
                    Φωτογραφίες ({{ $album->photos->count() }})
                </h2>

                @if ($album->photos->isEmpty())
                    <p style="color:var(--color-ink-400);margin:0">
                        Δεν υπάρχουν φωτογραφίες σε αυτό το άλμπουμ ακόμα.
                    </p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px">
                        @foreach ($album->photos as $photo)
                            <div style="border:1px solid var(--admin-border);border-radius:9px;overflow:hidden">
                                <div style="aspect-ratio:4/3;background:var(--color-soft-green)">
                                    @php $thumb = $photo->imageUrl('thumb'); @endphp
                                    @if ($thumb)
                                        <img src="{{ $thumb }}" alt="{{ $photo->caption }}"
                                             style="width:100%;height:100%;object-fit:cover">
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('admin.gallery.photos.update', $photo) }}"
                                      style="padding:10px">
                                    @csrf @method('PATCH')
                                    <input class="a-input" name="caption" type="text" maxlength="200"
                                           value="{{ $photo->caption }}" placeholder="Λεζάντα"
                                           style="font-size:.82rem;margin-bottom:6px">
                                    <div style="display:flex;gap:6px">
                                        <input class="a-input" name="sort_order" type="number" min="0" max="9999"
                                               value="{{ $photo->sort_order }}"
                                               style="width:74px;font-size:.82rem" aria-label="Σειρά">
                                        <button class="a-btn a-btn-ghost a-btn-sm" type="submit" style="flex:1">
                                            Αποθήκευση
                                        </button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.gallery.photos.destroy', $photo) }}"
                                      style="padding:0 10px 10px"
                                      x-data="confirmable(@js('Διαγραφή αυτής της φωτογραφίας;'))"
                                      @submit="confirm($event)">
                                    @csrf @method('DELETE')
                                    <button class="a-btn a-btn-danger a-btn-sm" type="submit" style="width:100%">
                                        Διαγραφή
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endunless
    </div>

@endsection
