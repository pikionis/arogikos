<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Η διάταξη της σελίδας ενός άρθρου
|--------------------------------------------------------------------------
| Η σελίδα στοίβαζε τέσσερα διαφορετικά πλάτη — 820, 960, 720, 820 — οπότε
| καμία στήλη δεν έπεφτε κάτω από την προηγούμενη και το δεξί περιθώριο
| άλλαζε τρεις φορές καθώς κατέβαινες. Τώρα υπάρχει ένα πλάτος.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function articleWithAuthor(string $bio): Article
{
    $author = Author::factory()->create([
        'name' => 'Μαρία Παπαδοπούλου',
        'role_title' => 'Ειδική Παιδαγωγός',
        'bio' => $bio,
        'is_active' => true,
    ]);

    return Article::factory()->create([
        'author_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
        'body' => '<p>Το κείμενο του άρθρου.</p>',
    ]);
}

/* ------------------------------------------------------------ one measure */

it('gives every block on an article the same column', function () {
    $article = articleWithAuthor(str_repeat('Κείμενο βιογραφικού. ', 20));

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    // Header, cover, body and author card: four blocks, one class.
    expect(substr_count($html, 'class="container column"'))->toBe(4);
});

it('no longer hard-codes a width on any article block', function () {
    $article = articleWithAuthor('Σύντομο.');

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    // The three widths the page used to carry inline.
    foreach (['max-width:820px', 'max-width:960px', 'max-width:720px'] as $width) {
        expect($html)->not->toContain($width);
    }
});

it('keeps the column defined in one place', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    expect($css)->toContain('--measure:')
        ->and($css)->toContain('.container.column')
        // The body must fill its column rather than open a narrower second one.
        ->and($css)->toContain('.article-body { max-width: none; }');
});

/* ---------------------------------------------------------- author card */

it('shows the author photo, name and role at the end of the article', function () {
    $article = articleWithAuthor(str_repeat('Κείμενο βιογραφικού. ', 20));

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('author-card-photo', false)
        ->assertSeeText('Μαρία Παπαδοπούλου')
        ->assertSeeText('Ειδική Παιδαγωγός')
        ->assertSeeText('Το άρθρο έγραψε');
});

it('puts a long bio in a box you can open, exactly as the team page does', function () {
    $article = articleWithAuthor(str_repeat('Κείμενο βιογραφικού. ', 20));

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    expect($html)->toContain('profile-bio')
        ->and($html)->toContain('is-clamped')
        ->and($html)->toContain('data-profile-open="author-'.$article->author_id.'"')
        ->and($html)->toContain('<dialog id="author-'.$article->author_id.'"')
        ->and($html)->toContain('profile-modal-photo');
});

it('carries the whole bio into the dialog', function () {
    $bio = str_repeat('Κείμενο βιογραφικού. ', 20).'ΤΕΛΕΥΤΑΙΑ ΠΡΟΤΑΣΗ.';
    $article = articleWithAuthor($bio);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('ΤΕΛΕΥΤΑΙΑ ΠΡΟΤΑΣΗ.');
});

it('offers nothing to open when the bio already fits', function () {
    $article = articleWithAuthor('Ειδική παιδαγωγός στην Κω.');

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    expect($html)->toContain('profile-bio')
        ->and($html)->not->toContain('is-clamped')
        ->and($html)->not->toContain('<dialog');
});

it('leaves the card out entirely when there is no bio', function () {
    $article = articleWithAuthor('');

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertDontSee('author-card', false);
});

it('opens the author the same way on both pages', function () {
    // One component, two callers: the same person must not be presented in
    // two different ways depending on which page you found them on.
    $article = articleWithAuthor(str_repeat('Κείμενο βιογραφικού. ', 20));

    $onArticle = $this->get(route('blog.show', $article->slug))->getContent();
    $onAbout = $this->get(route('about'))->getContent();

    foreach (['profile-bio', 'profile-more', 'profile-modal-panel', 'aria-haspopup="dialog"'] as $shared) {
        expect($onArticle)->toContain($shared)
            ->and($onAbout)->toContain($shared);
    }
});

/* ------------------------------------------------------- language switch */

it('no longer offers a language nobody can choose', function () {
    // English was never built; a ΕΛ · EN control that does nothing is worse
    // than no control at all.
    foreach (['home', 'about', 'services', 'blog.index', 'contact'] as $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        expect($html)->not->toContain('lang-switch')
            ->and($html)->not->toContain('ΕΛ');
    }
});

it('drops the stylesheet rules that dressed it', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    expect($css)->not->toContain('lang-switch');
});
