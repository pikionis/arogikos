@extends('admin.layouts.app')

@php $isNew = ! $author->exists; @endphp

@section('title', $isNew ? 'Νέος συντάκτης' : 'Επεξεργασία συντάκτη')
@section('heading', $isNew ? 'Νέος συντάκτης' : $author->name)

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isNew ? route('admin.authors.store') : route('admin.authors.update', $author) }}"
          enctype="multipart/form-data">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:18px;align-items:start">

            <section class="a-card">
                <div class="a-field">
                    <label class="a-label" for="name">Ονοματεπώνυμο</label>
                    <input class="a-input" id="name" name="name" type="text" required maxlength="120"
                           value="{{ old('name', $author->name) }}">
                    @error('name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label class="a-label" for="role_title">Ιδιότητα</label>
                    <input class="a-input" id="role_title" name="role_title" type="text" maxlength="120"
                           value="{{ old('role_title', $author->role_title) }}"
                           placeholder="π.χ. Ειδική Παιδαγωγός">
                    @error('role_title')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field" style="margin-bottom:0">
                    <label class="a-label" for="bio">Σύντομο βιογραφικό</label>
                    <textarea class="a-textarea" id="bio" name="bio" maxlength="2000">{{ old('bio', $author->bio) }}</textarea>
                    <span class="a-hint">Απλό κείμενο, χωρίς μορφοποίηση.</span>
                    @error('bio')<span class="a-error">{{ $message }}</span>@enderror
                </div>
            </section>

            <div style="display:grid;gap:18px">
                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Εμφάνιση στο site</h2>

                    <div class="a-field">
                        <label class="a-check">
                            <input type="checkbox" name="is_active" value="1"
                                   @checked(old('is_active', $author->is_active))>
                            <span>Εμφάνιση στη σελίδα «Σχετικά με εμάς»</span>
                        </label>
                        <span class="a-hint">
                            Ανεξάρτητο από το αν μπορεί να υπογράψει άρθρο — αυτό είναι
                            πάντα δυνατό.
                        </span>
                    </div>

                    @if ($author->exists && $author->user)
                        <div class="a-alert a-alert-ok" style="font-size:.85rem;margin-bottom:14px">
                            Συνδεδεμένο με τον λογαριασμό
                            <strong>{{ $author->user->email }}</strong>
                            ({{ $author->user->role->label() }}).
                        </div>
                    @endif

                    <button class="a-btn a-btn-primary" type="submit" style="width:100%">
                        {{ $isNew ? 'Δημιουργία' : 'Αποθήκευση' }}
                    </button>
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Φωτογραφία</h2>
                    <x-admin.image-field name="photo" :model="$author" collection="photo" />
                </section>
            </div>
        </div>
    </form>

@endsection
