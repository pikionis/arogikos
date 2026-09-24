@props([
    'bg' => 'var(--coral)',
])

<div class="cta-band reveal" style="background:{{ $bg }}">
    {{ $deco ?? '' }}
    {{ $slot }}
</div>
