@props([
    'bg' => 'var(--coral-700)',
])

<div class="cta-band reveal" style="background:{{ $bg }}">
    {{ $deco ?? '' }}
    {{ $slot }}
</div>
