@extends('admin.layouts.app')

@php $isNew = ! $article->exists; @endphp

@section('title', $isNew ? 'Νέο άρθρο' : 'Επεξεργασία άρθρου')
@section('heading', $isNew ? 'Νέο άρθρο' : $article->title)
@section('subheading', $isNew ? 'Συμπληρώστε το περιεχόμενο και δημοσιεύστε.' : 'Επεξεργασία άρθρου.')

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isNew ? route('admin.articles.store') : route('admin.articles.update', $article) }}"
          enctype="multipart/form-data">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:18px;align-items:start">

            {{-- ------------------------------------------------------- main --}}
            <div style="display:grid;gap:18px">
                <section class="a-card">
                    <div class="a-field">
                        <label class="a-label" for="title">Τίτλος</label>
                        <input class="a-input" id="title" name="title" type="text" required maxlength="200"
                               value="{{ old('title', $article->title) }}"
                               @error('title') aria-invalid="true" @enderror>
                        @error('title')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label class="a-label" for="excerpt">Περίληψη</label>
                        <textarea class="a-textarea" id="excerpt" name="excerpt" maxlength="500"
                                  style="min-height:80px">{{ old('excerpt', $article->excerpt) }}</textarea>
                        <span class="a-hint">Εμφανίζεται στις κάρτες του blog και ως fallback στο SEO.</span>
                        @error('excerpt')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="body">Κείμενο</label>
                        <x-admin.rich-text name="body" :value="old('body', $article->body)" />
                        @error('body')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 4px">Πώς θα φαίνεται στη Google</h2>
                    <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 14px">
                        Αν αφήσετε τα πεδία κενά, χρησιμοποιούνται ο τίτλος και η περίληψη
                        του άρθρου.
                    </p>

                    <x-admin.seo-preview
                        title-name="seo_title"
                        description-name="seo_description"
                        fallback-title-name="title"
                        fallback-description-name="excerpt"
                        title-suffix=" — Αρωγή"
                        :url="url('/blog/…')" />

                    <div class="a-field">
                        <label class="a-label" for="seo_title">Τίτλος SEO</label>
                        <input class="a-input" id="seo_title" name="seo_title" type="text" maxlength="200"
                               value="{{ old('seo_title', $article->seo_title) }}"
                               placeholder="Αν μείνει κενό, χρησιμοποιείται ο τίτλος">
                        @error('seo_title')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="seo_description">Περιγραφή SEO</label>
                        <textarea class="a-textarea" id="seo_description" name="seo_description" maxlength="300"
                                  style="min-height:70px">{{ old('seo_description', $article->seo_description) }}</textarea>
                        <span class="a-hint">Αν μείνει κενή, χρησιμοποιείται η περίληψη του άρθρου.</span>
                        @error('seo_description')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>
            </div>

            {{-- ------------------------------------------------------ aside --}}
            <div style="display:grid;gap:18px">
                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Δημοσίευση</h2>

                    <div class="a-field">
                        <label class="a-check">
                            <input type="checkbox" name="is_published" value="1"
                                   @checked(old('is_published', $article->is_published))>
                            <span>Δημοσιευμένο</span>
                        </label>
                    </div>

                    <div class="a-field">
                        <label class="a-label" for="published_at">Ημερομηνία δημοσίευσης</label>
                        <input class="a-input" id="published_at" name="published_at" type="datetime-local"
                               value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                        <span class="a-hint">
                            Ώρα Ελλάδας. Μελλοντική ημερομηνία κρατά το άρθρο κρυφό μέχρι τότε.
                        </span>
                        @error('published_at')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <button class="a-btn a-btn-primary" type="submit" style="width:100%">
                        {{ $isNew ? 'Δημιουργία άρθρου' : 'Αποθήκευση' }}
                    </button>

                    @unless ($isNew)
                        @if ($article->is_published)
                            <a class="a-btn a-btn-ghost a-btn-sm" style="width:100%;margin-top:8px"
                               href="{{ route('blog.show', $article->slug) }}" target="_blank" rel="noopener">
                                Προβολή στο site ↗
                            </a>
                        @endif
                    @endunless
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Συντάκτης</h2>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="author_id">Ποιος υπογράφει το άρθρο</label>
                        <select class="a-select" id="author_id" name="author_id">
                            <option value="">— Χωρίς συντάκτη —</option>
                            @foreach ($authorGroups as $groupLabel => $authors)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach ($authors as $author)
                                        <option value="{{ $author->id }}"
                                                @selected((int) old('author_id', $article->author_id) === $author->id)>
                                            {{ $author->name }}@if ($author->role_title) — {{ $author->role_title }}@endif
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <span class="a-hint">
                            Τα «Μέλη του panel» είναι όσοι έχουν πρόσβαση στη διαχείριση.
                        </span>
                        @error('author_id')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>

                <section class="a-card">
                    <x-admin.category-picker :categories="$categories" :selected="$article->category" />
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Εξώφυλλο</h2>
                    <x-admin.image-field name="cover" :model="$article" collection="cover" />
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Διεύθυνση (slug)</h2>
                    <div class="a-field" style="margin-bottom:0">
                        <input class="a-input" id="slug" name="slug" type="text" maxlength="200"
                               value="{{ old('slug', $article->slug) }}"
                               placeholder="Παράγεται αυτόματα από τον τίτλο">
                        <span class="a-hint">
                            @if ($isNew)
                                Αν μείνει κενό, παράγεται από τον τίτλο.
                            @else
                                <strong>Προσοχή:</strong> η αλλαγή του slug σπάει τους υπάρχοντες συνδέσμους
                                προς το άρθρο.
                            @endif
                        </span>
                        @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>
            </div>
        </div>
    </form>

@endsection
