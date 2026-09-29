@extends('admin.layouts.app')

@section('title', 'Επισκόπηση')
@section('heading', 'Επισκόπηση')
@section('subheading', 'Μια γρήγορη ματιά στο περιεχόμενο του site.')

@section('content')

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:26px">
        <div class="a-stat">
            <div class="a-stat-num" style="color:var(--color-green-700)">{{ $publishedArticles }}</div>
            <div class="a-stat-label">Δημοσιευμένα άρθρα</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-num" style="color:var(--color-ink-400)">{{ $draftArticles }}</div>
            <div class="a-stat-label">Προσχέδια</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-num" style="color:var(--color-yellow-700)">{{ $upcomingEvents }}</div>
            <div class="a-stat-label">Επερχόμενες εκδηλώσεις</div>
        </div>
        <div class="a-stat">
            <div class="a-stat-num" style="color:var(--color-coral-700)">{{ $unreadMessages }}</div>
            <div class="a-stat-label">Αδιάβαστα μηνύματα</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px">

        <section class="a-card a-card-pad0">
            <h2 style="font-size:1rem;padding:16px 20px;margin:0;border-bottom:1px solid var(--admin-border)">
                Πρόσφατα άρθρα
            </h2>

            @if ($recentArticles->isEmpty())
                <p style="padding:20px;color:var(--color-ink-400);margin:0">Δεν υπάρχουν άρθρα ακόμα.</p>
            @else
                <table class="a-table">
                    <tbody>
                        @foreach ($recentArticles as $article)
                            <tr>
                                <td>
                                    <div style="font-weight:600">{{ $article->title }}</div>
                                    <div style="color:var(--color-ink-400);font-size:.82rem">
                                        {{ $article->byline() ?: 'Χωρίς συντάκτη' }}
                                    </div>
                                </td>
                                <td style="text-align:right;white-space:nowrap">
                                    @if ($article->is_published)
                                        <span class="a-badge a-badge-green">Δημοσιευμένο</span>
                                    @else
                                        <span class="a-badge a-badge-grey">Προσχέδιο</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="a-card a-card-pad0">
            <h2 style="font-size:1rem;padding:16px 20px;margin:0;border-bottom:1px solid var(--admin-border)">
                Τελευταία μηνύματα
            </h2>

            @if ($latestMessages->isEmpty())
                <p style="padding:20px;color:var(--color-ink-400);margin:0">Κανένα μήνυμα ακόμα.</p>
            @else
                <table class="a-table">
                    <tbody>
                        @foreach ($latestMessages as $message)
                            <tr>
                                <td>
                                    <div style="font-weight:600">{{ $message->name }}</div>
                                    <div style="color:var(--color-ink-400);font-size:.82rem">{{ $message->email }}</div>
                                </td>
                                <td style="text-align:right;white-space:nowrap;color:var(--color-ink-400);font-size:.82rem">
                                    @if (! $message->read_at)
                                        <span class="a-badge a-badge-coral">Νέο</span>
                                    @endif
                                    {{ $message->created_at?->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>

@endsection
