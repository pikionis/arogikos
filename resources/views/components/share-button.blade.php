@props([
    'url',
    'title' => '',
])

{{-- Ένα πάτημα, μία δουλειά: αντιγράφει τον σύνδεσμο και το λέει.

     Δεν ανοίγει τον διάλογο του συστήματος. Στον υπολογιστή εκείνος είναι ένα
     παράθυρο των Windows που δεν δείχνει πουθενά «αντιγραφή», και ο κόσμος
     έμενε να ψάχνει τι να πατήσει. Όποιος θέλει τις εφαρμογές του, τις
     βρίσκει στο μενού πάνω από το άρθρο και στη μπάρα στο τέλος του. --}}
<button type="button"
        {{ $attributes->merge(['class' => 'share-mini']) }}
        data-share-copy-link
        data-share-url="{{ $url }}"
        data-share-copied="{{ content('shared.share.copied') }}"
        aria-label="{{ content('shared.share.copy') }}{{ filled($title) ? ': '.$title : '' }}">
    <span aria-hidden="true">🔗</span>
</button>
