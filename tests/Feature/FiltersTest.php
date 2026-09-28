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

function postBy(Author $author, string $title, string $category = 'learning'): Article
{
    return Article::factory()->create([
        'title' => $title,
        'author_id' => $author->id,
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
        ->assertSee('author-select', false)
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

    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    // A name alone tells a reader nothing; the role is how they recognise
    // the person whose articles they came back for.
    expect($html)->toContain(e('Ειδική Παιδαγωγός'))
        ->and($html)->toContain(e('Ψυχολόγος'))
        // One row per author plus the "all" row. Counted on the anchor, not
        // the bare class: author-option-all is a substring of it.
        ->and(substr_count($html, '<a class="author-option'))->toBe(3);
});

it('names the chosen author on the closed picker', function () {
    $maria = writer('Μαρία Παπαδοπούλου');
    postBy($maria, 'Άρθρο της Μαρίας');
    $kostas = writer('Κώστας Δημητρίου');
    postBy($kostas, 'Άρθρο του Κώστα');

    $html = $this->get(route('blog.index', ['author' => $maria->id]))->getContent();

    // Closed, it must say who is selected rather than "all authors".
    expect($html)->toContain('author-select-label');

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
