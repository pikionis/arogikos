@props([
    'author',
    // Matches the dialog beside it. Ignored when there is no dialog.
    'id',
    'tint' => 'var(--soft-green)',
    // Class for the frame itself, so each page keeps its own sizing.
    'frame' => 'team-photo',
])

@php $opens = $author->hasLongBio(); @endphp

@if ($opens)
    {{-- A real button, not a clickable div: a portrait that opens something
         has to be reachable by Tab and say what it does out loud. --}}
    <button type="button" class="profile-portrait {{ $frame }} frame-arch"
            data-profile-open="{{ $id }}" aria-haspopup="dialog"
            aria-label="Περισσότερα για {{ $author->name }}">
        <x-media-image :model="$author" collection="photo" motif="one" :tint="$tint" alt="" />
    </button>
@else
    <div class="{{ $frame }} frame-arch">
        <x-media-image :model="$author" collection="photo" motif="one" :tint="$tint" :alt="$author->name" />
    </div>
@endif
