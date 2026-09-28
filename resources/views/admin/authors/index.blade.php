@extends('admin.layouts.app')

@section('title', 'Συντάκτες')
@section('heading', 'Συντάκτες')
@section('subheading', 'Η ομάδα που εμφανίζεται στο site και υπογράφει τα άρθρα.')

@section('content')

    <div class="a-alert a-alert-warn" style="font-weight:500">
        <p style="margin:0 0 8px">
            <strong>Η σειρά:</strong> σύρετε έναν συντάκτη από τη λαβή αριστερά.
            Όποιος είναι ψηλότερα εδώ εμφανίζεται πρώτος στο «Σχετικά με εμάς»,
            από αριστερά προς τα δεξιά.
        </p>
        <p style="margin:0">
            <strong>Πώς συνδέονται με τους λογαριασμούς:</strong>
            όποιος προσκληθεί στη διαχείριση αποκτά αυτόματα προφίλ συντάκτη, ώστε να
            μπορεί να υπογράψει άρθρα. Το προφίλ ξεκινά <em>ανενεργό</em>, δηλαδή
            <strong>δεν</strong> εμφανίζεται στη σελίδα «Σχετικά με εμάς» μέχρι να το
            ενεργοποιήσετε εδώ.
        </p>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px">
        <a class="a-btn a-btn-primary" href="{{ route('admin.authors.create') }}">+ Νέος συντάκτης</a>
    </div>

<div x-data="sortableRows()" x-ref="root">

    <section class="a-card a-card-pad0">
        <table class="a-table a-table-sortable">
            <thead>
                <tr>
                    <th style="width:44px"><span class="a-sr">Σειρά</span></th>
                    <th style="width:56px"></th>
                    <th>Όνομα</th>
                    <th>Λογαριασμός</th>
                    <th>Άρθρα</th>
                    <th>Στο «Σχετικά»</th>
                    <th style="text-align:right">Ενέργειες</th>
                </tr>
            </thead>
            <tbody x-ref="list">
                @foreach ($authors as $author)
                    @php $photo = $author->getFirstMediaUrl('photo', 'thumb'); @endphp
                    <tr data-id="{{ $author->id }}" draggable="false"
                        @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
                        @drop.prevent="drop()" @dragend="dragEnd()">
                        <td>
                            {{-- The handle carries the drag, not the row: a row
                                 you cannot select text in is a row you cannot
                                 read properly. Arrow keys do the same job
                                 without a mouse. --}}
                            <button type="button" class="a-drag" title="Μετακίνηση"
                                    aria-label="Μετακίνηση: {{ $author->name }}"
                                    @mousedown="arm($event)" @mouseup="disarm($event)"
                                    @keydown.arrow-up.prevent="move($event, -1)"
                                    @keydown.arrow-down.prevent="move($event, 1)">
                                <span aria-hidden="true">⠿</span>
                            </button>
                        </td>
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

    {{-- Shown by Alpine only once something has actually moved. --}}
    <form method="POST" action="{{ route('admin.authors.order') }}" class="a-sortbar"
          x-show="dirty" x-cloak @submit="$refs.order.value = ids().join(',')">
        @csrf @method('PATCH')
        <input type="hidden" name="order" x-ref="order">
        <span>Η σειρά άλλαξε.</span>
        <button class="a-btn a-btn-ghost a-btn-sm" type="button" @click="reset()">Αναίρεση</button>
        <button class="a-btn a-btn-primary a-btn-sm" type="submit">Αποθήκευση σειράς</button>
    </form>

</div>

@endsection
