<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\ContentBlock;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Titles and meta descriptions
|--------------------------------------------------------------------------
| These are what a searcher actually reads in the results list, and what tells
| Google what each page is about. They were previously hard-coded, unique but
| far too short — "Blog — Αρωγή" is twelve characters and names no subject and
| no place.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function metaOf(string $html): array
{
    preg_match('/<title>(.*?)<\/title>/s', $html, $t);
    preg_match('/<meta name="description" content="(.*?)"/s', $html, $d);

    return [
        html_entity_decode(trim($t[1] ?? ''), ENT_QUOTES | ENT_HTML5),
        html_entity_decode(trim($d[1] ?? ''), ENT_QUOTES | ENT_HTML5),
    ];
}

const PAGES = ['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact'];

it('gives every page a title in the length Google displays', function (string $routeName) {
    [$title] = metaOf($this->get(route($routeName))->assertOk()->getContent());

    // Beyond ~60 characters Google truncates; below ~50 it is wasted space.
    expect(mb_strlen($title))->toBeGreaterThanOrEqual(50)->toBeLessThanOrEqual(60);
})->with(PAGES);

it('gives every page a description in the length Google displays', function (string $routeName) {
    [, $description] = metaOf($this->get(route($routeName))->assertOk()->getContent());

    expect(mb_strlen($description))->toBeGreaterThanOrEqual(150)->toBeLessThanOrEqual(160);
})->with(PAGES);

it('names the town in the title or description of every page', function (string $routeName) {
    // The centre competes locally; a page that never says "Κως" cannot rank
    // for the searches that matter.
    [$title, $description] = metaOf($this->get(route($routeName))->getContent());

    expect($title.' '.$description)->toMatch('/Κω/u');
})->with(PAGES);

it('gives every page a different title', function () {
    $titles = collect(PAGES)
        ->map(fn (string $r) => metaOf($this->get(route($r))->getContent())[0])
        ->all();

    expect($titles)->toHaveCount(count(array_unique($titles)));
});

it('lets the title be rewritten from the panel', function () {
    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'seo.title',
        'locale' => 'el',
        'value' => 'Ένας εντελώς νέος τίτλος για τη Google',
    ]);

    [$title] = metaOf($this->get(route('home'))->getContent());

    expect($title)->toBe('Ένας εντελώς νέος τίτλος για τη Google');
});

it('uses the same text for the Open Graph title, so shares match search', function () {
    $html = $this->get(route('home'))->getContent();
    [$title, $description] = metaOf($html);

    expect($html)->toContain('property="og:title" content="'.e($title).'"')
        ->and($html)->toContain('property="og:description" content="'.e($description).'"');
});

it('still lets an article carry its own title', function () {
    $article = Article::factory()
        ->for(Author::factory())
        ->create([
            'title' => 'Δυσλεξία στην πράξη',
            'seo_title' => 'Δυσλεξία: πρακτικός οδηγός για γονείς — Αρωγή Κως',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

    [$title] = metaOf($this->get(route('blog.show', $article->slug))->getContent());

    expect($title)->toBe('Δυσλεξία: πρακτικός οδηγός για γονείς — Αρωγή Κως');
});

it('offers the SEO fields for every page in the panel', function () {
    foreach (['home', 'about', 'services', 'blog', 'events', 'gallery', 'contact'] as $page) {
        $keys = collect(config("cms-content.{$page}.groups"))
            ->flatMap(fn (array $g) => collect($g['fields'])->pluck('key'))
            ->all();

        expect($keys)->toContain('seo.title', 'seo.description');
    }
});

/* --------------------------------------- an article with nothing written in */

it('still describes an article that has neither summary nor SEO text', function () {
    $article = Article::factory()->create([
        'seo_description' => null,
        'excerpt' => null,
        'body' => '<p>Η δυσλεξία δεν είναι θέμα ευφυΐας, αλλά διαφορετικός τρόπος επεξεργασίας της γραπτής γλώσσας.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    [, $description] = metaOf($this->get(route('blog.show', $article->slug))->assertOk()->getContent());

    // Falling back to the opening of the piece beats an empty tag.
    expect($description)->toContain('Η δυσλεξία δεν είναι θέμα ευφυΐας');
});

it('does not leak an output buffer while rendering that article', function () {
    // @section($name, null) makes Blade open a buffer and wait for an
    // @endsection an inline section never has. The description came out
    // empty and the buffer stayed open for the rest of the process.
    $article = Article::factory()->create([
        'seo_description' => null,
        'excerpt' => null,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $before = ob_get_level();

    $this->get(route('blog.show', $article->slug))->assertOk();

    expect(ob_get_level())->toBe($before);
});

it('keeps the description within what a results list shows', function () {
    $article = Article::factory()->create([
        'seo_description' => null,
        'excerpt' => null,
        'body' => '<p>'.str_repeat('Μια πολύ μεγάλη πρόταση που συνεχίζει. ', 30).'</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect(mb_strlen($article->metaDescription()))->toBeLessThanOrEqual(158);
});

it('prefers what the author actually wrote', function () {
    $article = Article::factory()->create([
        'seo_description' => 'Η περιγραφή για τη Google.',
        'excerpt' => 'Η περίληψη στην κάρτα.',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($article->metaDescription())->toBe('Η περιγραφή για τη Google.');

    $article->seo_description = null;

    expect($article->metaDescription())->toBe('Η περίληψη στην κάρτα.');
});
