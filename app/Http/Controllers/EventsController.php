<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EventsController extends Controller
{
    public function index(Request $request): View
    {
        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        // The year an event HAPPENS, not the year its page was written. A
        // visitor looking for "εκδηλώσεις 2025" means the ones held in 2025,
        // and a talk given in January that was announced the previous
        // December belongs under the year it was given.
        $years = $eventModel::published()
            ->whereNotNull('starts_at')
            ->pluck('starts_at')
            ->map(fn ($d) => (int) $d->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $year = $request->integer('year') ?: null;
        if ($year !== null && ! $years->contains($year)) {
            $year = null;
        }

        $inYear = fn ($q) => $year ? $q->whereYear('starts_at', $year) : $q;

        $upcoming = $eventModel::published()->upcoming()->tap($inYear)->with('media')->get();
        $past = $eventModel::published()->past()->tap($inYear)->with('media')->get();

        return view('pages.events', compact('upcoming', 'past', 'years', 'year'));
    }

    public function show(Event $event): View
    {
        // Announced but not yet due is a draft as far as a visitor is
        // concerned, and a link to it must not resolve either.
        abort_unless(
            $event->is_published && $event->published_at !== null && ! $event->published_at->isFuture(),
            404
        );

        $event->loadMissing('media');

        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        // What else is coming up, so the page is not a dead end.
        $more = $eventModel::published()
            ->upcoming()
            ->whereKeyNot($event->getKey())
            ->with('media')
            ->take(3)
            ->get();

        return view('pages.event', compact('event', 'more'));
    }
}
