@extends('layouts.app')

@section('meta_title', 'Επικοινωνία — Αρωγή')
@section('meta_description', 'Επικοινωνήστε με την Αρωγή στην Κω — τηλέφωνο, email, φόρμα και χάρτης.')

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => 'Αρχική', 'url' => route('home')],
        ['name' => content('contact.hero.crumb'), 'url' => route('contact')],
    ])" />
@endsection

@section('content')
<x-page-hero bg="bg-yellow" :crumb="content('contact.hero.crumb')" :title="content('contact.hero.title')"
  :lead="content('contact.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-dot" style="width:64px;height:64px;top:36px;right:12%;background:var(--coral-300)"></div>
  </x-slot:deco>
</x-page-hero>

<section class="section bg-white">
  <div class="container">
    <div class="contact-grid">
      <!-- FORM -->
      <div class="contact-form-wrap reveal {{ session('contact_sent') ? 'sent' : '' }}">
        <div class="form-body">
          <h2 style="font-size:1.7rem;margin-bottom:var(--s-2)">{{ content('contact.form.title') }}</h2>
          <p style="color:var(--ink-600);margin-bottom:var(--s-6)">{{ content('contact.form.lead') }}</p>

          @if ($errors->any())
            <div role="alert" style="background:var(--soft-coral);color:var(--coral-700);border-radius:var(--r-md);padding:var(--s-3) var(--s-4);margin-bottom:var(--s-4);font-weight:600">
              {{ content('contact.form.error') }}
            </div>
          @endif

          <form id="contactForm" method="POST" action="{{ route('contact.store') }}" novalidate>
            @csrf

            {{-- Honeypot: real visitors never see or fill this --}}
            <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">
              <label for="company">Εταιρεία</label>
              <input type="text" id="company" name="company" tabindex="-1" autocomplete="off">
            </div>

            <div class="field">
              <label for="name">Όνομα</label>
              <input id="name" name="name" type="text" placeholder="Το όνομά σας" value="{{ old('name') }}"
                     required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
              @error('name')<small id="name-error" style="color:var(--coral-700);font-weight:600">{{ $message }}</small>@enderror
            </div>

            <div class="grid-2" style="gap:var(--s-4)">
              <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="email@example.com" value="{{ old('email') }}"
                       required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<small id="email-error" style="color:var(--coral-700);font-weight:600">{{ $message }}</small>@enderror
              </div>
              <div class="field">
                <label for="phone">Τηλέφωνο</label>
                <input id="phone" name="phone" type="tel" placeholder="69_ _ _ _ _ _ _ _" value="{{ old('phone') }}"
                       @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                @error('phone')<small id="phone-error" style="color:var(--coral-700);font-weight:600">{{ $message }}</small>@enderror
              </div>
            </div>

            <div class="field">
              <label for="msg">Μήνυμα</label>
              <textarea id="msg" name="message" placeholder="Πείτε μας λίγα λόγια για το παιδί σας ή την ερώτησή σας…"
                        required @error('message') aria-invalid="true" aria-describedby="msg-error" @enderror>{{ old('message') }}</textarea>
              @error('message')<small id="msg-error" style="color:var(--coral-700);font-weight:600">{{ $message }}</small>@enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Αποστολή μηνύματος</button>
          </form>
        </div>

        <!-- SUCCESS SCENE -->
        <div class="send-scene">
          <svg viewBox="0 0 360 160" style="width:100%;max-width:360px;margin-inline:auto" aria-hidden="true">
            <rect x="10" y="116" width="340" height="9" rx="4.5" fill="#1E9E58"/>
            <rect class="run-door" x="278" y="74" width="42" height="46" rx="6" fill="#178049"/>
            <path d="M248 120 A52 52 0 0 1 352 120" fill="none" stroke="#E26D5A" stroke-width="14" stroke-linecap="round"/>
            <g class="run-fig" transform="translate(34,120)"><g class="run-bob">
              <path d="M-13 0 C-13 -28 13 -28 13 0 Z" fill="#E26D5A"/>
              <circle cx="0" cy="-38" r="13" fill="#E26D5A"/>
              <circle cx="20" cy="-22" r="10" fill="#F4B62A"/>
            </g></g>
          </svg>
          <div class="send-msg">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--soft-green);display:flex;align-items:center;justify-content:center;margin:0 auto var(--s-4)">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1E9E58" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <h2 style="font-size:1.6rem">{{ content('contact.success.title') }}</h2>
            <p style="color:var(--ink-600);margin-top:var(--s-2)">{{ content('contact.success.text') }}</p>
          </div>
        </div>
      </div>

      <!-- INFO -->
      <div class="reveal reveal-d1">
        <div class="contact-info-card">
          @if (filled(setting('phone')))
          <div class="contact-row">
            <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></span>
            <div><div class="lbl">Τηλέφωνο</div><a class="val" href="tel:{{ setting('phone_href', setting('phone')) }}">{{ setting('phone') }}</a></div>
          </div>
          @endif
          @if (filled(setting('email')))
          <div class="contact-row">
            <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M3 6l9 7 9-7"/></svg></span>
            <div><div class="lbl">Email</div><a class="val" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></div>
          </div>
          @endif
          @if (filled(setting('address')))
          <div class="contact-row">
            <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div><div class="lbl">Διεύθυνση</div><x-site-address class="val" /></div>
          </div>
          @endif
          @if (filled(setting('hours')))
          <div class="contact-row">
            <span class="ci"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div><div class="lbl">Ωράριο</div><span class="val">{{ setting('hours') }}</span></div>
          </div>
          @endif
        </div>
        @if (filled(setting('maps_embed_url')))
        <div class="map-wrap">
          <iframe title="Χάρτης Αρωγής, Κως" src="{{ setting('maps_embed_url') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection
