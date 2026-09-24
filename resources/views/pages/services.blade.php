@extends('layouts.app')

@section('meta_title', $page?->seo_title ?? 'Υπηρεσίες — Αρωγή')
@section('meta_description', $page?->seo_description ?? 'Μαθησιακή υποστήριξη, συμβουλευτική και επαγγελματικός προσανατολισμός στην Αρωγή, Κως.')

@section('content')
<x-page-hero bg="bg-green" :crumb="content('services.hero.crumb')" :title="content('services.hero.title')"
  :lead="content('services.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-ring" style="width:130px;height:130px;top:24px;right:10%"></div>
  </x-slot:deco>
</x-page-hero>

@php
  // The zig-zag rhythm is positional design: section background, which side
  // the media sits on, and the tint behind the icon all alternate. Only the
  // copy of each service is editable — shared.services, the same three items
  // the home page cards and the footer column read.
  $layouts = [
    ['section' => 'bg-white', 'rev' => false, 'media' => 'var(--soft-green)', 'tagBg' => 'var(--soft-green)', 'tagColor' => 'var(--green-700)'],
    ['section' => 'bg-coral', 'rev' => true,  'media' => '#fff',              'tagBg' => '#fff',              'tagColor' => 'var(--coral-700)'],
    ['section' => 'bg-white', 'rev' => false, 'media' => 'var(--soft-yellow)', 'tagBg' => 'var(--soft-yellow)', 'tagColor' => 'var(--yellow-700)'],
  ];
@endphp

@foreach (content_items('shared.services') as $i => $service)
  @php $layout = $layouts[$i % 3]; @endphp

  <section class="section {{ $layout['section'] }}" data-play>
    <div class="container">
      <div class="zig {{ $layout['rev'] ? 'rev' : '' }} reveal">
        <div class="zig-media frame-soft" style="background:{{ $layout['media'] }};display:flex;align-items:center;justify-content:center">
          <x-service-icon :index="$i" size="200px" />
        </div>
        <div>
          <span class="zig-tag" style="background:{{ $layout['tagBg'] }};color:{{ $layout['tagColor'] }}">{{ content('services.tag_prefix') }} {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
          <h2>{{ $service['name'] ?? '' }}</h2>
          <p class="lead" style="margin:var(--s-4) 0 var(--s-5)">{{ $service['lead'] ?? '' }}</p>
          @if (filled($service['bullets'] ?? []))
            <ul class="tri-list" style="margin-bottom:var(--s-6)">
              @foreach ((array) $service['bullets'] as $bullet)
                <li>{{ $bullet }}</li>
              @endforeach
            </ul>
          @endif
          <p style="color:var(--ink-400);font-weight:700;font-family:var(--font-head)">{{ $service['note'] ?? '' }}</p>
        </div>
      </div>
    </div>
  </section>
@endforeach

<section class="section-sm bg-green">
  <div class="container center">
    <div class="reveal" style="max-width:640px;margin-inline:auto">
      <h2>{{ content('services.cta.title') }}</h2>
      <p class="lead" style="margin:var(--s-4) 0 var(--s-8)">{{ content('services.cta.text') }}</p>
      <a class="btn btn-primary btn-lg" href="{{ route('contact') }}">{{ content('services.cta.button') }}</a>
    </div>
  </div>
</section>
@endsection
