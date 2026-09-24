<?php

declare(strict_types=1);

use App\Models\Cms\Album;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Category;
use App\Models\Cms\ContactMessage;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Event;
use App\Models\Cms\Page;
use App\Models\Cms\Photo;
use App\Models\Cms\Setting;

/*
|--------------------------------------------------------------------------
| CMS model map  (swap indirection)
|--------------------------------------------------------------------------
| Controllers resolve content models through cms_model('article') instead of
| referencing a class directly. Today these point at the app's temporary
| local models. When pikionis/cms-core is wired in, repoint each entry to
| Pikionis\Cms\Models\* and delete the local models + migrations — no
| controller, route, or view changes required.
*/

return [
    'page' => Page::class,
    'article' => Article::class,
    'author' => Author::class,
    'event' => Event::class,
    'album' => Album::class,
    'category' => Category::class,
    'photo' => Photo::class,
    'contact_message' => ContactMessage::class,
    'content_block' => ContentBlock::class,
    'setting' => Setting::class,
];
