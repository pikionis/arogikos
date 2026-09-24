@extends('layouts.app')

@section('meta_title', 'Η σελίδα δεν βρέθηκε — Αρωγή')

@section('content')
<section class="section center" style="padding-block:clamp(var(--s-16),12vw,var(--s-24))">
  <div class="container" style="max-width:560px">
    <svg viewBox="0 0 200 120" style="width:200px;margin:0 auto var(--s-6)" aria-hidden="true">
      <rect x="10" y="96" width="180" height="8" rx="4" fill="#1E9E58"/>
      <path d="M55 100 A45 45 0 0 1 145 100" fill="none" stroke="#E26D5A" stroke-width="14" stroke-linecap="round"/>
      <rect x="82" y="60" width="36" height="40" rx="6" fill="#178049"/>
      <circle cx="100" cy="34" r="14" fill="none" stroke="#F4B62A" stroke-width="6"/>
    </svg>
    <h1 style="font-size:clamp(2.5rem,8vw,4rem);color:var(--green-700)">404</h1>
    <h2 style="margin:var(--s-3) 0 var(--s-4)">{{ content('error404.title') }}</h2>
    <p class="lead" style="margin-bottom:var(--s-8)">{{ content('error404.text') }}</p>
    <div style="display:flex;gap:var(--s-3);justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary btn-lg" href="{{ route('home') }}">{{ content('error404.cta_home') }}</a>
      <a class="btn btn-ghost btn-lg" href="{{ route('contact') }}">{{ content('error404.cta_contact') }}</a>
    </div>
  </div>
</section>
@endsection
