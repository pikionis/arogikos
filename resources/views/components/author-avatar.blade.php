@props([
    'author' => null,
    'color' => 'green',
    'size' => null,
])

@php
    // The coloured initials circle is the fallback, not the default: an author
    // who has uploaded a photo should be recognisable at a glance.
    $tone = ['green' => 'g', 'coral' => 'c', 'yellow' => 'y'][$color] ?? 'g';
    $photo = $author?->photoUrl('circle');
    $style = $size ? "width:{$size};height:{$size};font-size:calc({$size} / 2.7)" : null;
@endphp

<span {{ $attributes->merge(['class' => 'avatar '.$tone.($photo ? ' has-photo' : '')]) }}
      @if ($style) style="{{ $style }}" @endif>
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $author->name }}" loading="lazy" decoding="async">
    @else
        {{ $author?->initials() }}
    @endif
</span>
