@props([
    'eyebrow' => null,
    'eyebrowColor' => '',
    'title' => null,
    'lead' => null,
    'align' => 'center',
])

<div class="section-head {{ $align }} reveal">
    @if ($eyebrow)
        <span class="eyebrow {{ $eyebrowColor }}">{{ $eyebrow }}</span>
    @endif
    @if ($title)
        <h2>{{ $title }}</h2>
    @endif
    @if ($lead)
        <p class="lead">{{ $lead }}</p>
    @endif
    {{ $slot }}
</div>
