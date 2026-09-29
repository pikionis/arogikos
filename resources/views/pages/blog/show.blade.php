@extends('layouts.app')

@section('meta_title', $article->seo_title ?? $article->title.' — '.setting('org_name', 'Αρωγή'))
@section('meta_description', $article->metaDescription())
@section('og_type', 'article')
@section('og_image', $article->shareImageUrl() ?? '')
@section('og_image_alt', $article->title)

{{-- What Facebook and LinkedIn read to date and attribute a post. Without
     these a shared article is undated, which reads as older than it is. --}}
@section('og_extra')
    @if ($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}">
    @endif
    @if ($article->updated_at)
        <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
    @endif
    @foreach ($article->authors as $person)
        <meta property="article:author" content="{{ $person->name }}">
    @endforeach
    @if (filled($article->category_label))
        <meta property="article:section" content="{{ $article->category_label }}">
    @endif
@endsection

@php
    $color = $article->category_color;
    $tint = ['green' => 'var(--soft-green)', 'coral' => 'var(--soft-coral)', 'yellow' => 'var(--soft-yellow)'][$color] ?? 'var(--soft-green)';
    $words = count(preg_split('/\s+/', trim(strip_tags((string) $article->body))) ?: []);
    $readMinutes = max(1, (int) round($words / 200));
@endphp

@section('schema')
    <x-schema :data="App\Support\StructuredData::article($article)" />
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => home_label(), 'url' => route('home')],
        ['name' => content('blog.hero.crumb'), 'url' => route('blog.index')],
        ['name' => $article->title, 'url' => route('blog.show', $article->slug)],
    ])" />
@endsection

@section('content')
<article>
  <div class="container column">
    <div style="padding-top:var(--s-12)" class="reveal">
      <p class="breadcrumb"><a href="{{ route('home') }}">{{ home_label() }}</a> · <a href="{{ route('blog.index') }}">{{ content('blog.hero.crumb') }}</a> · {{ content('blog.article_crumb') }}</p>
      <span class="cat-pill {{ $color }}" style="margin-bottom:var(--s-4);display:inline-block">{{ $article->category_label }}</span>
      <h1 style="font-size:clamp(2rem,4.5vw,3rem)">{{ $article->title }}</h1>
      @if ($article->authors->isNotEmpty())
      <div class="post-meta" style="margin-top:var(--s-6);font-size:1rem">
        <span class="byline-faces">
          @foreach ($article->authors as $person)
            <x-author-avatar :author="$person" :color="$color" size="46px" />
          @endforeach
        </span>
        <span><strong style="color:var(--ink);font-family:var(--font-head)">{{ $article->byline() }}</strong><br>@if ($article->authors->count() === 1 && filled($article->authors->first()->role_title)){{ $article->authors->first()->role_title }} · @endif{{ el_date($article->published_at) }} · {{ $readMinutes }}′ {{ content('blog.read_suffix') }}</span>
      </div>
      @endif
    </div>
  </div>

  <div class="container column" style="margin-top:var(--s-10)">
    <div class="frame-soft reveal" style="aspect-ratio:21/9">
      <x-media-image :model="$article" collection="cover" :motif="$article->cover_motif" :scene="$article->cover_scene" live :tint="$tint" :alt="$article->title" />
    </div>
  </div>

  <div class="container column">
    <div class="article-body reveal">
      {!! $article->body !!}
    </div>
  </div>

  @php
    // A profile with no bio has nothing to open, so it gets no box.
    $written = $article->authors->filter(fn ($person) => filled($person->bio))->values();
  @endphp

  @foreach ($written as $person)
    @php $authorId = 'author-'.$person->id; @endphp

    <div class="container column" style="margin-top:var(--s-{{ $loop->first ? '12' : '6' }})">
      <div class="author-card reveal" style="--profile-tint:{{ $tint }}">
        <x-author-portrait :author="$person" :id="$authorId" :tint="$tint" frame="author-card-photo" />

        <div class="author-card-text">
          @if ($loop->first)
            <span class="eyebrow coral">{{ content('blog.author_eyebrow') }}</span>
          @endif
          <h3><x-author-name :author="$person" :id="$authorId" /></h3>
          @if (filled($person->role_title))
            <p class="role">{{ $person->role_title }}</p>
          @endif

          <x-author-bio :author="$person" :id="$authorId" :tint="$tint" :lines="3" />
        </div>
      </div>
    </div>

    <x-author-dialog :author="$person" :id="$authorId" :tint="$tint" />
  @endforeach
</article>

<!-- RELATED -->
@if ($related->isNotEmpty())
<section class="section bg-white">
  <x-scene-backdrop for="blog.related" />
  <div class="container">
    <div class="section-head left reveal" style="margin-bottom:var(--s-8)"><span class="eyebrow coral">{{ content('blog.related_eyebrow') }}</span><h2>{{ content('blog.related_title') }}</h2></div>
    <div class="grid-3">
      @foreach ($related as $rel)
        <x-article-card :article="$rel" :delay="['','reveal-d1','reveal-d2'][$loop->index % 3]" :show-date="false" />
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
