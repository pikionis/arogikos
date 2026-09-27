<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Έτοιμα εξώφυλλα
|--------------------------------------------------------------------------
| Εννιά άρθρα, κανένα εξώφυλλο: ο αρθρογράφος δεν έχει φωτογραφία και δεν
| πρόκειται να βρει. Οι είκοσι σκηνές είναι η απάντηση σε αυτό — διαλέγει
| μία από λίστα, με περιγραφή του πού ταιριάζει.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

/* owner() is declared once for the whole suite, in Admin/AuthTest.php. */

/* -------------------------------------------------------------- registry */

it('ships a Blade partial for every scene in the registry', function () {
    $scenes = config('cms-covers');

    expect($scenes)->toHaveCount(20);

    foreach ($scenes as $key => $scene) {
        expect(view()->exists("covers.$key"))->toBeTrue("covers.$key is missing")
            ->and($scene['name'])->not->toBe('')
            ->and($scene['for'])->not->toBe('');
    }
});

it('scopes every scene stylesheet rule so two covers cannot drive each other', function () {
    $css = file_get_contents(resource_path('css/arogi-covers.css'));

    foreach (array_keys(config('cms-covers')) as $key) {
        // Keyframes carry the scene key, and rules are nested under it.
        expect($css)->toContain(".cv-$key.is-live");
    }

    // No bare animation name survived the namespacing.
    preg_match_all('/@keyframes\s+([A-Za-z0-9_-]+)/', $css, $m);

    foreach ($m[1] as $name) {
        expect($name)->toStartWith('cv-');
    }
});

it('keeps the brand palette out of the scenes as raw hex', function () {
    foreach (array_keys(config('cms-covers')) as $key) {
        $svg = file_get_contents(resource_path("views/covers/$key.blade.php"));

        expect($svg)->not->toMatch('/fill="#(1E9E58|E26D5A|F4B62A)"/i')
            ->and($svg)->toContain('aria-hidden="true"');
    }
});

/* ------------------------------------------------------------- rendering */

it('renders the chosen scene on the article page, and lets it move', function () {
    $article = Article::factory()->create([
        'cover_preset' => 'september',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('cv-september', false)
        ->assertSee('is-live', false);
});

it('keeps the scene still in the blog list', function () {
    Article::factory()->create([
        'cover_preset' => 'september',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    expect($html)->toContain('cv-september')
        ->and($html)->not->toContain('is-live');
});

it('lets an uploaded photo beat the chosen scene', function () {
    Storage::fake('public');

    $article = Article::factory()->create([
        'cover_preset' => 'september',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $article->addMedia(UploadedFile::fake()->image('real.jpg', 1600, 1000))->toMediaCollection('cover');

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    expect($html)->not->toContain('cv-september')
        ->and($html)->toContain('real');
});

it('falls back to the category motif when no scene is chosen', function () {
    $article = Article::factory()->create([
        'cover_preset' => null,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('photo-ph', false);
});

it('ignores a scene that has been retired from the registry', function () {
    // The column keeps what was picked; only a scene that still exists renders.
    $article = Article::factory()->create([
        'cover_preset' => 'scene-that-no-longer-exists',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($article->cover_scene)->toBeNull();

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('photo-ph', false);
});

/* ----------------------------------------------------------------- panel */

it('offers every scene in the article form, with what it is for', function () {
    $this->actingAs(owner());

    $html = $this->get(route('admin.articles.create'))->assertOk()->getContent();

    foreach (config('cms-covers') as $key => $scene) {
        expect($html)->toContain('value="'.$key.'"')
            ->and($html)->toContain(e($scene['for']));
    }
});

it('saves the author\'s choice', function () {
    $this->actingAs(owner());

    $this->post(route('admin.articles.store'), [
        'title' => 'Δοκιμαστικό άρθρο',
        'body' => '<p>Κείμενο.</p>',
        'category' => 'learning',
        'cover_preset' => 'network',
        'is_published' => 1,
    ])->assertRedirect();

    expect(Article::latest('id')->first()->cover_preset)->toBe('network');
});

it('refuses a scene that is not on the list', function () {
    $this->actingAs(owner());

    $this->post(route('admin.articles.store'), [
        'title' => 'Δοκιμαστικό άρθρο',
        'body' => '<p>Κείμενο.</p>',
        'category' => 'learning',
        'cover_preset' => '../../etc/passwd',
    ])->assertSessionHasErrors('cover_preset');
});

it('lets the author take the scene back off', function () {
    $this->actingAs(owner());

    $article = Article::factory()->create(['cover_preset' => 'summer']);

    $this->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'body' => '<p>Κείμενο.</p>',
        'category' => 'learning',
        'cover_preset' => '',
    ])->assertRedirect();

    expect($article->refresh()->cover_preset)->toBeNull();
});
