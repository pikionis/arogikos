@props([
    'class' => null,
])

@php
    $address = setting('address');
    $mapsUrl = setting('maps_url');
@endphp

@if (filled($address))
    @if (filled($mapsUrl))
        {{-- A link only when there is somewhere to go; a dead link that looks
             clickable is worse than plain text. --}}
        <a href="{{ $mapsUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           {{ $attributes->merge(['class' => $class]) }}
           title="Άνοιγμα στους χάρτες">{{ $address }}</a>
    @else
        <span {{ $attributes->merge(['class' => $class]) }}>{{ $address }}</span>
    @endif
@endif
