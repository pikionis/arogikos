@extends('admin.layouts.app')

@section('title', 'Χρήστες')
@section('heading', 'Χρήστες')
@section('subheading', 'Ποιος έχει πρόσβαση στη διαχείριση του site.')

@section('content')

    @if ($freshInviteUrl)
        <div class="a-alert a-alert-warn" x-data="copyable(@js($freshInviteUrl))">
            <strong>Σύνδεσμος πρόσκλησης</strong>
            <p style="font-weight:500;margin:6px 0 10px">
                Στάλθηκε με email. Αν δεν φτάσει (το production τρέχει ακόμα
                <code>MAIL_MAILER=log</code>), δώστε τον σύνδεσμο απευθείας:
            </p>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input class="a-input" style="flex:1;min-width:260px;font-size:.82rem"
                       readonly value="{{ $freshInviteUrl }}"
                       onfocus="this.select()">
                <button type="button" class="a-btn a-btn-ghost a-btn-sm" @click="copy()">
                    <span x-show="!copied">Αντιγραφή</span>
                    <span x-show="copied" x-cloak>Αντιγράφηκε ✓</span>
                </button>
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:18px;align-items:start">

        {{-- ------------------------------------------------------ accounts --}}
        <div style="display:grid;gap:18px">
            <section class="a-card a-card-pad0">
                <h2 style="font-size:1rem;padding:16px 20px;margin:0;border-bottom:1px solid var(--admin-border)">
                    Λογαριασμοί ({{ $users->count() }})
                </h2>

                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Όνομα</th>
                            <th>Ρόλος</th>
                            <th>Κατάσταση</th>
                            <th style="text-align:right">Ενέργειες</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $row)
                            <tr>
                                <td>
                                    <div style="font-weight:600">
                                        {{ $row->name }}
                                        @if ($row->is(auth()->user()))
                                            <span class="a-badge a-badge-grey">εσείς</span>
                                        @endif
                                    </div>
                                    <div style="color:var(--color-ink-400);font-size:.82rem">{{ $row->email }}</div>
                                </td>

                                <td>
                                    @can('change-user-role', $row)
                                        <form method="POST" action="{{ route('admin.users.role', $row) }}"
                                              style="display:flex;gap:6px;align-items:center">
                                            @csrf @method('PATCH')
                                            <select name="role" class="a-select" style="width:auto;padding:5px 9px;font-size:.84rem">
                                                @foreach ($assignableRoles as $role)
                                                    <option value="{{ $role->value }}" @selected($row->role === $role)>
                                                        {{ $role->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="a-btn a-btn-ghost a-btn-sm" type="submit">OK</button>
                                        </form>
                                    @else
                                        <span class="a-badge a-badge-grey">{{ $row->role->label() }}</span>
                                    @endcan
                                </td>

                                <td>
                                    @if ($row->is_active)
                                        <span class="a-badge a-badge-green">Ενεργός</span>
                                    @else
                                        <span class="a-badge a-badge-coral">Ανενεργός</span>
                                    @endif
                                </td>

                                <td style="text-align:right;white-space:nowrap">
                                    @can('update-user', $row)
                                        @unless ($row->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.active', $row) }}"
                                                  style="display:inline">
                                                @csrf @method('PATCH')
                                                <button class="a-btn a-btn-ghost a-btn-sm" type="submit">
                                                    {{ $row->is_active ? 'Απενεργοποίηση' : 'Ενεργοποίηση' }}
                                                </button>
                                            </form>
                                        @endunless
                                    @endcan

                                    @can('delete-user', $row)
                                        <form method="POST" action="{{ route('admin.users.destroy', $row) }}"
                                              style="display:inline"
                                              x-data="confirmable(@js('Διαγραφή του λογαριασμού '.$row->name.';'))"
                                              @submit="confirm($event)">
                                            @csrf @method('DELETE')
                                            <button class="a-btn a-btn-danger a-btn-sm" type="submit">Διαγραφή</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            @if ($invitations->isNotEmpty())
                <section class="a-card a-card-pad0">
                    <h2 style="font-size:1rem;padding:16px 20px;margin:0;border-bottom:1px solid var(--admin-border)">
                        Εκκρεμείς προσκλήσεις ({{ $invitations->count() }})
                    </h2>
                    <table class="a-table">
                        <tbody>
                            @foreach ($invitations as $invitation)
                                <tr>
                                    <td>
                                        <div style="font-weight:600">{{ $invitation->name }}</div>
                                        <div style="color:var(--color-ink-400);font-size:.82rem">{{ $invitation->email }}</div>
                                    </td>
                                    <td><span class="a-badge a-badge-grey">{{ $invitation->role->label() }}</span></td>
                                    <td style="color:var(--color-ink-400);font-size:.82rem">
                                        Λήγει {{ $invitation->expires_at->diffForHumans() }}
                                    </td>
                                    <td style="text-align:right">
                                        <form method="POST" action="{{ route('admin.users.invitations.revoke', $invitation) }}">
                                            @csrf @method('DELETE')
                                            <button class="a-btn a-btn-ghost a-btn-sm" type="submit">Ακύρωση</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        </div>

        {{-- -------------------------------------------------------- invite --}}
        <section class="a-card">
            <h2 style="font-size:1rem;margin:0 0 4px">Πρόσκληση μέλους</h2>
            <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                Δεν υπάρχει δημόσια εγγραφή. Ο προσκεκλημένος ορίζει μόνος του κωδικό.
            </p>

            @if ($errors->any())
                <div class="a-alert a-alert-err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.users.invite') }}">
                @csrf

                <div class="a-field">
                    <label class="a-label" for="invite-name">Όνομα</label>
                    <input class="a-input" id="invite-name" name="name" type="text" required
                           maxlength="120" value="{{ old('name') }}">
                </div>

                <div class="a-field">
                    <label class="a-label" for="invite-email">Email</label>
                    <input class="a-input" id="invite-email" name="email" type="email" required
                           maxlength="160" value="{{ old('email') }}">
                </div>

                <div class="a-field">
                    <label class="a-label" for="invite-role">Ρόλος</label>
                    <select class="a-select" id="invite-role" name="role" required>
                        @foreach ($assignableRoles as $role)
                            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                    <span class="a-hint">
                        @foreach ($assignableRoles as $role)
                            <strong>{{ $role->label() }}</strong>: {{ $role->description() }}<br>
                        @endforeach
                    </span>
                </div>

                <button class="a-btn a-btn-primary" type="submit" style="width:100%">
                    Αποστολή πρόσκλησης
                </button>
            </form>
        </section>
    </div>

@endsection
