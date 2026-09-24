<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Event;
use Illuminate\Contracts\View\View;

class EventsController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        $upcoming = $eventModel::published()->upcoming()->with('media')->get();
        $past = $eventModel::published()->past()->with('media')->get();

        return view('pages.events', compact('upcoming', 'past'));
    }
}
