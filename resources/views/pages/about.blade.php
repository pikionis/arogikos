@extends('layouts.app')

@section('meta_title', content('about.seo.title'))
@section('meta_description', content('about.seo.description'))

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
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

<!-- TEAM -->
@if ($authors->isNotEmpty())
<section class="section bg-white">
  <x-scene-backdrop for="about.team" />
  <div class="container">
    {{-- Η επικεφαλίδα είναι πλέον το hero της σελίδας: «Η ομάδα μας / Οι
         άνθρωποι της Αρωγής». Δύο φορές η ίδια θα ήταν δύο φορές η ίδια. --}}
    <div class="grid-3" style="gap:var(--s-10)">
      @foreach ($authors as $author)
        @php
          $tint = ['var(--soft-green)','var(--soft-coral)','var(--soft-yellow)'][$loop->index % 3];
          $delay = ['','reveal-d1','reveal-d2'][$loop->index % 3];
        @endphp
        <x-team-card :author="$author" :tint="$tint" :delay="$delay" :level="2" />
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="section-sm bg-white">
  <x-scene-backdrop for="about.cta" />
  <div class="container">
    <x-cta-band bg="var(--green-700)">
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
