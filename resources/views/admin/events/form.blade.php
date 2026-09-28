@extends('admin.layouts.app')

@php $isNew = ! $event->exists; @endphp

@section('title', $isNew ? 'Νέα εκδήλωση' : 'Επεξεργασία εκδήλωσης')
@section('heading', $isNew ? 'Νέα εκδήλωση' : $event->title)

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
          action="{{ $isNew ? route('admin.events.store') : route('admin.events.update', $event) }}"
          enctype="multipart/form-data">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:18px;align-items:start">

            <div style="display:grid;gap:18px">
                <section class="a-card">
                    <div class="a-field">
                        <label class="a-label" for="title">Τίτλος</label>
                        <input class="a-input" id="title" name="title" type="text" required maxlength="200"
                               value="{{ old('title', $event->title) }}">
                        @error('title')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="body">Περιγραφή</label>
                        <x-admin.rich-text name="body" :value="old('body', $event->body)" />
                        @error('body')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>
            </div>

            <div style="display:grid;gap:18px">
                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Πότε &amp; πού</h2>

                    <div class="a-field">
                        <label class="a-label" for="starts_at">Ημερομηνία &amp; ώρα</label>
                        <input class="a-input" id="starts_at" name="starts_at" type="datetime-local"
                               value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}">
                        <span class="a-hint">Ώρα Ελλάδας. Καθορίζει αν εμφανίζεται ως επερχόμενη.</span>
                        @error('starts_at')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field" style="margin-bottom:0">
                        <label class="a-label" for="location">Τοποθεσία</label>
                        <input class="a-input" id="location" name="location" type="text" maxlength="200"
                               value="{{ old('location', $event->location) }}"
                               placeholder="π.χ. Αίθουσα Αρωγής, Κως">
                        @error('location')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Δημοσίευση</h2>
                    <div class="a-field">
                        <label class="a-check">
                            <input type="checkbox" name="is_published" value="1"
                                   @checked(old('is_published', $event->is_published))>
                            <span>Δημοσιευμένη</span>
                        </label>
                    </div>
                    <button class="a-btn a-btn-primary" type="submit" style="width:100%">
                        {{ $isNew ? 'Δημιουργία' : 'Αποθήκευση' }}
                    </button>

                    @unless ($isNew)
                        {{-- Οι εκδηλώσεις δεν έχουν δική τους σελίδα: η
                             προεπισκόπηση δείχνει τη λίστα με την εκδήλωση
                             στη θέση που θα πάρει μόλις δημοσιευτεί. --}}
                        <a class="a-btn a-btn-ghost a-btn-sm" style="width:100%;margin-top:8px"
                           href="{{ route('admin.preview.event', $event) }}" target="_blank" rel="noopener">
                            Προεπισκόπηση ↗
                        </a>

                        @if ($event->is_published)
                            <a class="a-btn a-btn-ghost a-btn-sm" style="width:100%;margin-top:8px"
                               href="{{ route('events') }}" target="_blank" rel="noopener">
                                Προβολή στο site ↗
                            </a>
                        @endif
                    @endunless
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Εξώφυλλο</h2>
                    <x-admin.image-field name="cover" :model="$event" collection="cover" />
                </section>

                <section class="a-card">
                    <h2 style="font-size:.95rem;margin:0 0 14px">Διεύθυνση (slug)</h2>
                    <div class="a-field" style="margin-bottom:0">
                        <input class="a-input" id="slug" name="slug" type="text" maxlength="200"
                               value="{{ old('slug', $event->slug) }}"
                               placeholder="Παράγεται αυτόματα από τον τίτλο">
                        @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </section>
            </div>
        </div>
    </form>

@endsection
