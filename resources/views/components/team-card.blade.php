@props([
    'author',
    'delay' => '',
    'tint' => 'var(--soft-green)',
])

@php $id = 'team-'.$author->id; @endphp

<div class="team-card reveal {{ $delay }}">
    <x-author-portrait :author="$author" :id="$id" :tint="$tint" frame="team-photo" />

    <h3><x-author-name :author="$author" :id="$id" /></h3>
    @if (filled($author->role_title))
        <p class="role">{{ $author->role_title }}</p>
    @endif

    <x-author-bio :author="$author" :id="$id" :tint="$tint" />
</div>

<x-author-dialog :author="$author" :id="$id" :tint="$tint" />
