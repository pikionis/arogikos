@extends('admin.layouts.app')

@section('title', 'Εκδηλώσεις')
@section('heading', 'Εκδηλώσεις')
@section('subheading', 'Ημερίδες, εργαστήρια και ανοιχτές μέρες.')

@section('content')

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px">
        <a class="a-btn a-btn-primary" href="{{ route('admin.events.create') }}">+ Νέα εκδήλωση</a>
    </div>

    <section class="a-card a-card-pad0">
        @if ($events->isEmpty())
            <p style="padding:28px;text-align:center;color:var(--color-ink-400);margin:0">
                Δεν υπάρχουν εκδηλώσεις ακόμα.
            </p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Τίτλος</th>
                        <th>Ημερομηνία</th>
                        <th>Τοποθεσία</th>
                        <th>Κατάσταση</th>
                        <th style="text-align:right">Ενέργειες</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td style="font-weight:600">{{ $event->title }}</td>
                            <td style="white-space:nowrap;font-size:.88rem">
                                {{ $event->starts_at?->format('d/m/Y H:i') ?? '—' }}
                                @if ($event->starts_at?->isFuture())
                                    <span class="a-badge a-badge-yellow">Επερχόμενη</span>
                                @endif
                            </td>
                            <td style="font-size:.88rem">{{ $event->location ?: '—' }}</td>
                            <td>
                                @if ($event->is_published)
                                    <span class="a-badge a-badge-green">Δημοσιευμένη</span>
                                @else
                                    <span class="a-badge a-badge-grey">Προσχέδιο</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap">
                                <a class="a-btn a-btn-ghost a-btn-sm"
                                   href="{{ route('admin.events.edit', $event) }}">Επεξεργασία</a>
                                <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                                      style="display:inline"
                                      x-data="confirmable(@js('Διαγραφή της εκδήλωσης «'.$event->title.'»;'))"
                                      @submit="confirm($event)">
                                    @csrf @method('DELETE')
                                    <button class="a-btn a-btn-danger a-btn-sm" type="submit">Διαγραφή</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <div style="margin-top:16px">{{ $events->links() }}</div>

@endsection
