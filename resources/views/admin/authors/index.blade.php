@extends('admin.layouts.app')

@section('title', 'Συντάκτες')
@section('heading', 'Συντάκτες')
@section('subheading', 'Η ομάδα που εμφανίζεται στο site και υπογράφει τα άρθρα.')

@section('content')

    <div class="a-alert a-alert-warn" style="font-weight:500">
        <strong>Πώς συνδέονται με τους λογαριασμούς:</strong>
        όποιος προσκληθεί στη διαχείριση αποκτά αυτόματα προφίλ συντάκτη, ώστε να
        μπορεί να υπογράψει άρθρα. Το προφίλ ξεκινά <em>ανενεργό</em>, δηλαδή
        <strong>δεν</strong> εμφανίζεται στη σελίδα «Σχετικά με εμάς» μέχρι να το
        ενεργοποιήσετε εδώ.
    </div>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px">
        <a class="a-btn a-btn-primary" href="{{ route('admin.authors.create') }}">+ Νέος συντάκτης</a>
    </div>

    <section class="a-card a-card-pad0">
        <table class="a-table">
            <thead>
                <tr>
                    <th style="width:56px"></th>
                    <th>Όνομα</th>
                    <th>Λογαριασμός</th>
                    <th>Άρθρα</th>
                    <th>Στο «Σχετικά»</th>
                    <th style="text-align:right">Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($authors as $author)
                    @php $photo = $author->getFirstMediaUrl('photo', 'thumb'); @endphp
                    <tr>
                        <td>
                            <div style="width:40px;height:40px;border-radius:50%;overflow:hidden;background:var(--color-soft-green);display:grid;place-items:center;font-family:var(--font-head);font-weight:700;color:var(--color-green-700)">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="" width="40" height="40"
                                         style="width:100%;height:100%;object-fit:cover">
                                @else
                                    {{ $author->initials() }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $author->name }}</div>
                            <div style="color:var(--color-ink-400);font-size:.82rem">{{ $author->role_title ?: '—' }}</div>
                        </td>
                        <td style="font-size:.86rem">
                            @if ($author->user)
                                <span class="a-badge a-badge-green">{{ $author->user->role->label() }}</span>
                            @else
                                <span style="color:var(--color-ink-400)">Χωρίς πρόσβαση</span>
                            @endif
                        </td>
                        <td style="font-size:.88rem">{{ $author->articles_count }}</td>
                        <td>
                            @if ($author->is_active)
                                <span class="a-badge a-badge-green">Ναι</span>
                            @else
                                <span class="a-badge a-badge-grey">Όχι</span>
                            @endif
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <a class="a-btn a-btn-ghost a-btn-sm"
                               href="{{ route('admin.authors.edit', $author) }}">Επεξεργασία</a>
                            <form method="POST" action="{{ route('admin.authors.destroy', $author) }}"
                                  style="display:inline"
                                  x-data="confirmable(@js($author->articles_count > 0
                                      ? "Ο συντάκτης «{$author->name}» υπογράφει {$author->articles_count} άρθρα. Θα μείνουν χωρίς συντάκτη. Διαγραφή;"
                                      : "Διαγραφή του συντάκτη «{$author->name}»;"))"
                                  @submit="confirm($event)">
                                @csrf @method('DELETE')
                                <button class="a-btn a-btn-danger a-btn-sm" type="submit">Διαγραφή</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

@endsection
