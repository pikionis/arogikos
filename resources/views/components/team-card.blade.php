@props([
    'author',
    'delay' => '',
    'tint' => 'var(--soft-green)',
])

@php
    $bio = trim((string) $author->bio);
    // Under this, the whole thing fits the card and a "more" button would be
    // a lie. Over it, the card shows the opening and the rest waits inside.
    $hasMore = mb_strlen($bio) > 180;
    $id = 'team-'.$author->id;
@endphp

<div class="team-card reveal {{ $delay }}">
    <div class="team-photo frame-arch">
        <x-media-image
            :model="$author"
            collection="photo"
            motif="one"
            :tint="$tint"
            :alt="$author->name"
        />
    </div>

    <h3>{{ $author->name }}</h3>
    @if (filled($author->role_title))
        <p class="role">{{ $author->role_title }}</p>
    @endif

    @if (filled($bio))
        <div class="team-bio" style="--team-tint:{{ $tint }}">
            <p @class(['team-bio-text', 'is-clamped' => $hasMore])>{{ $bio }}</p>

            @if ($hasMore)
                <button type="button" class="team-more" data-team-open="{{ $id }}"
                        aria-haspopup="dialog">
                    Περισσότερα
                    <span aria-hidden="true">→</span>
                </button>
            @endif
        </div>
    @endif
</div>

@if (filled($bio) && $hasMore)
    {{-- A real <dialog>: Esc, focus trapping and the rest of the page going
         inert all come for free, and none of it has to be re-implemented. --}}
    <dialog id="{{ $id }}" class="team-modal" aria-labelledby="{{ $id }}-name">
        <div class="team-modal-panel" style="--team-tint:{{ $tint }}">
            <button type="button" class="team-modal-close" data-team-close aria-label="Κλείσιμο">✕</button>

            <div class="team-modal-photo frame-arch">
                <x-media-image
                    :model="$author"
                    collection="photo"
                    motif="one"
                    :tint="$tint"
                    :alt="$author->name"
                />
            </div>

            <div class="team-modal-text">
                <h3 id="{{ $id }}-name">{{ $author->name }}</h3>
                @if (filled($author->role_title))
                    <p class="role">{{ $author->role_title }}</p>
                @endif
                <div class="team-modal-bio">{!! nl2br(e($bio)) !!}</div>
            </div>
        </div>
    </dialog>
@endif
