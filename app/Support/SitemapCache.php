<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidation for the rendered sitemap XML.
 *
 * {@see SitemapController} caches the whole document for
 * a day. Content models bust it on write (see each model's booted()), the same
 * way {@see Settings} is busted by the Setting model.
 *
 * Caveat: Eloquent model events do NOT fire on mass updates, so any bulk
 * action in the admin (e.g. Article::whereIn(...)->update(...)) must call
 * flush() itself.
 */
class SitemapCache
{
    public const CACHE_KEY = 'cms.sitemap';

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
