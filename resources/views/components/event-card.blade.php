@props([
    'event',
    'delay' => '',
    'past' => false,
    // As on an article card: the button is a sibling of the link, not inside
    // it, because a button inside an anchor is neither.
    'share' => false,
])

{{-- The whole card opens the event, the way an article card does: a card
     where only the title is clickable is a card most people never open. --}}
<div @class(['card-wrap', 'reveal', $delay])>
@if ($share)
    <x-share-button :url="route('events.show', $event)" :title="$event->title" />
@endif
<a class="event-card" href="{{ route('events.show', $event) }}"
   @if ($past) style="opacity:.92" @endif>
    <div class="event-date" @if ($past) style="background:#fff;animation:none" @endif>
        <span class="d" @if ($past) style="color:var(--ink-400)" @endif>{{ $event->day }}</span>
        <span class="m">{{ $event->month_short }}</span>
    </div>
    <div class="event-info">
        <h3>{{ $event->title }}</h3>

        <div class="meta">
            @if ($event->starts_at)
                {{-- Η ώρα φαινόταν μόνο στις επερχόμενες. Στο αρχείο μετράει
                     εξίσου: δείχνει αν ήταν πρωινό εργαστήρι ή απογευματινή
                     ομιλία, που είναι ακριβώς αυτό που ρωτά κανείς όταν
                     κοιτάζει τι έχει ξαναγίνει. --}}
                <span class="event-meta">
                    <span aria-hidden="true">🕒</span>
                    <span class="sr">Ώρα:</span>
                    {{ $event->starts_at->format('H:i') }}
                </span>

                @if ($past)
                    {{-- Το σήμα δείχνει μόνο μέρα και μήνα, οπότε μια
                         εκδήλωση του 2025 μοιάζει φετινή. --}}
                    <span class="event-meta">
                        <span aria-hidden="true">📅</span>
                        <span class="sr">Χρονιά:</span>
                        {{ $event->starts_at->format('Y') }}
                    </span>
                @endif
            @endif

            @if (filled($event->location))
                <span class="event-meta">
                    <span aria-hidden="true">📍</span>
                    <span class="sr">Τοποθεσία:</span>
                    {{ $event->location }}
                </span>
            @endif

            @if (($photos = $event->getMedia('photos')->count()) > 0)
                <span class="event-meta">
                    <span aria-hidden="true">📷</span>
                    {{ $photos }} {{ $photos === 1 ? 'φωτογραφία' : 'φωτογραφίες' }}
                </span>
            @endif
        </div>

        {{-- Η περιγραφή γράφεται πλέον με μορφοποίηση, οπότε εδώ μπαίνει μόνο
             η αρχή της σε σκέτο κείμενο — ολόκληρη είναι στη σελίδα της. --}}
        @if (filled($summary = $event->summary()))
            <p>{{ $summary }}</p>
        @endif

        <span class="event-more">{{ content('events.card_link') }} <span aria-hidden="true">→</span></span>
    </div>
</a>
</div>
