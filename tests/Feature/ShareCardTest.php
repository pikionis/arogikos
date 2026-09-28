<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\ContentBlock;
use App\Support\Brand\Woff;
use App\Support\ShareImage;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| How a link looks when somebody shares it
|--------------------------------------------------------------------------
| Every page used to go out with no og:image at all, because the share image
| had never been uploaded and nothing stood behind it. Facebook, WhatsApp and
| LinkedIn render that as a grey box with a line of text — for a centre whose
| pages get passed between parents, that is the first impression.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function metaContent(string $html, string $property, string $attr = 'property'): ?string
{
    $pattern = '/<meta[^>]*'.$attr.'="'.preg_quote($property, '/').'"[^>]*content="([^"]*)"/';

    return preg_match($pattern, $html, $m) ? html_entity_decode($m[1]) : null;
}

/* ------------------------------------------------------------- every page */

it('never publishes a page without a share image', function (string $route) {
    $html = $this->get(route($route))->assertOk()->getContent();

    $image = metaContent($html, 'og:image');

    expect($image)->not->toBeNull()
        ->and($image)->toStartWith('http');
})->with(['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact']);

it('asks for the large card rather than the small one', function () {
    $html = $this->get(route('home'))->getContent();

    expect(metaContent($html, 'twitter:card', 'name'))->toBe('summary_large_image');
});

it('describes the image for people who cannot see it', function () {
    $html = $this->get(route('home'))->getContent();

    expect(metaContent($html, 'og:image:alt'))->toContain('Αρωγή');
});

/* ------------------------------------------------------------- the ladder */

it('falls back to the brand card when nothing has been uploaded', function () {
    $share = ShareImage::resolve();

    expect($share['url'])->toEndWith(ShareImage::DEFAULT_PATH)
        ->and($share['width'])->toBe(1200)
        ->and($share['height'])->toBe(630);
});

it('gives the crawler the dimensions of the card it ships', function () {
    $html = $this->get(route('home'))->getContent();

    expect(metaContent($html, 'og:image:width'))->toBe('1200')
        ->and(metaContent($html, 'og:image:height'))->toBe('630');
});

it('prefers the image the client uploaded', function () {
    Storage::fake('public');

    $block = ContentBlock::create([
        'page_key' => 'shared',
        'field_key' => 'share_image',
        'locale' => 'el',
        'value' => null,
    ]);
    $block->addMedia(UploadedFile::fake()->image('centre.jpg', 1200, 630))->toMediaCollection('image');

    $share = ShareImage::resolve();

    expect($share['url'])->not->toEndWith(ShareImage::DEFAULT_PATH)
        // Dimensions are unknown for an upload, so they must not be guessed.
        ->and($share['width'])->toBeNull();
});

it('lets a page name its own image above both', function () {
    expect(ShareImage::resolve('https://example.test/cover.jpg')['url'])
        ->toBe('https://example.test/cover.jpg');
});

/* --------------------------------------------------------------- articles */

it('shares an article with its own cover and title', function () {
    Storage::fake('public');

    $author = Author::factory()->create(['name' => 'Μαρία Παπαδοπούλου']);
    $article = Article::factory()->create([
        'title' => 'Επτά σημάδια δυσλεξίας',
        'author_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $article->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 800))->toMediaCollection('cover');

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    expect(metaContent($html, 'og:image'))->toContain('cover')
        ->and(metaContent($html, 'og:image:alt'))->toBe('Επτά σημάδια δυσλεξίας')
        ->and(metaContent($html, 'og:type'))->toBe('article');
});

it('dates and attributes a shared article', function () {
    $author = Author::factory()->create(['name' => 'Μαρία Παπαδοπούλου']);
    $article = Article::factory()->create([
        'author_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDays(3),
    ]);

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    expect(metaContent($html, 'article:published_time'))
        ->toBe($article->published_at->toIso8601String())
        ->and(metaContent($html, 'article:author'))->toBe('Μαρία Παπαδοπούλου')
        ->and(metaContent($html, 'article:section'))->not->toBeNull();
});

it('still shares a coverless article with the brand card', function () {
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    expect(metaContent($html, 'og:image'))->toEndWith(ShareImage::DEFAULT_PATH);
});

/* ---------------------------------------------------------- the artwork */

it('ships the generated card at the size platforms crop to', function () {
    $path = public_path(ShareImage::DEFAULT_PATH);

    expect($path)->toBeFile();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe(1200)->and($height)->toBe(630);
});

it('ships a favicon that is not empty', function () {
    // It was a zero-byte file, which renders as a blank tab.
    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(500)
        ->and(public_path('favicon.svg'))->toBeFile()
        ->and(public_path('apple-touch-icon.png'))->toBeFile();
});

it('points at the favicons from every page', function () {
    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('favicon.svg')
        ->and($html)->toContain('apple-touch-icon.png');
});

it('draws the mark from the same geometry as the logo component', function () {
    // If the component is redrawn, the generated icon has to follow — this
    // catches the two drifting apart.
    $svg = file_get_contents(resource_path('views/components/logo-mark.blade.php'));
    $icon = file_get_contents(public_path('favicon.svg'));

    foreach (['cx="50" cy="27" r="11.5"', 'M31 76 A19 19 0 0 1 69 76', '#1E9E58'] as $shape) {
        expect($svg)->toContain($shape)
            ->and($icon)->toContain($shape);
    }
});

/* ------------------------------------------------------------------ fonts */

it('unpacks a woff into a font GD can draw with', function () {
    $woff = base_path('node_modules/@fontsource/comfortaa/files/comfortaa-greek-700-normal.woff');

    if (! is_file($woff)) {
        $this->markTestSkipped('node_modules is not installed.');
    }

    Woff::toTtf($woff, $ttf = tempnam(sys_get_temp_dir(), 'ttf'));

    $box = imagettfbbox(64, 0, $ttf, 'ΑΡΩΓΗ');

    expect($box)->toBeArray()
        // A real Greek rendering, not a row of .notdef boxes collapsing to nil.
        ->and($box[2] - $box[0])->toBeGreaterThan(100);

    @unlink($ttf);
});

it('refuses something that is not a woff', function () {
    expect(fn () => Woff::convert('definitely not a font'))
        ->toThrow(RuntimeException::class);
});

/* ------------------------------------------------- the article's own scene */

it('shares an article with a raster copy of the scene its author chose', function () {
    $article = Article::factory()->create([
        'cover_preset' => 'september',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    // Not the SVG the page shows: no platform reads one.
    expect(metaContent($html, 'og:image'))->toEndWith('images/covers/september.png')
        ->and(metaContent($html, 'og:image'))->not->toContain('.svg');
});

it('ships a PNG for every scene an author can pick', function () {
    foreach (array_keys(config('cms-covers')) as $key) {
        $path = public_path("images/covers/$key.png");

        expect($path)->toBeFile();

        [$width, $height] = getimagesize($path);

        expect($width)->toBe(1200)->and($height)->toBe(630);
    }
});

it('falls back to the brand card when the scene has no picture yet', function () {
    // A scene added to the registry but not yet drawn must not produce a
    // link to a file that is not there.
    $article = Article::factory()->create([
        'cover_preset' => 'september',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $renamed = public_path('images/covers/september.png');
    $hidden = $renamed.'.hidden';
    rename($renamed, $hidden);

    try {
        $html = $this->get(route('blog.show', $article->slug))->getContent();

        expect(metaContent($html, 'og:image'))->toEndWith(ShareImage::DEFAULT_PATH);
    } finally {
        rename($hidden, $renamed);
    }
});
