@props([
    'event',
    'delay' => '',
    'past' => false,
])

<article class="event-card reveal {{ $delay }}" @if ($past) style="opacity:.92" @endif>
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
        </div>

        @if (filled($event->body))
            <p>{{ $event->body }}</p>
        @endif
    </div>
</article>
