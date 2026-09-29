<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Event;
use App\Models\Cms\Setting;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Public page rendering + SEO / sitemap / analytics
|--------------------------------------------------------------------------
| Every public route must respond 200 with the branded ΑΡΩΓΗ layout. These
| tests also cover the home/blog content surfaces, the branded 404, the XML
| sitemap, the no-Google-Fonts guarantee and the conditional GA snippet.
*/

/* ------------------------------------------------------------ smoke: 200s */

it('returns 200 for every static public route', function (string $routeName) {
    // Seed the full demo dataset so data-driven pages (home, blog, about,
    // events, gallery) have real content to render.
    $this->seed(DatabaseSeeder::class);

    $this->get(route($routeName))->assertOk();
})->with([
    'home' => 'home',
    'about' => 'about',
    'services' => 'services',
    'blog.index' => 'blog.index',
    'events' => 'events',
    'gallery' => 'gallery',
    'contact' => 'contact',
]);

it('returns 200 for the empty (un-seeded) home, blog and contact pages', function () {
    // Settings are needed so the layout/header/footer resolve org details.
    $this->seed(SettingsSeeder::class);

    $this->get(route('home'))->assertOk();
    $this->get(route('blog.index'))->assertOk();
    $this->get(route('contact'))->assertOk();
});

/* ----------------------------------------------------------------- home */

it('shows the latest published article title and an upcoming event on home', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();

    $latest = Article::factory()->by($author)->create([
        'title' => 'Το πιο πρόσφατο άρθρο μας',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $event = Event::factory()->create([
        'title' => 'Επερχόμενη εκδήλωση Αρωγής',
        'is_published' => true,
        'starts_at' => now()->addWeek(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText($latest->title)
        ->assertSeeText($event->title);
});

/* ----------------------------------------------------------- blog index */

it('lists published articles on the blog index', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();
    $article = Article::factory()->by($author)->create([
        'title' => 'Δημοσιευμένο άρθρο στο blog',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeText($article->title);
});

it('does not list draft / unpublished articles on the blog index', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();
    $draft = Article::factory()->by($author)->draft()->create([
        'title' => 'Κρυφό προσχέδιο άρθρου',
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertDontSeeText($draft->title);
});

it('filters the blog index by ?category=learning and hides other categories', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();

    $learning = Article::factory()->by($author)->create([
        'title' => 'Άρθρο για μαθησιακές δυσκολίες',
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $counseling = Article::factory()->by($author)->create([
        'title' => 'Άρθρο για συμβουλευτική γονέων',
        'category' => 'counseling',
        'is_published' => true,
        'published_at' => now()->subDays(2),
    ]);

    $this->get(route('blog.index', ['category' => 'learning']))
        ->assertOk()
        ->assertSeeText($learning->title)
        ->assertDontSeeText($counseling->title);
});

/* ------------------------------------------------------------ blog show */

it('returns 200 for a published article slug', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();
    $article = Article::factory()->by($author)->create([
        'title' => 'Ένα άρθρο που διαβάζεται',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText($article->title);
});

it('returns 404 for an unknown article slug', function () {
    $this->seed(SettingsSeeder::class);

    $this->get(route('blog.show', 'den-yparxei-auto-to-arthro'))
        ->assertNotFound();
});

it('returns 404 for a draft (unpublished) article slug', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();
    $draft = Article::factory()->by($author)->draft()->create([
        'title' => 'Προσχέδιο που δεν πρέπει να φαίνεται',
    ]);

    $this->get(route('blog.show', $draft->slug))
        ->assertNotFound();
});

/* ---------------------------------------------------------------- about */

it('shows an active author name on the about page', function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PageSeeder::class);

    $author = Author::factory()->create([
        'name' => 'Μαρία Κωνσταντίνου',
        'is_active' => true,
    ]);

    // An inactive author must NOT appear on the team grid.
    $hidden = Author::factory()->inactive()->create([
        'name' => 'Ανενεργός Συνεργάτης',
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSeeText($author->name)
        ->assertDontSeeText($hidden->name);
});

/* -------------------------------------------------------------- sitemap */

it('renders the sitemap as text/xml containing a blog article URL', function () {
    $this->seed(SettingsSeeder::class);

    $author = Author::factory()->create();
    $article = Article::factory()->by($author)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/xml');

    $response->assertSee(route('blog.show', $article->slug), false);
});

/* -------------------------------------------------- no google fonts, ever */

it('never references Google Fonts in the rendered HTML', function (string $routeName) {
    $this->seed(DatabaseSeeder::class);

    $this->get(route($routeName))
        ->assertOk()
        ->assertDontSee('fonts.googleapis', false)
        ->assertDontSee('fonts.gstatic', false);
})->with([
    'home' => 'home',
    'about' => 'about',
    'blog.index' => 'blog.index',
    'contact' => 'contact',
]);

/* ----------------------------------------------------- google analytics */

it('omits the GA snippet when ga_id is empty', function () {
    // SettingsSeeder stores ga_id as an empty string by default.
    $this->seed(SettingsSeeder::class);

    expect(setting('ga_id'))->toBeNull();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager', false);
});

it('renders the GA snippet once ga_id is set', function () {
    $this->seed(SettingsSeeder::class);

    // Update the setting and bump the cached settings array.
    Setting::updateOrCreate(['key' => 'ga_id'], ['value' => 'G-TEST123']);
    Settings::flush();

    expect(setting('ga_id'))->toBe('G-TEST123');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager', false)
        ->assertSee('G-TEST123', false);
});

/* ---------------------------------------------------------- branded 404 */

it('serves the branded 404 view with a 404 status', function () {
    $this->seed(SettingsSeeder::class);

    $this->get('/auti-i-diadromi-den-yparxei-pote')
        ->assertNotFound()
        ->assertSeeText('Αυτή η σελίδα δεν βρέθηκε');
});
