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
            @if (filled($event->location))
                <span>📍 {{ $event->location }}</span>
            @endif
            @if (! $past && $event->starts_at)
                <span>🕒 {{ $event->starts_at->format('H:i') }}</span>
            @endif
        </div>
        @if (filled($event->body))
            <p>{{ $event->body }}</p>
        @endif
    </div>
</article>
