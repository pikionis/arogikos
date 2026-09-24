@props([
    'author',
    'delay' => '',
    'tint' => 'var(--soft-green)',
])

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
    @if (filled($author->bio))
        <p>{{ $author->bio }}</p>
    @endif
</div>
