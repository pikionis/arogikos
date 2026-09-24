@props([
    'bg' => 'bg-yellow',
    'crumb' => null,
    'title' => null,
    'lead' => null,
    'class' => '',
])

<section {{ $attributes->merge(['class' => "page-hero $bg $class"]) }}>
    {{ $deco ?? '' }}
    <div class="container" style="position:relative;z-index:1">
        @if ($crumb)
            <p class="breadcrumb reveal"><a href="{{ route('home') }}">Αρχική</a> · {{ $crumb }}</p>
        @endif
        @if ($title)
            <h1 class="reveal">{{ $title }}</h1>
        @endif
        @if ($lead)
            <p class="lead reveal reveal-d1">{{ $lead }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
