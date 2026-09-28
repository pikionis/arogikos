@extends('admin.layouts.guest')

@section('title', 'Πρόσκληση')

@section('content')
    <h1 style="font-size:1.3rem;margin-bottom:6px">Καλώς ήρθατε</h1>
    <p style="color:var(--color-ink-600);font-size:.9rem;margin-bottom:18px">
        Έχετε προσκληθεί στη διαχείριση του site ως
        <strong>{{ $invitation->role->label() }}</strong>.
        Ορίστε έναν κωδικό για να ξεκινήσετε.
    </p>

    <div class="a-alert a-alert-ok" style="font-weight:600">
        {{ $invitation->email }}
    </div>

    @if ($errors->any())
        <div class="a-alert a-alert-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.invitation.accept', $token) }}">
        @csrf

        <div class="a-field">
            <label class="a-label" for="name">Το όνομά σας</label>
            <input class="a-input" id="name" name="name" type="text" required autofocus
                   maxlength="120" value="{{ old('name', $invitation->name) }}"
                   @error('name') aria-invalid="true" @enderror>
            <span class="a-hint">Θα εμφανίζεται ως συντάκτης στα άρθρα σας.</span>
        </div>

        <x-admin.password-field name="password" label="Κωδικός πρόσβασης"
                                hint="Τουλάχιστον 8 χαρακτήρες." />

        <x-admin.password-field name="password_confirmation" label="Επιβεβαίωση κωδικού" />

        <button type="submit" class="a-btn a-btn-primary" style="width:100%">
            Δημιουργία λογαριασμού
        </button>
    </form>
@endsection
