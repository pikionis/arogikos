@props([
    'author',
    // Must match the id on the button that opens it.
    'id',
    'tint' => 'var(--soft-green)',
])

@php
    $bio = trim((string) $author->bio);
    $hasMore = mb_strlen($bio) > 180;
@endphp

@if ($hasMore)
    {{-- A real <dialog>: Esc, focus trapping, focus returning to the button
         that opened it, and the rest of the page going inert all come for
         free, and none of it has to be re-implemented. --}}
    <dialog id="{{ $id }}" class="profile-modal" aria-labelledby="{{ $id }}-name">
        <div class="profile-modal-panel" style="--profile-tint:{{ $tint }}">
            <button type="button" class="profile-modal-close" data-profile-close aria-label="Κλείσιμο">✕</button>

            <div class="profile-modal-photo frame-arch">
                <x-media-image
                    :model="$author"
                    collection="photo"
                    motif="one"
                    :tint="$tint"
                    :alt="$author->name"
                />
            </div>

            <div class="profile-modal-text">
                <h3 id="{{ $id }}-name">{{ $author->name }}</h3>
                @if (filled($author->role_title))
                    <p class="role">{{ $author->role_title }}</p>
                @endif
                <div class="profile-modal-bio">{!! nl2br(e($bio)) !!}</div>
            </div>
        </div>
    </dialog>
@endif
