@props([
    'url',
    'title' => '',
    // With its name beside it, or just the mark. On a card the mark is
    // enough; at the top of an article the word is what makes it findable.
    'label' => false,
])

{{-- Ένα πάτημα: στο κινητό ανοίγει ο διάλογος του τηλεφώνου, αλλού
     αντιγράφεται ο σύνδεσμος και το λέει. --}}
<button type="button"
        {{ $attributes->merge(['class' => 'share-mini'.($label ? ' has-label' : '')]) }}
        data-share-mini
        data-share-url="{{ $url }}"
        data-share-title="{{ $title }}"
        data-share-copied="{{ content('shared.share.copied') }}"
        aria-label="{{ content('shared.share.button') }}{{ filled($title) ? ': '.$title : '' }}">
    <span aria-hidden="true">🔗</span>
    @if ($label)
        <span>{{ content('shared.share.button') }}</span>
    @endif
</button>
