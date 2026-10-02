<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cms\Article;
use App\Models\Cms\Event;
use App\Support\SitemapCache;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember(SitemapCache::CACHE_KEY, now()->addDay(), function (): string {
            $sitemap = Sitemap::create();

            // Static pages. The site serves a fixed set of routes; the CMS
            // edits their content but never creates new ones.
            // The front page is the one a search for the centre by name
            // should land on; everything else said it was just as important.
            $sitemap->add(Url::create(route('home'))->setPriority(1.0));

            foreach (['about', 'services', 'blog.index', 'events', 'gallery', 'contact'] as $name) {
                $sitemap->add(Url::create(route($name))->setPriority(0.8));
            }

            /** @var class-string<Article> $articleModel */
            $articleModel = cms_model('article');
            $articleModel::published()->latest('published_at')->get()
                ->each(fn (Article $a) => $sitemap->add(
                    Url::create(route('blog.show', $a->slug))
                        ->setLastModificationDate($a->updated_at)
                        ->setPriority(0.6)
                ));

            // Events have pages of their own now, and an event with photos
            // from the day is exactly the kind of page somebody searches for
            // months later.
            /** @var class-string<Event> $eventModel */
            $eventModel = cms_model('event');
            $eventModel::published()->whereNotNull('slug')->get()
                ->each(fn (Event $e) => $sitemap->add(
                    Url::create(route('events.show', $e))
                        ->setLastModificationDate($e->updated_at)
                        ->setPriority(0.6)
                ));

            // NOTE: this used to also emit every published `pages` row at
            // url($p->slug). There is no catch-all route, so any page beyond
            // the two the named routes already cover could only ever be a 404
            // in the sitemap. Dropped.

            return $sitemap->render();
        });

        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
