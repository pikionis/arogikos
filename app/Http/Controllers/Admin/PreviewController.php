<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Article;
use App\Models\Cms\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * See a draft the way a visitor will, before anyone else can.
 *
 * It renders the real public templates rather than a facsimile of them. A
 * preview that is built from its own markup only tells you how the preview
 * looks, and the one time it matters — the time the article page does
 * something unexpected with a very long title or a missing author — is
 * exactly the time it would lie.
 *
 * Sits behind the panel's auth middleware, so a draft never leaves the staff
 * who can already see it in the editor.
 */
class PreviewController extends Controller
{
    public function article(Article $article): View
    {
        $this->authorize('manage-content');

        $article->loadMissing(['authors', 'categoryLinks', 'media']);

        return view('pages.blog.show', [
            'article' => $article,
            // Related articles are the published ones; a draft has no business
            // recommending other drafts.
            'related' => $article->related(3),
            'preview' => $this->banner($article->is_published, $article->published_at?->isFuture() ?? false),
        ]);
    }

    public function event(Request $request, Event $event): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        $event->loadMissing('media');

        // Events have no page of their own — they are cards on one list. So
        // the preview is that list with this draft dropped into whichever
        // band it will land in once published.
        $isPast = $event->starts_at !== null && $event->starts_at->lt(now()->startOfDay());

        $upcoming = $eventModel::published()->upcoming()->with('media')->get();
        $past = $eventModel::published()->past()->with('media')->get();

        if ($isPast) {
            $past = $past->prepend($event)->sortByDesc('starts_at')->values();
        } else {
            $upcoming = $upcoming->push($event)->sortBy('starts_at')->values();
        }

        $years = $upcoming->concat($past)
            ->pluck('starts_at')
            ->filter()
            ->map(fn ($d) => (int) $d->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        return view('pages.events', [
            'upcoming' => $upcoming,
            'past' => $past,
            'years' => $years,
            'year' => null,
            'preview' => $this->banner($event->is_published, $event->published_at?->isFuture() ?? false),
        ]);
    }

    /**
     * What the bar at the top of a preview says.
     *
     * The distinction matters: "not published" is something to go and fix,
     * while "published, but dated ahead" is already correct and simply has
     * not arrived yet.
     *
     * @return array{state: string, note: string}
     */
    private function banner(bool $isPublished, bool $isFuture): array
    {
        if (! $isPublished) {
            return [
                'state' => 'draft',
                'note' => 'Πρόχειρο — δεν είναι ορατό σε κανέναν επισκέπτη.',
            ];
        }

        if ($isFuture) {
            return [
                'state' => 'scheduled',
                'note' => 'Δημοσιευμένο με μελλοντική ημερομηνία — θα εμφανιστεί μόνο του.',
            ];
        }

        return [
            'state' => 'live',
            'note' => 'Ήδη δημοσιευμένο — αυτό βλέπει και ο κόσμος.',
        ];
    }
}
