@props([
    'author',
    // Unique per page; the dialog beside it is opened by this id.
    'id',
    'tint' => 'var(--soft-green)',
    // How many lines survive in the box before the rest waits inside.
    'lines' => 4,
])

@php
    $bio = trim((string) $author->bio);
    // Under this the whole thing fits, and a "more" button would be a lie.
    $hasMore = mb_strlen($bio) > 180;
@endphp

@if (filled($bio))
    <div class="profile-bio" style="--profile-tint:{{ $tint }};--profile-lines:{{ $lines }}">
        <p @class(['profile-bio-text', 'is-clamped' => $hasMore])>{{ $bio }}</p>

        @if ($hasMore)
            <button type="button" class="profile-more" data-profile-open="{{ $id }}"
                    aria-haspopup="dialog">
                Περισσότερα
                <span aria-hidden="true">→</span>
            </button>
        @endif
    </div>
@endif
