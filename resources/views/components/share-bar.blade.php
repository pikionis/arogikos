@props([
    // The address somebody should end up at. Absolute: a link posted to a
    // chat has to work from anywhere, and a relative one would not.
    'url',
    'title' => '',
])

{{-- Κοινοποίηση.

     Στο κινητό ανοίγει ο κανονικός διάλογος του τηλεφώνου με ένα πάτημα —
     εκεί είναι ήδη μέσα το Messenger, το Viber και ό,τι άλλο έχει ο καθένας.
     Στον υπολογιστή δεν υπάρχει τέτοιος διάλογος, οπότε μένουν τα κουμπιά.

     Ο σύνδεσμος είναι η διεύθυνση της ίδιας της σελίδας, οπότε όποιος τον
     πατήσει έρχεται κατευθείαν εδώ — όχι σε μια λίστα να το ψάχνει. --}}
<section class="section-sm bg-white share-band">
    <div class="container">
        <div class="share-bar reveal"
             data-share
             data-share-url="{{ $url }}"
             data-share-title="{{ $title }}"
             data-share-copy="{{ content('shared.share.copy') }}"
             data-share-copied="{{ content('shared.share.copied') }}">

            <p class="share-title">{{ content('shared.share.title') }}</p>

            <div class="share-actions">
                {{-- Μόνο όπου υπάρχει ο διάλογος του συστήματος· η app.js το
                     αποκαλύπτει και κρύβει τα υπόλοιπα. --}}
                <button type="button" class="btn btn-primary btn-sm share-native" data-share-native hidden>
                    {{ content('shared.share.button') }}
                </button>

                <button type="button" class="share-chip is-copy" data-share-copy-button>
                    <span aria-hidden="true">🔗</span>
                    <span data-share-copy-label>{{ content('shared.share.copy') }}</span>
                </button>

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

            {{-- Η ίδια η διεύθυνση, για όποιον προτιμά να τη διαλέξει με το
                 χέρι — και για όταν το πρόχειρο του browser δεν παίζει. --}}
            <input class="share-url" type="text" readonly value="{{ $url }}"
                   aria-label="{{ content('shared.share.copy') }}" data-share-field>
        </div>
    </div>
</section>
