<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Article;
use App\Models\Cms\ContactMessage;
use App\Models\Cms\Event;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var class-string<Article> $articleModel */
        $articleModel = cms_model('article');
        /** @var class-string<Event> $eventModel */
        $eventModel = cms_model('event');
        /** @var class-string<ContactMessage> $messageModel */
        $messageModel = cms_model('contact_message');

        return view('admin.dashboard', [
            'publishedArticles' => $articleModel::published()->count(),
            'draftArticles' => $articleModel::where('is_published', false)->count(),
            'upcomingEvents' => $eventModel::published()->upcoming()->count(),
            'unreadMessages' => $messageModel::whereNull('read_at')->count(),
            'latestMessages' => $messageModel::latest()->take(5)->get(),
            'recentArticles' => $articleModel::with(['authors', 'categoryLinks'])->latest('updated_at')->take(5)->get(),
        ]);
    }
}
