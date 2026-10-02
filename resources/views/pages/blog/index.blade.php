@extends('layouts.app')

@section('meta_title', content('blog.seo.title'))
@section('meta_description', content('blog.seo.description'))

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
        ['name' => content('blog.hero.crumb'), 'url' => route('blog.index')],
    ])" />
@endsection

@section('content')
<x-page-hero bg="bg-coral" :crumb="content('blog.hero.crumb')" :title="content('blog.hero.title')"
  :lead="content('blog.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-dot" style="width:70px;height:70px;top:40px;left:10%;background:var(--yellow-300)"></div>
  </x-slot:deco>

  {{-- Δύο ανεξάρτητα φίλτρα: η επιλογή του ενός κρατά την επιλογή του άλλου,
       ώστε «Μαθησιακές δυσκολίες + Μαρία» να είναι εφικτό σε δύο κλικ. --}}
  <div class="filters reveal reveal-d2">
    <a class="chip {{ $category === null ? 'active' : '' }}" href="{{ route('blog.index', array_filter(['author' => $author])) }}">{{ content('blog.filter_all') }}</a>
    @foreach ($categories as $key => $meta)
      <a class="chip {{ $category === $key ? 'active' : '' }}" href="{{ route('blog.index', array_filter(['category' => $key, 'author' => $author])) }}">{{ $meta['label'] }}</a>
    @endforeach
  </div>

</x-page-hero>

<section class="section bg-white">
  <x-scene-backdrop for="blog.index" />
  <div class="container">
    {{-- Ο επιλογέας συντάκτη κάθεται εδώ και όχι στο hero: το hero κόβει ό,τι
         βγαίνει έξω του (overflow:hidden για τα διακοσμητικά), οπότε το
         πτυσσόμενο άνοιγε κρυμμένο. Εδώ ανοίγει ελεύθερα, και στέκεται
         δίπλα στη λίστα που φιλτράρει. --}}
    @if ($authors->count() > 1)
      <div class="list-toolbar reveal">
        <span class="list-toolbar-label">{{ content('blog.filter_author_label') }}</span>
        <x-author-filter :authors="$authors" :selected="$author" :category="$category" />
      </div>
    @endif

    @if ($articles->isNotEmpty())
      <div class="grid-3" style="gap:var(--s-8)">
        @foreach ($articles as $article)
          <x-article-card :article="$article" :delay="['','reveal-d1','reveal-d2'][$loop->index % 3]" :show-excerpt="true" :level="2" :share="true" />
        @endforeach
      </div>

      <x-paginator :paginator="$articles" />
    @else
      <p class="lead center reveal">{{ content('blog.empty') }} <a class="link-arrow" href="{{ route('blog.index') }}">{{ content('blog.empty_link') }}</a></p>
    @endif
  </div>
</section>
@endsection
