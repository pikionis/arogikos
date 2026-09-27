@extends('layouts.app')

@section('meta_title', content('gallery.seo.title'))
@section('meta_description', content('gallery.seo.description'))

@section('schema')
    <x-schema :data="App\Support\StructuredData::breadcrumbs([
        ['name' => 'Αρχική', 'url' => route('home')],
        ['name' => content('gallery.hero.crumb'), 'url' => route('gallery')],
    ])" />
@endsection

@section('content')
<x-page-hero bg="bg-green" :crumb="content('gallery.hero.crumb')" :title="content('gallery.hero.title')"
  :lead="content('gallery.hero.lead')">
  <x-slot:deco>
    <div class="deco deco-ring" style="width:120px;height:120px;bottom:-30px;left:6%;border-color:var(--yellow-300)"></div>
  </x-slot:deco>

  <div class="filters reveal reveal-d2">
    <button class="chip active" data-filter="all">{{ content('gallery.filter_all') }}</button>
    @foreach ($albums as $album)
      <button class="chip" data-filter="{{ $album->slug }}">{{ $album->title }}</button>
    @endforeach
  </div>
</x-page-hero>

<section class="section bg-white">
  <div class="container">
    @php $i = 0; @endphp
    <div class="gal-grid">
      @foreach ($albums as $aIndex => $album)
        @foreach ($album->photos as $photo)
          @php
            $isArch = $i % 2 === 0;
            $shape = $isArch ? 'arch' : 'circle';
            $frame = $isArch ? 'frame-arch' : 'frame-circle';
            $aspect = $isArch ? ($i % 4 === 0 ? 'tall' : 'wide') : 'sq';
            $tint = ['var(--soft-green)','var(--soft-coral)','var(--soft-yellow)'][$aIndex % 3];
            $motif = ['group','one','figures','book','sun'][$i % 5];
            $full = $photo->imageUrl('web');
            $i++;
          @endphp
          <div class="gal-item {{ $frame }} {{ $aspect }} reveal" data-cat="{{ $album->slug }}"
               data-lightbox data-shape="{{ $shape }}" data-tint="{{ $tint }}" data-motif="{{ $motif }}"
               data-cap="{{ $photo->caption }}" @if ($full) data-full="{{ $full }}" @endif>
            <x-media-image :model="$photo" collection="image" :motif="$motif" :tint="$tint" :alt="$photo->caption" imgClass="gal-img" />
            <div class="gal-cap">{{ $photo->caption }}</div>
          </div>
        @endforeach
      @endforeach
    </div>
  </div>
</section>
@endsection
