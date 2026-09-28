@props([
    'author',
    'id',
])

@php $opens = $author->hasLongBio(); @endphp

@if ($opens)
    <button type="button" class="profile-name" data-profile-open="{{ $id }}"
            aria-haspopup="dialog">{{ $author->name }}</button>
@else
    {{ $author->name }}
@endif
