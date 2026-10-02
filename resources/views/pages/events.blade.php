@extends('layouts.app')

@section('meta_title', content('events.seo.title'))
@section('meta_description', content('events.seo.description'))

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
        ['name' => content('events.hero.crumb'), 'url' => route('events')],
    ])" />
@endsection

@section('content')
<x-page-hero bg="bg-yellow" class="events-hero" :crumb="content('events.hero.crumb')" :title="content('events.hero.title')"
  :lead="content('events.hero.lead')">
  <svg viewBox="0 0 520 150" style="max-width:460px;margin:var(--s-8) auto 0;display:block" aria-hidden="true">
    <path d="M150 130 A110 110 0 0 1 370 130" fill="none" stroke="#E26D5A" stroke-width="16" stroke-linecap="round"/>
    <rect x="40" y="128" width="440" height="8" rx="4" fill="#1E9E58"/>
    <g class="gather-fig gf1" transform="translate(200,128)"><circle cx="0" cy="-40" r="13" fill="#1E9E58"/><path d="M-14 0 C-14 -30 14 -30 14 0 Z" fill="#1E9E58"/></g>
    <g class="gather-fig gf2" transform="translate(235,128)"><circle cx="0" cy="-40" r="13" fill="#F4B62A"/><path d="M-14 0 C-14 -30 14 -30 14 0 Z" fill="#F4B62A"/></g>
    <g class="gather-fig gf3" transform="translate(270,128)"><circle cx="0" cy="-40" r="13" fill="#E26D5A"/><path d="M-14 0 C-14 -30 14 -30 14 0 Z" fill="#E26D5A"/></g>
    <g class="gather-fig gf4" transform="translate(305,128)"><circle cx="0" cy="-40" r="13" fill="#1E9E58"/><path d="M-14 0 C-14 -30 14 -30 14 0 Z" fill="#1E9E58"/></g>
    <g class="gather-fig gf5" transform="translate(340,128)"><circle cx="0" cy="-40" r="13" fill="#F4B62A"/><path d="M-14 0 C-14 -30 14 -30 14 0 Z" fill="#F4B62A"/></g>
  </svg>

  {{-- Εμφανίζεται μόνο όταν υπάρχει κάτι να διαλέξει κανείς: με μία χρονιά
       το φίλτρο δεν φιλτράρει τίποτα και απλώς προσθέτει θόρυβο. --}}
  @if ($years->count() > 1)
    <div class="filters reveal reveal-d2">
      <a class="chip {{ $year === null ? 'active' : '' }}" href="{{ route('events') }}">{{ content('events.filter_year_all') }}</a>
      @foreach ($years as $y)
        <a class="chip {{ $year === $y ? 'active' : '' }}" href="{{ route('events', ['year' => $y]) }}">{{ $y }}</a>
      @endforeach
    </div>
  @endif
</x-page-hero>

<!-- UPCOMING -->
<section class="section bg-white">
  <x-scene-backdrop for="events.upcoming" />
  <div class="container">
    <div class="section-head left reveal" style="margin-bottom:var(--s-8)"><span class="eyebrow">{{ content('events.upcoming.eyebrow') }}</span><h2>{{ content('events.upcoming.title') }}</h2></div>
    @if ($upcoming->isNotEmpty())
      <div style="display:grid;gap:var(--s-5);max-width:860px">
        @foreach ($upcoming as $event)
          <x-event-card :event="$event" :share="true" :delay="$loop->index % 2 ? 'reveal-d1' : ''" />
        @endforeach
      </div>
    @else
      <p class="lead reveal">{{ content('events.upcoming.empty') }}</p>
    @endif
  </div>
</section>

<!-- PAST -->
@if ($past->isNotEmpty())
<section class="section bg-green arch-top">
  <x-scene-backdrop for="events.past" />
  <div class="container">
    <div class="section-head left reveal" style="margin-bottom:var(--s-8)"><span class="eyebrow coral">{{ content('events.past.eyebrow') }}</span><h2>{{ content('events.past.title') }}</h2></div>
    <div style="display:grid;gap:var(--s-5);max-width:860px">
      @foreach ($past as $event)
        <x-event-card :event="$event" :past="true" :share="true" :delay="$loop->index % 2 ? 'reveal-d1' : ''" />
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
