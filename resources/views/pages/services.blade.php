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
       it, instead of scrolling past everything else to find out we do it.

       Σε τηλέφωνο όμως δεκατέσσερα ονόματα δεν χωρούν: γίνονταν μια οριζόντια
       λωρίδα που την έσερνες στα τυφλά μέχρι να βρεις το σωστό. Εκεί μπαίνει ο
       εγγενής επιλογέας — ένα άγγιγμα, η λίστα του ίδιου του κινητού, όλα τα
       ονόματα ακέραια. Οι πλάκες μένουν για τις μεγάλες οθόνες, και για όποιον
       δεν έχει JavaScript σε οποιαδήποτε οθόνη. --}}
  @if (count($named) > 1)
    <select class="jump-select reveal reveal-d2" id="svc-picker"
            aria-label="{{ content('services.jump_label') }}">
      <option value="">{{ content('services.jump_label') }}</option>
      @foreach ($named as $service)
        <option value="{{ $service['anchor'] }}">{{ $service['name'] }}</option>
      @endforeach
    </select>

    <nav class="jump-list reveal reveal-d2" aria-label="{{ content('services.hero.crumb') }}">
      @foreach ($named as $service)
        <a href="#{{ $service['anchor'] }}">{{ $service['name'] }}</a>
      @endforeach
    </nav>
  @endif
</x-page-hero>

{{-- Δεκατέσσερις υπηρεσίες ανοιχτές η μία κάτω από την άλλη έκαναν μια σελίδα
     δώδεκα χιλιάδων pixel: για να φτάσεις στη δέκατη τέταρτη περνούσες
     υποχρεωτικά μέσα από τις δεκατρείς. Τώρα η καθεμία δείχνει όνομα και δυο
     γραμμές εισαγωγής, και ανοίγει όποια θέλεις.

     Το <details> τα κάνει όλα μόνο του: πληκτρολόγιο, αναγνώστης οθόνης και
     λειτουργία χωρίς JavaScript έρχονται δωρεάν, και το κείμενο μένει μέσα στη
     σελίδα ακόμα κι όταν είναι κλειστό — οπότε η Google το διαβάζει κανονικά
     και οι υπηρεσίες δεν χάνουν την ορατότητά τους στην αναζήτηση.

     Η σειρά δεν είναι γραμμένη στην πέτρα: πατώντας ένα όνομα παραπάνω, η
     υπηρεσία έρχεται εδώ πρώτη και ανοίγει. Ο ρυθμός των χρωμάτων είναι θέσης
     και όχι περιεχομένου, γι' αυτό ζει στο CSS με nth-child: όταν η σειρά
     αλλάξει, ξαναδένει μόνος του. --}}
<section class="section bg-white">
  <div class="container">
    <div class="svc-list" id="svc-list">
      @foreach ($services as $i => $service)
        <details class="svc-band" id="{{ $service['anchor'] }}" tabindex="-1" data-play>
          <summary class="svc-head">
            <span class="svc-ico"><x-service-icon :index="$i" size="46px" /></span>
            <span class="svc-head-text">
              <h2>{{ $service['name'] ?? '' }}</h2>
              <p>{{ $service['lead'] ?? '' }}</p>
            </span>
            <span class="svc-toggle">
              <span class="svc-toggle-word">{{ content('services.more_label') }}</span>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </span>
          </summary>

          <div class="svc-body">
            <div class="zig">
              <div class="zig-media frame-soft">
                @if (filled($service['icon'] ?? null))
                  <x-article-cover :scene="$service['icon']" live />
                @else
                  <div style="display:flex;align-items:center;justify-content:center;height:100%">
                    <x-service-icon :index="$i" size="200px" />
                  </div>
                @endif
              </div>
              <div>
                @if (filled($service['signs'] ?? []) && filled(content('services.signs_heading')))
                  <h3>{{ content('services.signs_heading') }}</h3>
                  <ul class="tri-list">
                    @foreach ((array) $service['signs'] as $sign)
                      <li>{{ $sign }}</li>
                    @endforeach
                  </ul>
                @endif

                @if (filled($service['bullets'] ?? []) && filled(content('services.bullets_heading')))
                  <h3>{{ content('services.bullets_heading') }}</h3>
                  <ul class="tri-list">
                    @foreach ((array) $service['bullets'] as $bullet)
                      <li>{{ $bullet }}</li>
                    @endforeach
                  </ul>
                @endif

                @if (filled($service['process'] ?? []) && filled(content('services.process_heading')))
                  <h3>{{ content('services.process_heading') }}</h3>
                  <ol class="step-list">
                    @foreach ((array) $service['process'] as $step)
                      <li>{{ $step }}</li>
                    @endforeach
                  </ol>
                @endif

                <p class="svc-note">{{ $service['note'] ?? '' }}</p>
              </div>
            </div>
          </div>
        </details>
      @endforeach
    </div>
  </div>
</section>

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
