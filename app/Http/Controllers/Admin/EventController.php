<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Cms\Event;
use App\Support\MediaUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        return view('admin.events.index', [
            'events' => $eventModel::with('media')->orderByDesc('starts_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manage-content');

        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        return view('admin.events.form', ['event' => new $eventModel]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        $event = new $eventModel;
        $this->fill($event, $request);
        $event->save();

        MediaUpload::sync($event, $request, 'cover');

        return redirect()
            ->route('admin.events.edit', $event)
            ->with('status', 'Η εκδήλωση δημιουργήθηκε.');
    }

    public function edit(Event $event): View
    {
        $this->authorize('manage-content');

        return view('admin.events.form', compact('event'));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->fill($event, $request);
        $event->save();

        MediaUpload::sync($event, $request, 'cover');

        return back()->with('status', 'Οι αλλαγές αποθηκεύτηκαν.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('manage-content');

        $title = $event->title;
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Η εκδήλωση «{$title}» διαγράφηκε.");
    }

    private function fill(Event $event, EventRequest $request): void
    {
        $data = $request->validated();

        $event->title = $data['title'];
        $event->starts_at = $data['starts_at'] ?? null;
        $event->location = $data['location'] ?? null;
        $event->body = $data['body'] ?? null;
        $event->is_published = $data['is_published'] ?? false;

        // Same rule as articles: publishing without a date would hide the
        // event, because the scope wants a date that has arrived.
        $publishAt = $data['published_at'] ?? null;
        if ($publishAt) {
            $event->published_at = $publishAt;
        } elseif ($event->is_published && ! $event->published_at) {
            $event->published_at = now();
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug !== '') {
            $event->slug = Str::slug($slug, '-', 'el');
        }
    }
}
