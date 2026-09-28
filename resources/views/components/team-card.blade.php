@props([
    'author',
    'delay' => '',
    'tint' => 'var(--soft-green)',
])

@php $id = 'team-'.$author->id; @endphp

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

    <x-author-bio :author="$author" :id="$id" :tint="$tint" />
</div>

<x-author-dialog :author="$author" :id="$id" :tint="$tint" />
