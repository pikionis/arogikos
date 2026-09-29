@extends('admin.layouts.app')

@section('title', 'Το προφίλ μου')
@section('heading', 'Το προφίλ μου')
@section('subheading', 'Τα στοιχεία και ο κωδικός του λογαριασμού σας.')

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;gap:18px;max-width:560px">

        <section class="a-card">
            <h2 style="font-size:1rem;margin:0 0 4px">Στοιχεία</h2>
            <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                Ο ρόλος σας είναι <strong>{{ $user->role->label() }}</strong> και δεν αλλάζει από εδώ.
            </p>

            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf @method('PUT')

                <div class="a-field">
                    <label class="a-label" for="name">Όνομα</label>
                    <input class="a-input" id="name" name="name" type="text" required maxlength="120"
                           value="{{ old('name', $user->name) }}">
                </div>

                <div class="a-field">
                    <label class="a-label" for="email">Email</label>
                    <input class="a-input" id="email" name="email" type="email" required maxlength="160"
                           value="{{ old('email', $user->email) }}">
                    <span class="a-hint">Με αυτό συνδέεστε.</span>
                </div>

                <button class="a-btn a-btn-primary" type="submit">Αποθήκευση</button>
            </form>
        </section>

        <section class="a-card">
            <h2 style="font-size:1rem;margin:0 0 4px">Το δημόσιο προφίλ μου</h2>
            <p class="a-hint" style="margin-bottom:16px">
                Έτσι εμφανίζεστε στα άρθρα σας και, αν σας έχουν ενεργοποιήσει,
                στην ομάδα του «Σχετικά με εμάς».
            </p>

            @if ($author)
                <form method="POST" action="{{ route('admin.profile.author') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="a-field">
                        <label class="a-label" for="role_title">Ιδιότητα</label>
                        <input class="a-input" id="role_title" name="role_title" type="text" maxlength="120"
                               value="{{ old('role_title', $author->role_title) }}"
                               placeholder="π.χ. Ειδική Παιδαγωγός">
                        <span class="a-hint">Μπαίνει κάτω από το όνομά σας, στα άρθρα και στην ομάδα.</span>
                        @error('role_title')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label class="a-label" for="bio">Βιογραφικό</label>
                        <textarea class="a-textarea" id="bio" name="bio" maxlength="2000">{{ old('bio', $author->bio) }}</textarea>
                        <span class="a-hint">
                            Πάνω από 180 χαρακτήρες εμφανίζεται κομμένο, με κουμπί «Περισσότερα»
                            που ανοίγει ολόκληρο.
                        </span>
                        @error('bio')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label class="a-label">Φωτογραφία</label>
                        <x-admin.image-field name="photo" :model="$author" collection="photo" />
                        <x-admin.photo-crop :author="$author" />
                        @error('photo')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <button class="a-btn a-btn-primary" type="submit">Αποθήκευση προφίλ</button>
                </form>
            @else
                <div class="a-alert a-alert-warn">
                    Ο λογαριασμός σας δεν έχει ακόμη προφίλ συντάκτη. Ζητήστε από τον
                    διαχειριστή να σας συνδέσει με έναν.
                </div>
            @endif
        </section>

        <section class="a-card">
            <h2 style="font-size:1rem;margin:0 0 4px">Αλλαγή κωδικού</h2>
            <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                Επιλέξτε κωδικό που δεν χρησιμοποιείτε πουθενά αλλού.
            </p>

            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf @method('PUT')

                <x-admin.password-field name="current_password" label="Τρέχων κωδικός"
                                        autocomplete="current-password" />

                <x-admin.password-field name="password" label="Νέος κωδικός"
                                        hint="Τουλάχιστον 8 χαρακτήρες." />

                <x-admin.password-field name="password_confirmation" label="Επιβεβαίωση νέου κωδικού" />

                <button class="a-btn a-btn-primary" type="submit">Αλλαγή κωδικού</button>
            </form>
        </section>
    </div>

@endsection
