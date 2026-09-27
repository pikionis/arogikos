@props([
    'model' => null,
    'collection' => 'cover',
    'motif' => 'figures',
    // A ready-made cover scene (config/cms-covers.php), if one was chosen.
    'scene' => null,
    'live' => false,
    'tint' => 'var(--soft-green)',
    'alt' => '',
    'imgClass' => '',
])

@php
    $media = $model?->getFirstMedia($collection);
@endphp

@if ($media)
    <img
        class="{{ $imgClass }}"
        src="{{ $model->getFirstMediaUrl($collection, 'web') }}"
        srcset="{{ $model->getFirstMediaUrl($collection, 'thumb') }} 400w, {{ $model->getFirstMediaUrl($collection, 'web') }} 1600w"
        sizes="(max-width: 700px) 100vw, 50vw"
        alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        style="width:100%;height:100%;object-fit:cover"
    >
@elseif (filled($scene))
    {{-- No photo, but the author picked a scene. --}}
    <x-article-cover :scene="$scene" :live="$live" />
@else
    {{-- Neither: the branded SVG motif is injected by app.js into .photo-ph --}}
    <div class="photo-ph {{ $imgClass }}" data-motif="{{ $motif }}" style="background:{{ $tint }}" role="img" aria-label="{{ $alt }}"></div>
@endif
