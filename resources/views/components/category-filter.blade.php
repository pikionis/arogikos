@props([
    // Active categories, keyed by key, as the controller supplies them.
    'categories' => [],
    // The chosen key, or null for all of them.
    'selected' => null,
    // Kept so that choosing a category does not throw away the author.
    'author' => null,
])

@php
    $chosen = $selected ? ($categories[$selected] ?? null) : null;
@endphp

{{-- The same disclosure as the author picker beside it, because they do the
     same job and should not need to be learned twice. <details> is
     keyboard-operable and announced as a disclosure without a line of
     script. --}}
<details class="list-select is-categories reveal reveal-d2">
    <summary>
        @if ($chosen)
            <span class="cat-dot {{ $chosen['color'] }}" aria-hidden="true"></span>
            <span class="list-select-label">{{ $chosen['label'] }}</span>
        @else
            <span class="list-select-label">{{ content('blog.filter_category_all') }}</span>
        @endif
        <span class="list-select-caret" aria-hidden="true">▾</span>
    </summary>

    <div class="list-select-panel">
        <a @class(['list-option', 'is-on' => $selected === null])
           href="{{ route('blog.index', array_filter(['author' => $author])) }}">
            <span class="list-option-all" aria-hidden="true">✳</span>
            <span>
                <strong>{{ content('blog.filter_category_all') }}</strong>
                <em>{{ count($categories) }} {{ content('blog.filter_category_count') }}</em>
            </span>
        </a>

        @foreach ($categories as $key => $meta)
            <a @class(['list-option', 'is-on' => $selected === $key])
               href="{{ route('blog.index', array_filter(['category' => $key, 'author' => $author])) }}">
                <span class="cat-dot {{ $meta['color'] }}" aria-hidden="true"></span>
                <span><strong>{{ $meta['label'] }}</strong></span>
            </a>
        @endforeach
    </div>
</details>
