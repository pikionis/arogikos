<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Event;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Φίλτρα: άρθρα ανά συντάκτη, εκδηλώσεις ανά χρονιά
|--------------------------------------------------------------------------
| Τα δύο φίλτρα του blog είναι ανεξάρτητα και συνδυάζονται· οι εκδηλώσεις
| φιλτράρονται με τη χρονιά που ΓΙΝΟΝΤΑΙ, όχι που γράφτηκαν.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function writer(string $name): Author
{
    return Author::factory()->create(['name' => $name, 'is_active' => true]);
}

/** Just the author picker: there are two on the page now. */
function authorPicker(string $html): string
{
    preg_match('/<details class="list-select is-authors.*?<\/details>/s', $html, $m);

    return $m[0] ?? '';
}

function postBy(Author $author, string $title, string $category = 'learning'): Article
{
    return Article::factory()->by($author)->create([
        'title' => $title,
        'category' => $category,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

/* ------------------------------------------------------- blog · συντάκτης */

it('offers a row for each author who has published', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Άρθρο του Κώστα');

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('list-select', false)
        ->assertSeeText('Μαρία Παπαδοπούλου')
        ->assertSeeText('Κώστας Δημητρίου')
        ->assertSeeText('Όλοι οι συντάκτες');
});

it('shows each author with a face and what they do', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    $maria->forceFill(['role_title' => 'Ειδική Παιδαγωγός'])->save();
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    $kostas->forceFill(['role_title' => 'Ψυχολόγος'])->save();
    postBy($kostas, 'Άρθρο του Κώστα');

    $html = authorPicker($this->get(route('blog.index'))->assertOk()->getContent());

    // A name alone tells a reader nothing; the role is how they recognise
    // the person whose articles they came back for.
    expect($html)->toContain(e('Ειδική Παιδαγωγός'))
        ->and($html)->toContain(e('Ψυχολόγος'))
        // One row per author plus the "all" row. Counted on the anchor, not
        // the bare class: list-option-all is a substring of it.
        ->and(substr_count($html, '<a class="list-option'))->toBe(3);
});

it('names the chosen author on the closed picker', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Άρθρο του Κώστα');

    $html = authorPicker($this->get(route('blog.index', ['author' => $maria->id]))->getContent());

    // Closed, it must say who is selected rather than "all authors".
    expect($html)->toContain('list-select-label');

    preg_match('/<summary>(.*?)<\/summary>/s', $html, $m);

    expect($m[1])->toContain(e('Μαρία Παπαδοπούλου'))
        ->and($m[1])->not->toContain(e('Όλοι οι συντάκτες'));
});

it('leaves out an author with nothing published', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    writer('Δεν Έγραψε Ποτέ');

    // A chip leading to an empty list is a dead end.
    $this->get(route('blog.index'))->assertOk()->assertDontSee('Δεν Έγραψε Ποτέ');
});

it('narrows the list to one author', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Άρθρο του Κώστα');

    $this->get(route('blog.index', ['author' => $maria->id]))
        ->assertOk()
        ->assertSeeText('Άρθρο της Μαρίας')
        ->assertDontSee('Άρθρο του Κώστα');
});

it('combines the author filter with the category one', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Μαθησιακό της Μαρίας', 'learning');
    postBy($maria, 'Γονεϊκό της Μαρίας', 'parents');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Μαθησιακό του Κώστα', 'learning');

    $this->get(route('blog.index', ['author' => $maria->id, 'category' => 'learning']))
        ->assertOk()
        ->assertSeeText('Μαθησιακό της Μαρίας')
        ->assertDontSee('Γονεϊκό της Μαρίας')
        ->assertDontSee('Μαθησιακό του Κώστα');
});

it('keeps the author when the reader changes category', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    postBy($maria, 'Και ένα γονεϊκό', 'parents');

    $html = $this->get(route('blog.index', ['author' => $maria->id]))->getContent();

    // Every category chip must carry the chosen author along with it.
    expect($html)->toContain('category=learning&amp;author='.$maria->id);
});

it('ignores an author id that means nothing', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');

    foreach (['999999', 'κάτι', '0', '-3'] as $bad) {
        $this->get(route('blog.index', ['author' => $bad]))
            ->assertOk()
            ->assertSeeText('Άρθρο της Μαρίας');
    }
});

it('hides the picker when there is only one writer', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');

    // Nothing to choose between, so the row would only add noise.
    $this->get(route('blog.index'))->assertOk()->assertDontSee('Όλοι οι συντάκτες');
});

/* ------------------------------------------------------- blog · κατηγορία */

/** Just the category picker, the other half of the bar. */
function categoryPicker(string $html): string
{
    preg_match('/<details class="list-select is-categories.*?<\/details>/s', $html, $m);

    return $m[0] ?? '';
}

it('offers a row for each category something is filed under', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Μαθησιακό', 'learning');
    postBy($maria, 'Γονεϊκό', 'parents');

    $html = categoryPicker($this->get(route('blog.index'))->assertOk()->getContent());

    expect($html)->toContain(e('Μαθησιακές δυσκολίες'))
        ->and($html)->toContain(e('Γονείς'))
        // Nothing is filed under counselling, so offering it would lead
        // nowhere — the same dead end the author picker avoids.
        ->and($html)->not->toContain(e('Συμβουλευτική'));
});

it('hides the picker when everything sits in one category', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο', 'learning');
    postBy($maria, 'Και ένα δεύτερο', 'learning');

    // Nothing to choose between, so the row would only add noise.
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertDontSee('is-categories', false);
});

it('narrows the list to one category', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Μαθησιακό', 'learning');
    postBy($maria, 'Γονεϊκό', 'parents');

    $this->get(route('blog.index', ['category' => 'parents']))
        ->assertOk()
        ->assertSeeText('Γονεϊκό')
        ->assertDontSee('Μαθησιακό');
});

it('lists an article under every category it is filed under', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    Article::factory()->by($maria)->inCategory('learning', 'parents')->create([
        'title' => 'Δυσλεξία και γονείς',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    // One piece, two shelves: the point of letting an article carry several.
    foreach (['learning', 'parents'] as $key) {
        $this->get(route('blog.index', ['category' => $key]))
            ->assertOk()
            ->assertSeeText('Δυσλεξία και γονείς');
    }
});

it('shows every category on the article, each one a way into the rest', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    $article = Article::factory()->by($maria)->inCategory('learning', 'parents')->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    preg_match('/<div class="cat-pills">(.*?)<\/div>/s', $html, $m);

    expect($m[1] ?? '')->toContain(e('Μαθησιακές δυσκολίες'))
        ->and($m[1])->toContain(e('Γονείς'))
        // A pill is a question — what else is there about this? — so it has
        // to answer it.
        ->and($m[1])->toContain('category=learning')
        ->and($m[1])->toContain('category=parents');
});

it('wears the first category it was given', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    $article = Article::factory()->by($maria)->inCategory('parents', 'learning')->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    // The order the boxes were ticked in decides the colour of the card and
    // the drawing a coverless article falls back to.
    expect($article->category)->toBe('parents')
        ->and($article->category_color)->toBe('yellow')
        ->and($article->categoryKeys())->toBe(['parents', 'learning']);
});

/* ------------------------------------------------- εκδηλώσεις · χρονιά */

it('offers a chip for each year that has an event', function () {
    Event::factory()->create(['is_published' => true, 'starts_at' => now()->subYears(2)]);
    Event::factory()->create(['is_published' => true, 'starts_at' => now()->subYear()]);

    $this->get(route('events'))
        ->assertOk()
        ->assertSeeText((string) now()->subYears(2)->year)
        ->assertSeeText((string) now()->subYear()->year)
        ->assertSeeText('Όλες οι χρονιές');
});

it('narrows the events to one year', function () {
    $older = Event::factory()->create(['title' => 'Παλιά εκδήλωση', 'is_published' => true, 'starts_at' => now()->subYears(2)]);
    Event::factory()->create(['title' => 'Περσινή εκδήλωση', 'is_published' => true, 'starts_at' => now()->subYear()]);

    $this->get(route('events', ['year' => $older->starts_at->year]))
        ->assertOk()
        ->assertSeeText('Παλιά εκδήλωση')
        ->assertDontSee('Περσινή εκδήλωση');
});

it('filters by the year the event happens, not the year it was written', function () {
    // Written long ago, held next year: it belongs under next year.
    $event = Event::factory()->create([
        'title' => 'Γραμμένη πέρσι, γίνεται του χρόνου',
        'is_published' => true,
        'starts_at' => now()->addYear(),
        'created_at' => now()->subYear(),
    ]);
    Event::factory()->create(['title' => 'Άλλη χρονιά', 'is_published' => true, 'starts_at' => now()->subYears(2)]);

    $this->get(route('events', ['year' => $event->starts_at->year]))
        ->assertOk()
        ->assertSeeText('Γραμμένη πέρσι, γίνεται του χρόνου')
        ->assertDontSee('Άλλη χρονιά');
});

it('ignores a year with nothing in it', function () {
    Event::factory()->create(['title' => 'Η μόνη εκδήλωση', 'is_published' => true, 'starts_at' => now()->subYear()]);

    foreach (['1999', 'πέρσι', '0'] as $bad) {
        $this->get(route('events', ['year' => $bad]))
            ->assertOk()
            ->assertSeeText('Η μόνη εκδήλωση');
    }
});

it('hides the year row when everything happened in one year', function () {
    // Pinned dates, not now()-relative ones: two events "two months apart"
    // straddle a year boundary for two months of every year, and a test that
    // passes in June and fails in December is worse than no test.
    $this->travelTo(Carbon::create(2026, 6, 15));

    Event::factory()->create(['is_published' => true, 'starts_at' => Carbon::create(2026, 3, 10)]);
    Event::factory()->create(['is_published' => true, 'starts_at' => Carbon::create(2026, 11, 20)]);

    // Nothing to choose between, so the row would only add noise.
    $this->get(route('events'))->assertOk()->assertDontSee('Όλες οι χρονιές');
});

it('never offers a year that only a draft event falls in', function () {
    // Two published years, so the row is shown at all.
    Event::factory()->create(['is_published' => true, 'starts_at' => now()->subYear()]);
    Event::factory()->create(['is_published' => true, 'starts_at' => now()->subYears(2)]);
    $draft = Event::factory()->create(['is_published' => false, 'starts_at' => now()->subYears(5)]);

    $html = $this->get(route('events'))->assertOk()->getContent();

    // The chip's link, not the bare number: four digits turn up in SVG path
    // coordinates all over this page, and a plain search would match those.
    expect($html)->not->toContain('year='.$draft->starts_at->year)
        ->and($html)->toContain('year='.now()->subYear()->year);
});

/* ------------------------------------------------ where the picker stands */

it('keeps the picker out of the hero, which clips what opens inside it', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Άρθρο του Κώστα');

    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    // .page-hero carries overflow:hidden so its decorative shapes stay put.
    // Anything that opens downward from inside it opens invisible.
    $hero = mb_substr($html, mb_strpos($html, 'page-hero'));
    $hero = mb_substr($hero, 0, mb_strpos($hero, '</section>'));

    expect($hero)->not->toContain('list-select')
        // It lives with the list it filters instead.
        ->and($html)->toContain('list-toolbar')
        ->and($html)->toContain('list-select');
});

it('puts the category filter beside the author one, not in the hero', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας', 'learning');
    postBy($maria, 'Κάτι άλλο', 'parents');

    $html = $this->get(route('blog.index'))->getContent();

    $hero = mb_substr($html, mb_strpos($html, 'page-hero'));
    $hero = mb_substr($hero, 0, mb_strpos($hero, '</section>'));

    // Both filters open a panel, and the hero clips what leaves it.
    expect($hero)->not->toContain('is-categories')
        ->and($html)->toContain('is-categories')
        // Side by side in the one bar above the list.
        ->and(mb_strpos($html, 'is-categories'))
        ->toBeGreaterThan(mb_strpos($html, 'list-toolbar'));
});

it('opens the panel below its own button, not centred on the page', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match('/\.list-select-panel \{(.*?)\}/s', $css, $m);

    expect($m[1])->toContain('left: 0')
        ->and($m[1])->not->toContain('translateX(-50%)');
});
