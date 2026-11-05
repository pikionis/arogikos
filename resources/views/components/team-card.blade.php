@props([
    'author',
    'delay' => '',
    'tint' => 'var(--soft-green)',
    // On the team page the grid follows the h1 directly, so a name is the
    // next heading down; elsewhere it sits under a section heading.
    'level' => 3,
])

@php $id = 'team-'.$author->id; @endphp

<div class="team-card reveal {{ $delay }}">
    <x-author-portrait :author="$author" :id="$id" :tint="$tint" frame="team-photo" />

    <h{{ $level }}><x-author-name :author="$author" :id="$id" /></h{{ $level }}>
    @if (filled($author->role_title))
        <p class="role">{{ $author->role_title }}</p>
    @endif

    <x-author-bio :author="$author" :id="$id" :tint="$tint" />
</div>

<x-author-dialog :author="$author" :id="$id" :tint="$tint" />
