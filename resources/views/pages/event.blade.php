@extends('layouts.app')

@section('meta_title', $event->title.' — '.setting('org_name', 'Αρωγή'))
@section('meta_description', $event->summary(155))
@section('og_type', 'article')
@section('og_image', $event->shareImageUrl() ?? '')
@section('og_image_alt', $event->title)

@section('schema')
    <x-schema :data="App\Support\StructuredData::event($event)" />
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
        ['name' => content('events.hero.crumb'), 'url' => route('events')],
        ['name' => $event->title, 'url' => route('events.show', $event)],
    ])" />
@endsection

@php
    $tint = 'var(--soft-coral)';
    $photos = $event->photos();
@endphp

@section('content')
<article>
  <div class="container column">
    <div style="padding-top:var(--s-12)" class="reveal">
      <p class="breadcrumb">
        <a href="{{ route('home') }}">{{ home_label() }}</a> ·
        <a href="{{ route('events') }}">{{ content('events.hero.crumb') }}</a> ·
        {{ content('events.event_crumb') }}
      </p>

      <h1 style="font-size:clamp(2rem,4.5vw,3rem)">{{ $event->title }}</h1>

      {{-- Πότε και πού: οι δύο ερωτήσεις που φέρνουν κάποιον εδώ. --}}
      <div class="event-facts reveal reveal-d1">
        @if ($event->starts_at)
          <span class="event-fact">
            <span aria-hidden="true">📅</span>
            <span class="sr">Ημερομηνία:</span>
            {{ el_date($event->starts_at) }}
          </span>
          <span class="event-fact">
            <span aria-hidden="true">🕒</span>
            <span class="sr">Ώρα:</span>
            {{ $event->starts_at->format('H:i') }}
          </span>
        @endif

        @if (filled($event->location))
          <span class="event-fact">
            <span aria-hidden="true">📍</span>
            <span class="sr">Τοποθεσία:</span>
            {{ $event->location }}
          </span>
        @endif
      </div>
    </div>
  </div>

  <div class="container column" style="margin-top:var(--s-10)">
    <div class="frame-soft reveal" style="aspect-ratio:21/9">
      <x-media-image :model="$event" collection="cover" motif="group" live :tint="$tint" :alt="$event->title" />
    </div>
  </div>

  @if (filled($event->body))
    <div class="container column">
      <div class="article-body reveal">
        {!! $event->body !!}
      </div>
    </div>
  @endif

  <x-share-bar :url="route('events.show', $event)" :title="$event->title" />

  {{-- Οι φωτογραφίες ανεβαίνουν μετά, οπότε η ενότητα υπάρχει μόνο όταν
       υπάρχουν κιόλας. --}}
  @if ($photos->isNotEmpty())
    <section class="section bg-white" style="padding-top:var(--s-12)">
      <x-scene-backdrop for="event.photos" />
      <div class="container">
        <div class="section-head left reveal" style="margin-bottom:var(--s-8)">
          <span class="eyebrow coral">{{ content('events.hero.crumb') }}</span>
          <h2>{{ content('events.photos_title') }}</h2>
        </div>

        <div class="gal-grid">
          @foreach ($photos as $i => $photo)
            @php
              $isArch = $i % 2 === 0;
              $frame = $isArch ? 'frame-arch' : 'frame-circle';
              $aspect = $isArch ? ($i % 4 === 0 ? 'tall' : 'wide') : 'sq';
              $caption = $photo->getCustomProperty('caption') ?: $event->title;
            @endphp
            <div class="gal-item {{ $frame }} {{ $aspect }} reveal"
                 data-lightbox data-shape="{{ $isArch ? 'arch' : 'circle' }}" data-tint="{{ $tint }}"
                 data-cap="{{ $caption }}" data-full="{{ $photo->getUrl('web') }}">
              <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $caption }}"
                   class="gal-img" loading="lazy" decoding="async">
              <div class="gal-cap">{{ $caption }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif
</article>

@if ($more->isNotEmpty())
<section class="section bg-green arch-top">
  <x-scene-backdrop for="events.more" />
  <div class="container">
    <div class="section-head left reveal" style="margin-bottom:var(--s-8)">
      <span class="eyebrow coral">{{ content('events.upcoming.eyebrow') }}</span>
      <h2>{{ content('events.more_title') }}</h2>
    </div>
    <div style="display:grid;gap:var(--s-5);max-width:860px">
      @foreach ($more as $other)
        <x-event-card :event="$other" :delay="$loop->index % 2 ? 'reveal-d1' : ''" />
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="section-sm bg-white">
  <div class="container center">
    <a class="btn btn-ghost" href="{{ route('events') }}">← {{ content('events.back') }}</a>
  </div>
</section>
@endsection
