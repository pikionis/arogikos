@props([
    'url',
    'title' => '',
])

{{-- Το μενού κοινοποίησης, στην κορυφή ενός άρθρου ή μιας εκδήλωσης.

     Είναι <details>, όπως ο επιλογέας συντάκτη: ανοίγει με το πληκτρολόγιο,
     ανακοινώνεται σωστά και δουλεύει χωρίς μία γραμμή script. Μέσα του η
     αντιγραφή είναι το πρώτο και μεγαλύτερο κουμπί, και από κάτω η ίδια η
     διεύθυνση — για να μη χρειάζεται να μαντέψει κανείς πού πατάει. --}}
<details {{ $attributes->merge(['class' => 'share-menu']) }} data-share-menu>
    <summary class="share-mini has-label" aria-label="{{ content('shared.share.button') }}{{ filled($title) ? ': '.$title : '' }}">
        <span aria-hidden="true">🔗</span>
        <span>{{ content('shared.share.button') }}</span>
    </summary>

    <div class="share-menu-panel">
        <button type="button" class="share-chip is-copy" style="width:100%;justify-content:center"
                data-share-copy-link
                data-share-url="{{ $url }}"
                data-share-copied="{{ content('shared.share.copied') }}">
            <span aria-hidden="true">🔗</span>
            <span>{{ content('shared.share.copy') }}</span>
        </button>

        <input class="share-url" type="text" readonly value="{{ $url }}"
               aria-label="{{ content('shared.share.copy') }}" data-share-field>

        <div class="share-menu-apps">
            <a class="share-chip" rel="noopener nofollow" target="_blank"
               href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}">
                <span aria-hidden="true">f</span> Facebook
            </a>
            <a class="share-chip" rel="noopener nofollow" target="_blank"
               href="https://wa.me/?text={{ urlencode(trim($title.' '.$url)) }}">
                <span aria-hidden="true">✆</span> WhatsApp
            </a>
            <a class="share-chip" rel="noopener nofollow"
               href="viber://forward?text={{ urlencode(trim($title.' '.$url)) }}">
                <span aria-hidden="true">✆</span> Viber
            </a>
            <a class="share-chip"
               href="mailto:?subject={{ rawurlencode($title) }}&body={{ rawurlencode($url) }}">
                <span aria-hidden="true">✉</span> Email
            </a>
        </div>

        {{-- Ο διάλογος της συσκευής, αν τον έχει — ως επιλογή, όχι ως έκπληξη. --}}
        <button type="button" class="share-more" data-share-native
                data-share-url="{{ $url }}" data-share-title="{{ $title }}" hidden>
            {{ content('shared.share.more') }}
        </button>
    </div>
</details>
