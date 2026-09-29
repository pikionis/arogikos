@props([
    // A specific scene, or nothing for a different one on every visit.
    'scene' => null,
])

@php $key = $scene ?? backdrop_scene(); @endphp

@if (filled($key))
    {{-- Decoration only: hidden from screen readers, out of the way of the
         mouse, and behind the text — .section > .container already claims
         z-index 1 above it. --}}
    <div class="scene-backdrop" aria-hidden="true">
        <x-article-cover :scene="$key" live />
    </div>
@endif
