@props([
    // A specific scene, fixed.
    'scene' => null,
    // Or the band this sits behind: a key in config/cms-backdrops.php, whose
    // shortlist holds the scenes that suit it. One of them is drawn per
    // visit, so the page keeps changing without ever being off the subject.
    'for' => null,
])

@php $key = $scene ?? backdrop_scene($for); @endphp

@if (filled($key))
    {{-- Decoration only: hidden from screen readers, out of the way of the
         mouse, and behind the text — .section > .container already claims
         z-index 1 above it. --}}
    <div class="scene-backdrop" aria-hidden="true">
        <x-article-cover :scene="$key" live />
    </div>
@endif
