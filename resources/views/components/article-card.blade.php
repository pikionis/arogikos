@props([
    'article',
    // Card titles sit under a section heading on most pages, but on the blog
    // index they follow the h1 directly — passing the level keeps the document
    // outline from skipping a step.
    'level' => 3,
    'delay' => '',
    'showExcerpt' => false,
    'showDate' => true,
    // A card is one big link, so the share button cannot live inside it —
    // it sits over the cover as its sibling.
    'share' => false,
])

@php
    $color = $article->category_color; // green | coral | yellow
    $tint = ['green' => 'var(--soft-green)', 'coral' => 'var(--soft-coral)', 'yellow' => 'var(--soft-yellow)'][$color] ?? 'var(--soft-green)';
@endphp

<div @class(['card-wrap', 'reveal', $delay]) data-cat="{{ $article->category }}">
@if ($share)
    <x-share-button :url="route('blog.show', $article->slug)" :title="$article->title" />
@endif
<a class="post-card blog-card" href="{{ route('blog.show', $article->slug) }}">
    <div class="post-cover">
        <x-media-image
            :model="$article"
            collection="cover"
            :motif="$article->cover_motif"
            :scene="$article->cover_scene"
            :tint="$tint"
            :alt="$article->title"
            imgClass="cover-zoom"
        />
    </div>
    <div class="post-body">
        <span class="cat-pill {{ $color }}">{{ $article->category_label }}</span>
        <h{{ $level }} class="post-title">{{ $article->title }}</h{{ $level }}>
        <span class="title-underline"></span>
        @if ($showExcerpt && filled($article->excerpt))
            <p class="post-excerpt" style="color:var(--ink-600);font-size:.96rem">{{ $article->excerpt }}</p>
        @endif
        <div class="post-meta">
            {{-- Faces overlap so three of them still read as one byline and
                 do not push the date onto its own line. --}}
            <span class="byline-faces">
                @foreach ($article->authors->take(3) as $person)
                    <x-author-avatar :author="$person" :color="$color" />
                @endforeach
                @if ($article->authors->isEmpty())
                    <x-author-avatar :color="$color" />
                @endif
            </span>
            <span>{{ $article->byline() }}@if ($showDate && $article->published_at) · {{ el_date($article->published_at) }}@endif</span>
        </div>
    </div>
</a>
</div>
