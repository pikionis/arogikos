@props([
    'author',
    // Which of Author::FRAMES this frame is. The tall ones are the default:
    // they are the ones a photograph is actually looked at in.
    'frame' => 'arch',
    'tint' => 'var(--soft-green)',
    'alt' => '',
])

@php $photo = $author?->photoUrl($frame); @endphp

@if ($photo)
    {{-- One conversion, not a srcset: the two conversions are framed
         differently on purpose, and letting the browser pick between them
         would hand it two different photographs. --}}
    <img src="{{ $photo }}" alt="{{ $alt }}" loading="lazy" decoding="async"
         style="width:100%;height:100%;object-fit:cover">
@else
    <div class="photo-ph" data-motif="one" style="background:{{ $tint }}"
         role="img" aria-label="{{ $alt }}"></div>
@endif
