@extends('layouts.app')

@section('meta_title', 'Blog — Αρωγή')
@section('meta_description', 'Άρθρα και συμβουλές από τους εκπαιδευτικούς και ειδικούς της Αρωγής.')

@section('content')
<x-page-hero bg="bg-coral" :crumb="content('blog.hero.crumb')" :title="content('blog.hero.title')"
  :lead="content('blog.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-dot" style="width:70px;height:70px;top:40px;left:10%;background:var(--yellow-300)"></div>
  </x-slot:deco>

  <div class="filters reveal reveal-d2">
    <a class="chip {{ $category === null ? 'active' : '' }}" href="{{ route('blog.index') }}">{{ content('blog.filter_all') }}</a>
    @foreach ($categories as $key => $meta)
      <a class="chip {{ $category === $key ? 'active' : '' }}" href="{{ route('blog.index', ['category' => $key]) }}">{{ $meta['label'] }}</a>
    @endforeach
  </div>
</x-page-hero>

<section class="section bg-white">
  <div class="container">
    @if ($articles->isNotEmpty())
      <div class="grid-3" style="gap:var(--s-8)">
        @foreach ($articles as $article)
          <x-article-card :article="$article" :delay="['','reveal-d1','reveal-d2'][$loop->index % 3]" :show-excerpt="true" />
        @endforeach
      </div>

      <x-paginator :paginator="$articles" />
    @else
      <p class="lead center reveal">Δεν βρέθηκαν άρθρα σε αυτή την κατηγορία. <a class="link-arrow" href="{{ route('blog.index') }}">Δείτε όλα τα άρθρα</a></p>
    @endif
  </div>
</section>
@endsection
