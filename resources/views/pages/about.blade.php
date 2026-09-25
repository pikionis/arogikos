@extends('layouts.app')

@section('meta_title', $page?->seo_title ?? 'Σχετικά με εμάς — Αρωγή')
@section('meta_description', $page?->seo_description ?? 'Η ιστορία, η φιλοσοφία και η ομάδα της Αρωγής — κέντρο μάθησης και συμβουλευτικής στην Κω.')

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => 'Αρχική', 'url' => route('home')],
        ['name' => content('about.hero.crumb'), 'url' => route('about')],
    ])" />
@endsection

@section('content')
<x-page-hero bg="bg-yellow" :crumb="content('about.hero.crumb')" :title="content('about.hero.title')"
  :lead="content('about.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-ring" style="width:130px;height:130px;top:30px;left:8%"></div>
    <div class="deco deco-dot" style="width:60px;height:60px;bottom:20px;right:12%;background:var(--green-300)"></div>
  </x-slot:deco>
</x-page-hero>

<!-- STORY + SUNRISE -->
<section class="section bg-white">
  <div class="container">
    <div class="grid-2" style="gap:var(--s-12);align-items:center">
      <div class="reveal">
        <span class="eyebrow">{{ content('about.story.eyebrow') }}</span>
        <h2 style="margin:12px 0 var(--s-5)">{{ content('about.story.title') }}</h2>
        <div class="story-prose" style="color:var(--ink-600);font-size:1.08rem">
          {!! $page?->body !!}
        </div>
      </div>
      <div class="sunrise-stage frame-soft" style="aspect-ratio:1/1;position:relative;background:linear-gradient(180deg,#FFF7E6 0%,#FCEEE9 100%);overflow:hidden">
        <svg viewBox="0 0 400 400" style="position:absolute;inset:0;width:100%;height:100%" aria-hidden="true">
          <g class="sunrise-glow"><circle cx="200" cy="250" r="150" fill="#F9D27E" opacity=".35"/></g>
          <g class="sunrise-sun"><circle cx="200" cy="250" r="60" fill="#F4B62A"/></g>
          <rect x="0" y="248" width="400" height="10" rx="5" fill="#1E9E58"/>
          <path d="M120 258 A80 80 0 0 1 280 258" fill="none" stroke="#E26D5A" stroke-width="18" stroke-linecap="round"/>
          <rect x="170" y="190" width="60" height="68" rx="6" fill="#178049"/>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- MISSION + VALUES -->
<section class="section bg-green arch-top">
  <div class="container">
    <x-section-head :eyebrow="content('about.values.eyebrow')" :title="content('about.values.title')"
      :lead="content('about.values.lead')" />
    <div class="grid-4" style="margin-top:var(--s-12)">
      @php
        // Each card carries its own little decoration — a yellow disc, a green
        // bar, a coral arch, three dots. Positional design; only the title and
        // text are editable.
        $valueDelays = ['', 'reveal-d1', 'reveal-d2', 'reveal-d3'];
      @endphp

      @foreach (content_items('about.values.items') as $i => $value)
        <div class="card reveal {{ $valueDelays[$i % 4] }}">
          @switch($i % 4)
            @case(0)
              <div style="width:54px;height:54px;border-radius:50%;background:var(--yellow);margin-bottom:var(--s-4)"></div>
              @break
            @case(1)
              <div style="width:54px;height:14px;border-radius:7px;background:var(--green);margin:20px 0 calc(var(--s-4) + 20px)"></div>
              @break
            @case(2)
              <svg width="54" height="40" viewBox="0 0 54 40" style="margin-bottom:var(--s-4)"><path d="M5 36 A22 22 0 0 1 49 36" fill="none" stroke="#E26D5A" stroke-width="8" stroke-linecap="round"/></svg>
              @break
            @default
              <div style="display:flex;gap:8px;margin-bottom:var(--s-4)"><span style="width:18px;height:18px;border-radius:50%;background:var(--yellow)"></span><span style="width:18px;height:18px;border-radius:50%;background:var(--coral)"></span><span style="width:18px;height:18px;border-radius:50%;background:var(--green)"></span></div>
          @endswitch

          <h3 style="font-size:1.15rem">{{ $value['title'] ?? '' }}</h3>
          <p style="color:var(--ink-600)">{{ $value['text'] ?? '' }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- TEAM -->
@if ($authors->isNotEmpty())
<section class="section bg-white">
  <div class="container">
    <x-section-head :eyebrow="content('about.team.eyebrow')" eyebrow-color="coral" :title="content('about.team.title')"
      :lead="content('about.team.lead')" />
    <div class="grid-3" style="margin-top:var(--s-12);gap:var(--s-10)">
      @foreach ($authors as $author)
        @php
          $tint = ['var(--soft-green)','var(--soft-coral)','var(--soft-yellow)'][$loop->index % 3];
          $delay = ['','reveal-d1','reveal-d2'][$loop->index % 3];
        @endphp
        <x-team-card :author="$author" :tint="$tint" :delay="$delay" />
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="section-sm bg-white">
  <div class="container">
    <x-cta-band bg="var(--green)">
      <x-slot:deco>
        <div class="deco deco-dot" style="width:90px;height:90px;top:-30px;left:8%;background:var(--yellow-300)"></div>
        <div class="deco deco-ring" style="width:130px;height:130px;bottom:-50px;right:5%;border-color:var(--coral-300)"></div>
      </x-slot:deco>
      <h2>{{ content('about.cta.title') }}</h2>
      <p>{{ content('about.cta.text') }}</p>
      <a class="btn btn-white btn-lg" href="{{ route('contact') }}">{{ content('about.cta.button') }}</a>
    </x-cta-band>
  </div>
</section>
@endsection
