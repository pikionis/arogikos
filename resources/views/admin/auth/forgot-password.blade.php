@extends('admin.layouts.guest')

@section('title', 'Επαναφορά κωδικού')

@section('content')
    <h1 style="font-size:1.3rem;margin-bottom:6px">Επαναφορά κωδικού</h1>
    <p style="color:var(--color-ink-600);font-size:.9rem;margin-bottom:22px">
        Δώστε το email σας και θα σας στείλουμε σύνδεσμο για να ορίσετε νέο κωδικό.
    </p>

    @if ($errors->any())
        <div class="a-alert a-alert-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}">
        @csrf

        <div class="a-field">
            <label class="a-label" for="email">Email</label>
            <input class="a-input" id="email" name="email" type="email" required autofocus
                   autocomplete="username" value="{{ old('email') }}"
                   @error('email') aria-invalid="true" @enderror>
        </div>

        <button type="submit" class="a-btn a-btn-primary" style="width:100%">
            Αποστολή συνδέσμου
        </button>
    </form>

    <p style="margin-top:18px;font-size:.86rem;text-align:center">
        <a href="{{ route('admin.login') }}">← Επιστροφή στην είσοδο</a>
    </p>
@endsection
