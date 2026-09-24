@extends('admin.layouts.app')

@section('title', 'Μήνυμα')
@section('heading', 'Μήνυμα από ' . $message->name)
@section('subheading', $message->created_at?->format('d/m/Y H:i'))

@section('content')

    <div style="max-width:720px;display:grid;gap:18px">
        <section class="a-card">
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:8px 18px;margin:0 0 18px;font-size:.9rem">
                <dt style="font-weight:700">Όνομα</dt>
                <dd style="margin:0">{{ $message->name }}</dd>

                <dt style="font-weight:700">Email</dt>
                <dd style="margin:0"><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>

                @if ($message->phone)
                    <dt style="font-weight:700">Τηλέφωνο</dt>
                    <dd style="margin:0"><a href="tel:{{ $message->phone }}">{{ $message->phone }}</a></dd>
                @endif
            </dl>

            {{--
                Escaped, then newlines turned into <br>. Never {!! !!} here:
                this text comes from an anonymous public form.
            --}}
            <div style="background:var(--admin-bg);border-radius:9px;padding:16px;line-height:1.6">
                {!! nl2br(e($message->message)) !!}
            </div>
        </section>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="a-btn a-btn-primary" href="mailto:{{ $message->email }}">Απάντηση με email</a>

            <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                @csrf @method('PATCH')
                <button class="a-btn a-btn-ghost" type="submit">
                    {{ $message->read_at ? 'Σήμανση ως αδιάβαστο' : 'Σήμανση ως διαβασμένο' }}
                </button>
            </form>

            <a class="a-btn a-btn-ghost" href="{{ route('admin.messages.index') }}">← Όλα τα μηνύματα</a>

            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                  x-data="confirmable(@js('Διαγραφή του μηνύματος από '.$message->name.';'))"
                  @submit="confirm($event)" style="margin-left:auto">
                @csrf @method('DELETE')
                <button class="a-btn a-btn-danger" type="submit">Διαγραφή</button>
            </form>
        </div>
    </div>

@endsection
