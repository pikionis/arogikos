<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Blog administration
|--------------------------------------------------------------------------
| The brief's Definition of Done: "Νέο άρθρο στο /admin εμφανίζεται αμέσως
| στο /blog με σωστό συγγραφέα."
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function staff(Role $role = Role::Editor): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

/* ------------------------------------------------------------------ access */

it('lets an editor reach the article list', function () {
    $this->actingAs(staff())
        ->get(route('admin.articles.index'))
        ->assertOk();
});

it('redirects a guest away from the article list', function () {
    $this->get(route('admin.articles.index'))->assertRedirect(route('admin.login'));
});

/* ------------------------------------------------------------------ create */

it('creates an article and shows it on the public blog with its author', function () {
    $author = Author::factory()->create(['name' => 'Μαρία Κωνσταντίνου']);

    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Πώς στηρίζουμε τη μελέτη στο σπίτι',
        'excerpt' => 'Πρακτικές ιδέες για γονείς.',
        'body' => '<p>Το κυρίως κείμενο.</p>',
        'author_id' => $author->id,
        'category' => 'parents',
        'is_published' => '1',
    ])->assertRedirect();

    $article = Article::firstOrFail();

    expect($article->title)->toBe('Πώς στηρίζουμε τη μελέτη στο σπίτι')
        ->and($article->author_id)->toBe($author->id)
        ->and($article->is_published)->toBeTrue()
        // Publishing without a date would leave it invisible: the published()
        // scope requires published_at to be set and in the past.
        ->and($article->published_at)->not->toBeNull();

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeText('Πώς στηρίζουμε τη μελέτη στο σπίτι');

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Μαρία Κωνσταντίνου');
});

it('generates a Greek slug from the title', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Δυσλεξία: 7 σημάδια',
        'is_published' => '0',
    ]);

    expect(Article::firstOrFail()->slug)->not->toBeEmpty();
});

it('keeps a draft off the public blog', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Κρυφό προσχέδιο',
        'is_published' => '0',
    ]);

    $this->get(route('blog.index'))->assertOk()->assertDontSeeText('Κρυφό προσχέδιο');
});

it('keeps a future-dated article off the public blog', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Προγραμματισμένο άρθρο',
        'is_published' => '1',
        'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
    ]);

    $this->get(route('blog.index'))->assertOk()->assertDontSeeText('Προγραμματισμένο άρθρο');
});

it('rejects an article with no title', function () {
    $this->actingAs(staff())
        ->post(route('admin.articles.store'), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(Article::count())->toBe(0);
});

it('rejects a duplicate slug', function () {
    $existing = Article::factory()->create(['slug' => 'yparxei-idi']);

    $this->actingAs(staff())
        ->post(route('admin.articles.store'), [
            'title' => 'Άλλος τίτλος',
            'slug' => 'yparxei-idi',
        ])->assertSessionHasErrors('slug');
});

/* ----------------------------------------------------------- author picker */

it('offers panel members and standalone authors as separate optgroups', function () {
    $panelUser = staff();
    $panelAuthor = Author::create([
        'user_id' => $panelUser->getKey(),
        'name' => 'Μέλος Πάνελ',
        // Invited members start inactive so they stay off the About page —
        // they must still be offered as a byline.
        'is_active' => false,
    ]);
    $standalone = Author::factory()->create(['name' => 'Εξωτερικός Συνεργάτης']);

    $this->actingAs($panelUser)
        ->get(route('admin.articles.create'))
        ->assertOk()
        ->assertSee('Μέλη του panel')
        ->assertSee('Άλλοι συντάκτες')
        ->assertSeeText('Μέλος Πάνελ')
        ->assertSeeText('Εξωτερικός Συνεργάτης');
});

it('accepts an inactive panel author as the byline', function () {
    $panelUser = staff();
    $author = Author::create([
        'user_id' => $panelUser->getKey(),
        'name' => 'Ανενεργό Μέλος',
        'is_active' => false,
    ]);

    $this->actingAs($panelUser)->post(route('admin.articles.store'), [
        'title' => 'Άρθρο μέλους',
        'author_id' => $author->id,
        'is_published' => '1',
    ])->assertSessionHasNoErrors();

    expect(Article::firstOrFail()->author_id)->toBe($author->id);
});

/* ------------------------------------------------------------ sanitisation */

it('strips script tags from the body but keeps real formatting', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με κακόβουλο κώδικα',
        'body' => '<p>Καλό <strong>έντονο</strong></p><script>alert(1)</script>',
        'is_published' => '1',
    ]);

    $body = Article::firstOrFail()->body;

    expect($body)->toContain('<strong>έντονο</strong>')
        ->and($body)->not->toContain('<script>')
        ->and($body)->not->toContain('alert(1)');
});

it('strips inline event handlers from the body', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με onclick',
        'body' => '<p onclick="steal()">κείμενο</p>',
        'is_published' => '1',
    ]);

    expect(Article::firstOrFail()->body)->not->toContain('onclick');
});

it('strips javascript: links from the body', function () {
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με κακό link',
        'body' => '<p><a href="javascript:alert(1)">πάτα</a></p>',
        'is_published' => '1',
    ]);

    expect(Article::firstOrFail()->body)->not->toContain('javascript:');
});

/* -------------------------------------------------------------------- cover */

it('stores a cover image and generates the thumb and web conversions', function () {
    Storage::fake('public');

    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με εξώφυλλο',
        'is_published' => '1',
        'cover' => UploadedFile::fake()->image('exofyllo.jpg', 1200, 800),
    ])->assertSessionHasNoErrors();

    $article = Article::firstOrFail();
    $media = $article->getFirstMedia('cover');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('web'))->toBeTrue();
});

it('refuses a non-image upload as a cover', function () {
    Storage::fake('public');

    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με κακό αρχείο',
        'cover' => UploadedFile::fake()->create('malware.php', 40, 'application/x-php'),
    ])->assertSessionHasErrors('cover');
});

it('refuses an SVG cover', function () {
    Storage::fake('public');

    // Same-origin SVG is an XSS vector; the design uses inline SVG in Blade.
    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Άρθρο με SVG',
        'cover' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    ])->assertSessionHasErrors('cover');
});

/* -------------------------------------------------------------------- edit */

it('updates an article', function () {
    $article = Article::factory()->create(['title' => 'Παλιός τίτλος']);

    $this->actingAs(staff())->put(route('admin.articles.update', $article), [
        'title' => 'Νέος τίτλος',
        'is_published' => '1',
    ])->assertRedirect();

    expect($article->refresh()->title)->toBe('Νέος τίτλος');
});

it('keeps the slug when only the title changes', function () {
    $article = Article::factory()->create(['title' => 'Αρχικός', 'slug' => 'arxikos']);

    $this->actingAs(staff())->put(route('admin.articles.update', $article), [
        'title' => 'Διορθωμένος τίτλος',
        'slug' => 'arxikos',
        'is_published' => '1',
    ]);

    // Existing links must not break on a typo fix.
    expect($article->refresh()->slug)->toBe('arxikos');
});

it('deletes an article', function () {
    $article = Article::factory()->create();

    $this->actingAs(staff())
        ->delete(route('admin.articles.destroy', $article))
        ->assertRedirect(route('admin.articles.index'));

    expect(Article::count())->toBe(0);
});

/* ------------------------------------------------------------ sitemap cache */

it('busts the cached sitemap when an article is published', function () {
    // Without this the new article would be missing from sitemap.xml for 24h.
    $this->get(route('sitemap'))->assertOk();
    expect(Cache::has('cms.sitemap'))->toBeTrue();

    $this->actingAs(staff())->post(route('admin.articles.store'), [
        'title' => 'Φρέσκο άρθρο',
        'is_published' => '1',
    ]);

    expect(Cache::has('cms.sitemap'))->toBeFalse();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('blog.show', Article::firstOrFail()->slug), false);
});
