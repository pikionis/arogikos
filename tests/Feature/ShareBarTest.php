<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Event;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Κοινοποίηση
|--------------------------------------------------------------------------
| Ένα κουμπί στο τέλος κάθε άρθρου και κάθε εκδήλωσης. Στο κινητό ανοίγει ο
| διάλογος του τηλεφώνου, που έχει ήδη μέσα ό,τι εφαρμογή χρησιμοποιεί ο
| καθένας. Στον υπολογιστή μένουν τα κουμπιά και η ίδια η διεύθυνση.
|
| Ο σύνδεσμος είναι η διεύθυνση της ίδιας της σελίδας: όποιος τον πατήσει
| έρχεται κατευθείαν εκεί, όχι σε μια λίστα να το ψάχνει.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function shareable(): Article
{
    return Article::factory()->by(Author::factory()->create())->create([
        'title' => 'Δυσλεξία στην πράξη',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

function happeningSoon(): Event
{
    return Event::factory()->create([
        'title' => 'Εργαστήρι για γονείς',
        'starts_at' => now()->addWeek(),
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

/* ------------------------------------------------------------ the article */

it('offers a way to share an article', function () {
    $article = shareable();

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('data-share-url="'.route('blog.show', $article->slug).'"', false)
        ->assertSeeText(content('shared.share.title'))
        ->assertSeeText(content('shared.share.copy'));
});

it('shares the article itself, not the list it sits in', function () {
    $article = shareable();

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    preg_match('/data-share-url="([^"]+)"/', $html, $m);

    // Absolute, and straight to the piece: a link posted to a chat has to
    // open the article from anywhere.
    expect($m[1])->toBe(route('blog.show', $article->slug))
        ->and($m[1])->toStartWith('http')
        ->and($m[1])->not->toBe(route('blog.index'));
});

it('offers the same on an event', function () {
    $event = happeningSoon();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-share-url="'.route('events.show', $event).'"', false)
        ->assertSeeText(content('shared.share.copy'));
});

/* ------------------------------------------------------------- the places */

it('hands each service the address, encoded', function () {
    $article = shareable();
    $url = route('blog.show', $article->slug);

    $html = $this->get($url)->getContent();

    expect($html)->toContain('facebook.com/sharer/sharer.php?u='.urlencode($url))
        ->and($html)->toContain('wa.me/?text='.urlencode('Δυσλεξία στην πράξη '.$url))
        ->and($html)->toContain('viber://forward?text=')
        ->and($html)->toContain('mailto:?subject='.rawurlencode('Δυσλεξία στην πράξη'));
});

it('leaves the address on the page for anyone to take by hand', function () {
    $article = shareable();

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    // The clipboard needs a secure context and is not everywhere; a field
    // holding the address always works.
    expect($html)->toContain('data-share-field')
        ->and($html)->toContain('value="'.route('blog.show', $article->slug).'"')
        ->and($html)->toContain('data-share-native');
});

it('says nothing about sharing where there is nothing to share', function () {
    expect($this->get(route('blog.index'))->getContent())->not->toContain('data-share-url')
        ->and($this->get(route('home'))->getContent())->not->toContain('data-share-url');
});

/* -------------------------------------------------------------- the words */

it('lets the centre rewrite what the button says', function () {
    ContentBlock::updateOrCreate(
        ['page_key' => 'shared', 'field_key' => 'share.title', 'locale' => 'el'],
        ['value' => 'Πείτε το και σε άλλους']
    );

    $this->get(route('blog.show', shareable()->slug))
        ->assertOk()
        ->assertSeeText('Πείτε το και σε άλλους');
});

/* -------------------------------------------------------------- the prose */

it('styles a list written in the panel, not one wearing a class', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    // The editor writes plain <ul><li>, and the sanitiser strips classes —
    // so a rule that needed ul.dotted styled nothing anybody ever wrote.
    expect($css)->toContain('.article-body ul,')
        ->and($css)->toContain('.article-body ul > li::before')
        ->and($css)->toContain('.article-body ol > li::before')
        ->and($css)->not->toContain('.article-body ul.dotted');
});

it('puts a bulleted description on an event page as a list', function () {
    $event = Event::factory()->create([
        'title' => 'Με λίστα',
        'starts_at' => now()->addWeek(),
        'is_published' => true,
        'published_at' => now()->subDay(),
        'body' => '<p>Θα δούμε:</p><ul><li>Πρώτο</li><li>Δεύτερο</li></ul>',
    ]);

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    expect($html)->toContain('<ul><li>Πρώτο</li><li>Δεύτερο</li></ul>')
        // Inside the block the stylesheet actually dresses.
        ->and($html)->toContain('class="article-body reveal"');
});

/*
|--------------------------------------------------------------------------
| Και έξω, και ψηλά
|--------------------------------------------------------------------------
| Η μπάρα στο τέλος δεν φτάνει: κάποιος που περνά από τη λίστα δεν σκοπεύει
| να κατέβει ως κάτω, και κάποιος που μόλις άνοιξε το άρθρο δεν ψάχνει.
*/

it('puts a share button on every card in the list', function () {
    foreach (range(1, 3) as $i) {
        Article::factory()->by(Author::factory()->create())->create([
            'title' => "Άρθρο $i",
            'is_published' => true,
            'published_at' => now()->subDays($i),
        ]);
    }

    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    expect(substr_count($html, 'data-share-copy-link'))->toBe(3);

    foreach (Article::published()->get() as $article) {
        expect($html)->toContain('data-share-url="'.route('blog.show', $article->slug).'"');
    }
});

it('puts one on every event in the list too', function () {
    $soon = happeningSoon();
    $past = Event::factory()->create([
        'title' => 'Περασμένη',
        'starts_at' => now()->subMonth(),
        'is_published' => true,
        'published_at' => now()->subMonths(2),
    ]);

    $html = $this->get(route('events'))->assertOk()->getContent();

    expect(substr_count($html, 'data-share-copy-link'))->toBe(2)
        ->and($html)->toContain('data-share-url="'.route('events.show', $soon).'"')
        ->and($html)->toContain('data-share-url="'.route('events.show', $past).'"');
});

it('keeps the button out of the link rather than inside it', function () {
    Article::factory()->by(Author::factory()->create())->create([
        'is_published' => true, 'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('blog.index'))->getContent();

    // A <button> inside an <a> is neither one. The wrapper is what lets the
    // whole card stay a link while the button stays a button.
    expect($html)->toContain('class="card-wrap')
        ->and($html)->toMatch('/<div[^>]*class="card-wrap[^"]*"[^>]*>\s*<button[^>]*data-share-copy-link/');
});

it('offers one at the top of an article as well as the bottom', function () {
    $article = shareable();

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    $top = mb_strpos($html, 'data-share-menu');
    $bar = mb_strpos($html, 'share-bar');

    expect($top)->not->toBeFalse()
        ->and($bar)->not->toBeFalse()
        // Above the body, not beside the one at the end of it.
        ->and($top)->toBeLessThan($bar)
        ->and($top)->toBeLessThan(mb_strpos($html, 'class="article-body'));
});

it('offers one at the top of an event as well', function () {
    $event = happeningSoon();

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    expect(mb_strpos($html, 'data-share-menu'))->toBeLessThan(mb_strpos($html, 'share-bar'));
});

it('names what each button shares, for a reader who cannot see the card', function () {
    $article = shareable();

    $html = $this->get(route('blog.index'))->getContent();

    // Nine identical "share" buttons on a page are nine identical
    // announcements; the title is what tells them apart.
    // The card button has one job, and says which piece it does it for.
    expect($html)->toContain(
        'aria-label="'.content('shared.share.copy').': '.e($article->title).'"'
    );
});

it('never hands the reader off to a dialog they did not ask for', function () {
    $article = shareable();

    $html = $this->get(route('blog.show', $article->slug))->getContent();
    $js = file_get_contents(resource_path('js/app.js'));

    // The system's own share window is an option inside the menu, with its
    // name on it — not what happens when somebody presses "Κοινοποίηση".
    // On a desktop that window says nothing about copying, which is exactly
    // what sent people looking for a button that was not there.
    expect($html)->toContain('data-share-native')
        ->and($html)->toContain(e(content('shared.share.more')))
        ->and($js)->toContain('function initNativeShare()')
        ->and($js)->not->toContain('navigator.share({ title: title, url: url })');
});

it('puts copying first, and shows what will be copied', function () {
    $article = shareable();

    $html = $this->get(route('blog.show', $article->slug))->getContent();

    preg_match('/<div class="share-menu-panel">.*?<\/div>\s*<\/details>/s', $html, $panel);

    expect($panel)->not->toBeEmpty();

    $copy = mb_strpos($panel[0], content('shared.share.copy'));
    $facebook = mb_strpos($panel[0], 'facebook.com');

    expect($copy)->toBeLessThan($facebook)
        // And the address is right there, not a guess.
        ->and($panel[0])->toContain('value="'.route('blog.show', $article->slug).'"');
});

it('opens the menu without a line of script', function () {
    // <details> is keyboard-operable and announced as a disclosure on its
    // own; the script only closes it when the reader looks elsewhere.
    $html = $this->get(route('blog.show', shareable()->slug))->getContent();

    expect($html)->toContain('data-share-menu')
        ->and($html)->toMatch('/<details[^>]*class="[^"]*share-menu[^"]*"[^>]*data-share-menu>/')
        ->and($html)->toContain('<summary class="share-mini has-label"');
});
