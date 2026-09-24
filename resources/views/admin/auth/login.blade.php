@extends('admin.layouts.guest')

@section('title', 'Είσοδος')

@section('content')
    <h1 style="font-size:1.3rem;margin-bottom:6px">Είσοδος</h1>
    <p style="color:var(--color-ink-600);font-size:.9rem;margin-bottom:22px">
        Συνδεθείτε για να διαχειριστείτε το περιεχόμενο του site.
    </p>

    @if ($errors->any())
        <div class="a-alert a-alert-err" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf

        <div class="a-field">
            <label class="a-label" for="email">Email</label>
            <input class="a-input" id="email" name="email" type="email" required autofocus
                   autocomplete="username" value="{{ old('email') }}"
                   @error('email') aria-invalid="true" @enderror>
        </div>

        <div class="a-field">
            <label class="a-label" for="password">Κωδικός πρόσβασης</label>
            <input class="a-input" id="password" name="password" type="password" required
                   autocomplete="current-password"
                   @error('password') aria-invalid="true" @enderror>
        </div>

        <div class="a-field" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <label class="a-check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Να με θυμάσαι</span>
            </label>
            <a href="{{ route('admin.password.request') }}" style="font-size:.86rem;font-weight:600">
                Ξεχάσατε τον κωδικό;
            </a>
        </div>

        <button type="submit" class="a-btn a-btn-primary" style="width:100%">Είσοδος</button>
    </form>
@endsection
