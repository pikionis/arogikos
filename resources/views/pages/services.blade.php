@extends('layouts.app')

@section('meta_title', content('services.seo.title'))
@section('meta_description', content('services.seo.description'))

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
        ['name' => content('services.hero.crumb'), 'url' => route('services')],
    ])" />
    <x-schema :data="App\Support\StructuredData::faq()" />
@endsection

@section('content')
@php
  // One list for the whole page: the links below and the section ids further
  // down both read the anchor from here, so they cannot drift apart.
  $services = service_list();
  $named = array_values(array_filter($services, fn (array $s) => filled($s['name'] ?? null)));
@endphp

<x-page-hero bg="bg-green" :crumb="content('services.hero.crumb')" :title="content('services.hero.title')"
  :lead="content('services.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-ring" style="width:130px;height:130px;top:24px;right:10%"></div>
  </x-slot:deco>

  {{-- A parent arrives for one thing. Naming them all here means they land on
       it, instead of scrolling past everything else to find out we do it. --}}
  @if (count($named) > 1)
    <nav class="jump-list reveal reveal-d2" aria-label="{{ content('services.hero.crumb') }}">
      @foreach ($named as $service)
        <a href="#{{ $service['anchor'] }}">{{ $service['name'] }}</a>
      @endforeach
    </nav>
  @endif
</x-page-hero>

@php
  // The zig-zag rhythm is positional: section background, which side the
  // media sits on, and the tint behind the illustration all alternate, so it
  // keeps working however many services there are and in whatever order the
  // panel puts them.
  $layouts = [
    ['section' => 'bg-white', 'rev' => false, 'media' => 'var(--soft-green)', 'tagBg' => 'var(--soft-green)', 'tagColor' => 'var(--green-700)'],
    ['section' => 'bg-coral', 'rev' => true,  'media' => '#fff',              'tagBg' => '#fff',              'tagColor' => 'var(--coral-700)'],
    ['section' => 'bg-white', 'rev' => false, 'media' => 'var(--soft-yellow)', 'tagBg' => 'var(--soft-yellow)', 'tagColor' => 'var(--yellow-700)'],
  ];
@endphp

@foreach ($services as $i => $service)
  @php $layout = $layouts[$i % 3]; @endphp

  <section id="{{ $service['anchor'] }}" class="section {{ $layout['section'] }}" data-play>
    <div class="container">
      <div class="zig {{ $layout['rev'] ? 'rev' : '' }} reveal">
        <div class="zig-media frame-soft" style="background:{{ $layout['media'] }};overflow:hidden">
          @if (filled($service['icon'] ?? null))
            <x-article-cover :scene="$service['icon']" live />
          @else
            <div style="display:flex;align-items:center;justify-content:center;height:100%">
              <x-service-icon :index="$i" size="200px" />
            </div>
          @endif
        </div>
        <div>
          <span class="zig-tag" style="background:{{ $layout['tagBg'] }};color:{{ $layout['tagColor'] }}">{{ content('services.tag_prefix') }} {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
          <h2>{{ $service['name'] ?? '' }}</h2>
          <p class="lead" style="margin:var(--s-4) 0 var(--s-5)">{{ $service['lead'] ?? '' }}</p>
          @if (filled($service['signs'] ?? []) && filled(content('services.signs_heading')))
            <h3 style="font-size:1.05rem;margin:var(--s-5) 0 var(--s-3)">{{ content('services.signs_heading') }}</h3>
            <ul class="tri-list" style="margin-bottom:var(--s-5)">
              @foreach ((array) $service['signs'] as $sign)
                <li>{{ $sign }}</li>
              @endforeach
            </ul>
          @endif

          @if (filled($service['bullets'] ?? []) && filled(content('services.bullets_heading')))
            <h3 style="font-size:1.05rem;margin:var(--s-5) 0 var(--s-3)">{{ content('services.bullets_heading') }}</h3>
            <ul class="tri-list" style="margin-bottom:var(--s-5)">
              @foreach ((array) $service['bullets'] as $bullet)
                <li>{{ $bullet }}</li>
              @endforeach
            </ul>
          @endif

          @if (filled($service['process'] ?? []) && filled(content('services.process_heading')))
            <h3 style="font-size:1.05rem;margin:var(--s-5) 0 var(--s-3)">{{ content('services.process_heading') }}</h3>
            <ol class="step-list" style="margin-bottom:var(--s-6)">
              @foreach ((array) $service['process'] as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>
          @endif
          <p style="color:var(--ink-400);font-weight:700;font-family:var(--font-head)">{{ $service['note'] ?? '' }}</p>
        </div>
      </div>
    </div>
  </section>
@endforeach

@php
  // Blank slots are how staff remove a question, so only render the filled ones.
  $faqs = collect(content_items('services.faq'))
      ->filter(fn (array $f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))
      ->values();
@endphp

@if ($faqs->isNotEmpty())
<section class="section bg-white" id="syxnes-erotiseis">
  <x-scene-backdrop for="services.faq" />
  <div class="container" style="max-width:820px">
    <x-section-head :title="content('services.faq_title')" :lead="content('services.faq_lead')" />

    <div style="display:grid;gap:var(--s-3);margin-top:var(--s-10)">
      @foreach ($faqs as $faq)
        <details class="faq-item reveal">
          <summary>{{ $faq['question'] }}</summary>
          <p>{{ $faq['answer'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="section-sm bg-green">
  <x-scene-backdrop for="services.cta" />
  <div class="container center">
    <div class="reveal" style="max-width:640px;margin-inline:auto">
      <h2>{{ content('services.cta.title') }}</h2>
      <p class="lead" style="margin:var(--s-4) 0 var(--s-8)">{{ content('services.cta.text') }}</p>
      <a class="btn btn-primary btn-lg" href="{{ route('contact') }}">{{ content('services.cta.button') }}</a>
    </div>
  </div>
</section>
@endsection
