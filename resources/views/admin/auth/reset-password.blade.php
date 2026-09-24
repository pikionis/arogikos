@extends('admin.layouts.guest')

@section('title', 'Νέος κωδικός')

@section('content')
    <h1 style="font-size:1.3rem;margin-bottom:6px">Νέος κωδικός</h1>
    <p style="color:var(--color-ink-600);font-size:.9rem;margin-bottom:22px">
        Επιλέξτε έναν κωδικό που δεν χρησιμοποιείτε αλλού.
    </p>

    @if ($errors->any())
        <div class="a-alert a-alert-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="a-field">
            <label class="a-label" for="email">Email</label>
            <input class="a-input" id="email" name="email" type="email" required
                   autocomplete="username" value="{{ old('email', $email) }}"
                   @error('email') aria-invalid="true" @enderror>
        </div>

        <div class="a-field">
            <label class="a-label" for="password">Νέος κωδικός</label>
            <input class="a-input" id="password" name="password" type="password" required autofocus
                   autocomplete="new-password"
                   @error('password') aria-invalid="true" @enderror>
            <span class="a-hint">Τουλάχιστον 8 χαρακτήρες.</span>
        </div>

        <div class="a-field">
            <label class="a-label" for="password_confirmation">Επιβεβαίωση κωδικού</label>
            <input class="a-input" id="password_confirmation" name="password_confirmation"
                   type="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="a-btn a-btn-primary" style="width:100%">
            Αποθήκευση κωδικού
        </button>
    </form>
@endsection
