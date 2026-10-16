@php
    // Routes and active-state patterns stay in code — only the labels are
    // editable (shared.nav in config/cms-content.php). The footer reads the
    // same list, so the two can never drift apart.
    $labels = content_items('shared.nav');

    $nav = collect([
        ['route' => 'home', 'pattern' => 'home'],
        ['route' => 'about', 'pattern' => 'about'],
        ['route' => 'services', 'pattern' => 'services'],
        ['route' => 'blog.index', 'pattern' => 'blog.*'],
        ['route' => 'events', 'pattern' => 'events'],
        ['route' => 'gallery', 'pattern' => 'gallery'],
    ])->map(fn (array $item, int $i) => $item + [
        'label' => $labels[$i]['label'] ?? '',
    ])->all();
@endphp

<header id="site-header">
    <nav class="nav" id="nav">
        <div class="container nav-inner">
            <a class="brand" href="{{ route('home') }}">
                <x-logo-mark />
                <span>{{ setting('org_name', 'Αρωγή') }}</span>
            </a>

            <div class="nav-links">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @class(['active' => request()->routeIs($item['pattern'])])>{{ $item['label'] }}</a>
                @endforeach
            </div>

            <a class="btn btn-coral nav-cta desk" href="{{ route('contact') }}">{{ content('shared.nav_cta') }}</a>

            {{-- Το κουμπί του μενού είναι το σήμα του κέντρου: κύκλος πάνω,
                 γραμμή στη μέση, καμάρα με δύο πόδια από κάτω. Με το πάτημα
                 ανοίγει — τα σχήματα φεύγουν από τη θέση τους και σταυρώνουν. --}}
            <button class="burger" id="burger" aria-label="Μενού" aria-expanded="false">
                <x-logo-mark class="burger-mark" :burger="true" />
            </button>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" @class(['active' => request()->routeIs($item['pattern'])])>{{ $item['label'] }}</a>
        @endforeach
        <a class="btn btn-coral" href="{{ route('contact') }}">{{ content('shared.nav_cta') }}</a>
    </div>
</header>
