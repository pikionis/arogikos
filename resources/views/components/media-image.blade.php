@props([
    'model' => null,
    'collection' => 'cover',
    'motif' => 'figures',
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
@else
    {{-- No real image yet: the branded SVG motif is injected by app.js into .photo-ph --}}
    <div class="photo-ph {{ $imgClass }}" data-motif="{{ $motif }}" style="background:{{ $tint }}" role="img" aria-label="{{ $alt }}"></div>
@endif
