@extends('admin.layouts.app')

@section('title', 'Άρθρα')
@section('heading', 'Άρθρα')
@section('subheading', 'Το blog του site.')

@section('content')

    <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:18px">
        <form method="GET" action="{{ route('admin.articles.index') }}"
              style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input class="a-input" type="search" name="q" value="{{ $search }}"
                   placeholder="Αναζήτηση τίτλου…" style="width:240px">
            <select class="a-select" name="status" style="width:auto">
                <option value="">Όλα</option>
                <option value="published" @selected($status === 'published')>Δημοσιευμένα</option>
                <option value="draft" @selected($status === 'draft')>Προσχέδια</option>
            </select>
            <button class="a-btn a-btn-ghost" type="submit">Φίλτρο</button>
        </form>

        <a class="a-btn a-btn-primary" href="{{ route('admin.articles.create') }}">+ Νέο άρθρο</a>
    </div>

    <section class="a-card a-card-pad0">
        @if ($articles->isEmpty())
            <p style="padding:28px;text-align:center;color:var(--color-ink-400);margin:0">
                Δεν βρέθηκαν άρθρα.
            </p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th style="width:64px"></th>
                        <th>Τίτλος</th>
                        <th>Συντάκτης</th>
                        <th>Κατηγορία</th>
                        <th>Κατάσταση</th>
                        <th style="text-align:right">Ενέργειες</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        @php $cover = $article->getFirstMediaUrl('cover', 'thumb'); @endphp
                        <tr>
                            <td>
                                <div style="width:48px;height:36px;border-radius:6px;overflow:hidden;background:var(--color-soft-green)">
                                    @if ($cover)
                                        <img src="{{ $cover }}" alt="" width="48" height="36"
                                             style="width:100%;height:100%;object-fit:cover">
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600">{{ $article->title }}</div>
                                <div style="color:var(--color-ink-400);font-size:.8rem">/{{ $article->slug }}</div>
                            </td>
                            <td style="font-size:.88rem">
                                {{ $article->author?->name ?? '—' }}
                                @if ($article->author?->hasPanelAccess())
                                    <span class="a-badge a-badge-grey">panel</span>
                                @endif
                            </td>
                            <td style="font-size:.88rem">{{ $article->category_label ?: '—' }}</td>
                            <td style="white-space:nowrap">
                                @if ($article->is_published)
                                    <span class="a-badge a-badge-green">Δημοσιευμένο</span>
                                @else
                                    <span class="a-badge a-badge-grey">Προσχέδιο</span>
                                @endif
                                <div style="color:var(--color-ink-400);font-size:.78rem;margin-top:3px">
                                    {{ $article->published_at?->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </td>
                            <td style="text-align:right;white-space:nowrap">
                                <a class="a-btn a-btn-ghost a-btn-sm"
                                   href="{{ route('admin.articles.edit', $article) }}">Επεξεργασία</a>
                                <form method="POST" action="{{ route('admin.articles.destroy', $article) }}"
                                      style="display:inline"
                                      x-data="confirmable(@js('Διαγραφή του άρθρου «'.$article->title.'»;'))"
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

    <div style="margin-top:16px">{{ $articles->links() }}</div>

@endsection
