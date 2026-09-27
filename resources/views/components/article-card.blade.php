@props([
    'article',
    // Card titles sit under a section heading on most pages, but on the blog
    // index they follow the h1 directly — passing the level keeps the document
    // outline from skipping a step.
    'level' => 3,
    'delay' => '',
    'showExcerpt' => false,
    'showDate' => true,
])

@php
    $color = $article->category_color; // green | coral | yellow
    $tint = ['green' => 'var(--soft-green)', 'coral' => 'var(--soft-coral)', 'yellow' => 'var(--soft-yellow)'][$color] ?? 'var(--soft-green)';
@endphp

<a class="post-card blog-card reveal {{ $delay }}" href="{{ route('blog.show', $article->slug) }}" data-cat="{{ $article->category }}">
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
        <h{{ $level }}>{{ $article->title }}</h{{ $level }}>
        <span class="title-underline"></span>
        @if ($showExcerpt && filled($article->excerpt))
            <p style="color:var(--ink-600);font-size:.96rem">{{ $article->excerpt }}</p>
        @endif
        <div class="post-meta">
            <x-author-avatar :author="$article->author" :color="$color" />
            <span>{{ $article->author?->name }}@if ($showDate && $article->published_at) · {{ el_date($article->published_at) }}@endif</span>
        </div>
    </div>
</a>
