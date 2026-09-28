@props([
    // Authors who have something published, already filtered by the controller.
    'authors',
    // The chosen author's id, or null for all of them.
    'selected' => null,
    // Kept so that choosing an author does not throw away the category.
    'category' => null,
])

@php
    $chosen = $selected ? $authors->firstWhere('id', $selected) : null;
    $tones = ['green', 'coral', 'yellow'];
@endphp

{{-- A disclosure, not a row of chips: six names side by side is a wall, and
     the reader is looking for one person. <details> is keyboard-operable and
     announced as a disclosure without a line of script. --}}
<details class="author-select reveal reveal-d2">
    <summary>
        @if ($chosen)
            <x-author-avatar :author="$chosen" color="green" size="30px" />
            <span class="author-select-label">{{ $chosen->name }}</span>
        @else
            <span class="author-select-label">{{ content('blog.filter_author_all') }}</span>
        @endif
        <span class="author-select-caret" aria-hidden="true">▾</span>
    </summary>

    <div class="author-select-panel">
        <a @class(['author-option', 'is-on' => $selected === null])
           href="{{ route('blog.index', array_filter(['category' => $category])) }}">
            <span class="author-option-all" aria-hidden="true">✳</span>
            <span>
                <strong>{{ content('blog.filter_author_all') }}</strong>
                <em>{{ $authors->count() }} {{ content('blog.filter_author_count') }}</em>
            </span>
        </a>

        @foreach ($authors as $person)
            <a @class(['author-option', 'is-on' => $selected === $person->id])
               href="{{ route('blog.index', array_filter(['category' => $category, 'author' => $person->id])) }}">
                <x-author-avatar :author="$person" :color="$tones[$loop->index % 3]" size="42px" />
                <span>
                    <strong>{{ $person->name }}</strong>
                    @if (filled($person->role_title))
                        <em>{{ $person->role_title }}</em>
                    @endif
                </span>
            </a>
        @endforeach
    </div>
</details>
