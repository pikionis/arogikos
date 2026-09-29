<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Article;
use App\Models\Cms\Event;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');
        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');

        $articles = $articleModel::published()
            ->with(['authors', 'media'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $events = $eventModel::published()
            ->upcoming()
            ->with('media')
            ->take(2)
            ->get();

        return view('pages.home', compact('articles', 'events'));
    }
}
