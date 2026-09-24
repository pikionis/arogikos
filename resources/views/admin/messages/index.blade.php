@extends('admin.layouts.app')

@section('title', 'Μηνύματα')
@section('heading', 'Μηνύματα')
@section('subheading', 'Ό,τι έρχεται από τη φόρμα επικοινωνίας.')

@section('content')

    <div style="display:flex;gap:8px;margin-bottom:18px">
        <a class="a-btn {{ $filter === 'unread' ? 'a-btn-ghost' : 'a-btn-primary' }} a-btn-sm"
           href="{{ route('admin.messages.index') }}">Όλα</a>
        <a class="a-btn {{ $filter === 'unread' ? 'a-btn-primary' : 'a-btn-ghost' }} a-btn-sm"
           href="{{ route('admin.messages.index', ['filter' => 'unread']) }}">
            Αδιάβαστα ({{ $unreadCount }})
        </a>
    </div>

    <section class="a-card a-card-pad0">
        @if ($messages->isEmpty())
            <p style="padding:28px;text-align:center;color:var(--color-ink-400);margin:0">
                Κανένα μήνυμα.
            </p>
        @else
            <table class="a-table">
                <tbody>
                    @foreach ($messages as $message)
                        <tr>
                            <td style="width:8px;padding-right:0">
                                @unless ($message->read_at)
                                    <span title="Αδιάβαστο"
                                          style="display:block;width:8px;height:8px;border-radius:50%;background:var(--color-coral)"></span>
                                @endunless
                            </td>
                            <td>
                                <a href="{{ route('admin.messages.show', $message) }}"
                                   style="font-weight:{{ $message->read_at ? '600' : '700' }};text-decoration:none">
                                    {{ $message->name }}
                                </a>
                                <div style="color:var(--color-ink-400);font-size:.82rem">{{ $message->email }}</div>
                            </td>
                            <td style="color:var(--color-ink-600);font-size:.88rem;max-width:380px">
                                {{ Str::limit($message->message, 90) }}
                            </td>
                            <td style="white-space:nowrap;color:var(--color-ink-400);font-size:.82rem">
                                {{ $message->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td style="text-align:right;white-space:nowrap">
                                <a class="a-btn a-btn-ghost a-btn-sm"
                                   href="{{ route('admin.messages.show', $message) }}">Άνοιγμα</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <div style="margin-top:16px">{{ $messages->links() }}</div>

@endsection
