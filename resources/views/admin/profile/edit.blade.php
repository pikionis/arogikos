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
            <h2 style="font-size:1rem;margin:0 0 4px">Αλλαγή κωδικού</h2>
            <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                Επιλέξτε κωδικό που δεν χρησιμοποιείτε πουθενά αλλού.
            </p>

            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf @method('PUT')

                <div class="a-field">
                    <label class="a-label" for="current_password">Τρέχων κωδικός</label>
                    <input class="a-input" id="current_password" name="current_password" type="password"
                           required autocomplete="current-password">
                </div>

                <div class="a-field">
                    <label class="a-label" for="password">Νέος κωδικός</label>
                    <input class="a-input" id="password" name="password" type="password"
                           required autocomplete="new-password">
                    <span class="a-hint">Τουλάχιστον 8 χαρακτήρες.</span>
                </div>

                <div class="a-field">
                    <label class="a-label" for="password_confirmation">Επιβεβαίωση νέου κωδικού</label>
                    <input class="a-input" id="password_confirmation" name="password_confirmation" type="password"
                           required autocomplete="new-password">
                </div>

                <button class="a-btn a-btn-primary" type="submit">Αλλαγή κωδικού</button>
            </form>
        </section>
    </div>

@endsection
