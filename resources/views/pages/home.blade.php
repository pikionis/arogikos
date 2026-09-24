@extends('layouts.app')

@section('content')
<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-copy">
        <span class="eyebrow">{{ content('home.hero.eyebrow') }}</span>
        <h1>{{ content('home.hero.title') }}</h1>
        <p class="lead">{{ content('home.hero.lead') }}</p>
        <div class="hero-cta">
          <a class="btn btn-primary btn-lg" href="{{ route('contact') }}">{{ content('home.hero.cta_primary') }}</a>
          <a class="btn btn-ghost btn-lg" href="{{ route('services') }}">{{ content('home.hero.cta_secondary') }}</a>
        </div>
        <p style="margin-top:28px;color:var(--ink-400);font-family:var(--font-head);font-weight:700;font-size:.98rem">{{ content('home.hero.note') }}</p>
      </div>

      <div class="hero-stage">
        <svg class="hero-scene hero-anim" viewBox="0 0 1200 540" role="img" aria-label="Παιδικές φιγούρες περπατούν προς την αψίδα της Αρωγής">
          <!-- faint decorative shapes -->
          <circle cx="120" cy="120" r="46" fill="none" stroke="#F9D27E" stroke-width="7" opacity=".5"/>
          <circle cx="1080" cy="430" r="12" fill="#EEA294" opacity=".7"/>
          <circle cx="220" cy="380" r="9" fill="#6FC79A" opacity=".7"/>

          <!-- sun -->
          <g class="hero-rays">
            <g stroke="#F4B62A" stroke-width="7" stroke-linecap="round" opacity=".85">
              <line x1="900" y1="120" x2="900" y2="142"/>
              <line x1="900" y1="288" x2="900" y2="310"/>
              <line x1="805" y1="215" x2="827" y2="215"/>
              <line x1="973" y1="215" x2="995" y2="215"/>
              <line x1="833" y1="148" x2="849" y2="164"/>
              <line x1="951" y1="266" x2="967" y2="282"/>
              <line x1="967" y1="148" x2="951" y2="164"/>
              <line x1="849" y1="266" x2="833" y2="282"/>
            </g>
          </g>
          <circle class="hero-sun" cx="900" cy="215" r="48" fill="#F4B62A"/>

          <!-- ground -->
          <rect x="20" y="438" width="1160" height="12" rx="6" fill="#1E9E58"/>
          <circle cx="430" cy="470" r="5" fill="#F4B62A" opacity=".6"/>
          <circle cx="640" cy="474" r="6" fill="#E26D5A" opacity=".55"/>
          <circle cx="300" cy="476" r="4" fill="#1E9E58" opacity=".6"/>

          <!-- door inside arch -->
          <rect class="hero-door" x="835" y="338" width="130" height="100" rx="10" fill="#178049"/>
          <rect class="hero-door" x="835" y="338" width="130" height="100" rx="10" fill="none" stroke="#0f5e35" stroke-width="2" opacity=".4"/>

          <!-- arch -->
          <path d="M785 438 A115 115 0 0 1 1015 438" fill="none" stroke="#E26D5A" stroke-width="28" stroke-linecap="round"/>

          <!-- walking figures -->
          <g class="hero-walker w1"><g class="hero-bob"><g transform="translate(120,438)">
            <circle cx="0" cy="-48" r="16" fill="#1E9E58"/>
            <path d="M-17 0 C-17 -36 17 -36 17 0 Z" fill="#1E9E58"/>
          </g></g></g>
          <g class="hero-walker w2"><g class="hero-bob"><g transform="translate(120,438)">
            <circle cx="0" cy="-48" r="16" fill="#E26D5A"/>
            <path d="M-17 0 C-17 -36 17 -36 17 0 Z" fill="#E26D5A"/>
          </g></g></g>
          <g class="hero-walker w3"><g class="hero-bob"><g transform="translate(120,438)">
            <circle cx="0" cy="-48" r="16" fill="#F4B62A"/>
            <path d="M-17 0 C-17 -36 17 -36 17 0 Z" fill="#F4B62A"/>
          </g></g></g>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- ============ SERVICES ============ -->
<section class="section bg-white" data-play>
  <div class="container">
    <x-section-head
      :eyebrow="content('home.services.eyebrow')"
      :title="content('home.services.title')"
      :lead="content('home.services.lead')"
    />

    <div class="grid-3" style="margin-top:var(--s-12)">
      @php
        // Colour and reveal delay are positional design decisions; the text of
        // each service is editable (shared.services).
        $tints = ['c-green', 'c-coral', 'c-yellow'];
        $delays = ['', 'reveal-d1', 'reveal-d2'];
      @endphp

      @foreach (content_items('shared.services') as $i => $service)
        <article class="card card-hover svc-card {{ $tints[$i % 3] }} reveal {{ $delays[$i % 3] }}">
          <div class="ico-wrap">
            <x-service-icon :index="$i" />
          </div>
          <h3>{{ $service['name'] ?? '' }}</h3>
          <p>{{ $service['card_body'] ?? '' }}</p>
          <a class="link-arrow" href="{{ route('services') }}">{{ content('home.services.card_link') }} <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </article>
      @endforeach
    </div>
  </div>
</section>

<!-- ============ WHY ΑΡΩΓΗ ============ -->
<section class="section bg-green why-row arch-top">
  <div class="container">
    <div class="grid-2" style="gap:var(--s-12);align-items:center">
      <div class="section-head left reveal">
        <span class="eyebrow">{{ content('home.why.eyebrow') }}</span>
        <h2>{{ content('home.why.title') }}</h2>
        <p class="lead">{{ content('home.why.lead') }}</p>
      </div>
      <div class="why-list" style="display:grid;gap:var(--s-6)">
        @php
          // Dot colour and the check/star/house/people glyph are positional
          // design; only the title and text of each point are editable.
          $whyDots = ['var(--green)', 'var(--coral)', 'var(--yellow)', 'var(--green)'];
          $whyPaths = [
            'M20 6L9 17l-5-5',
            'M12 3l2.4 5.4L20 9l-4 4 1 6-5-3-5 3 1-6-4-4 5.6-.6z',
            'M3 11l9-8 9 8M5 10v9h14v-9',
            'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 010 7.75',
          ];
        @endphp

        @foreach (content_items('home.why.items') as $i => $item)
          <div class="why-item why-row-{{ $i + 1 }}">
            <div class="why-dot" style="background:{{ $whyDots[$i % 4] }}">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2"><path d="{{ $whyPaths[$i % 4] }}"/></svg>
            </div>
            <div><h3>{{ $item['title'] ?? '' }}</h3><p>{{ $item['text'] ?? '' }}</p></div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<!-- ============ LATEST ARTICLES ============ -->
@if ($articles->isNotEmpty())
<section class="section bg-white">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:var(--s-6);flex-wrap:wrap" class="reveal">
      <div class="section-head left" style="margin:0">
        <span class="eyebrow coral">{{ content('home.blog.eyebrow') }}</span>
        <h2>{{ content('home.blog.title') }}</h2>
      </div>
      <a class="link-arrow" href="{{ route('blog.index') }}">{{ content('home.blog.link') }} <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>

    <div class="grid-3" style="margin-top:var(--s-10)">
      @foreach ($articles as $article)
        <x-article-card :article="$article" :delay="$loop->index ? 'reveal-d'.$loop->index : ''" />
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- ============ UPCOMING EVENTS ============ -->
@if ($events->isNotEmpty())
<section class="section bg-coral">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:var(--s-6);flex-wrap:wrap" class="reveal">
      <div class="section-head left" style="margin:0">
        <span class="eyebrow yellow">{{ content('home.events.eyebrow') }}</span>
        <h2>{{ content('home.events.title') }}</h2>
      </div>
      <a class="link-arrow" href="{{ route('events') }}">{{ content('home.events.link') }} <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>

    <div class="grid-2" style="margin-top:var(--s-10)">
      @foreach ($events as $event)
        <x-event-card :event="$event" :delay="$loop->index ? 'reveal-d'.$loop->index : ''" />
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- ============ CTA BAND ============ -->
<section class="section-sm bg-white">
  <div class="container">
    <x-cta-band>
      <x-slot:deco>
        <div class="deco deco-ring" style="width:160px;height:160px;top:-50px;left:-40px"></div>
        <div class="deco deco-dot" style="width:90px;height:90px;bottom:-30px;right:30px;background:var(--yellow-300)"></div>
      </x-slot:deco>
      <span class="eyebrow" style="color:#fff;opacity:.9">{{ content('home.cta.eyebrow') }}</span>
      <h2 style="margin-top:12px">{{ content('home.cta.title') }}</h2>
      <p>{{ content('home.cta.text') }}</p>
      <div style="display:flex;gap:var(--s-3);justify-content:center;flex-wrap:wrap">
        <a class="btn btn-white btn-lg" href="{{ route('contact') }}">{{ content('home.cta.button') }}</a>
        @if (filled(setting('phone')))
          <a class="btn btn-lg" href="tel:{{ setting('phone_href', setting('phone')) }}" style="background:rgba(255,255,255,.16);color:#fff">☏ {{ setting('phone') }}</a>
        @endif
      </div>
    </x-cta-band>
  </div>
</section>
@endsection
