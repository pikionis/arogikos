<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Τι να διαβάσεις μετά
|--------------------------------------------------------------------------
| Τα προτεινόμενα έδειχναν μέχρι τρία άρθρα της ίδιας κατηγορίας. Μια
| κατηγορία με δύο άρθρα άφηνε τον αναγνώστη στο τέλος μιας σελίδας με μία
| κάρτα και πουθενά να πάει — το αντίθετο από αυτό που κάνει η ενότητα.
|
| Τώρα: ό,τι άλλο έχει γράψει ο ίδιος ο συντάκτης πρώτα, και από πίσω το
| υπόλοιπο blog, με τα πιο πρόσφατα μπροστά. Σε καρουζέλ, ώστε να χωρούν όλα.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function piece(string $title, array $authors = [], array $attributes = []): Article
{
    $factory = Article::factory();

    foreach ($authors as $author) {
        $factory = $factory->by($author);
    }

    return $factory->create($attributes + [
        'title' => $title,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

function penName(string $name): Author
{
    return Author::factory()->create(['name' => $name, 'is_active' => true]);
}

/* ------------------------------------------------------------ the order */

it('puts the rest of the same writer first', function () {
    $maria = penName('Μαρία');
    $other = penName('Άλλος');

    $current = piece('Αυτό που διαβάζω', [$maria], ['published_at' => now()->subDays(10)]);
    piece('Από κάποιον άλλον, χθες', [$other], ['published_at' => now()->subDay()]);
    $alsoMaria = piece('Της Μαρίας, πέρυσι', [$maria], ['published_at' => now()->subYear()]);

    $related = $current->related();

    // Older, but hers — so it leads, ahead of yesterday's by somebody else.
    expect($related->first()->id)->toBe($alsoMaria->id)
        ->and($related->pluck('title')->all())
        ->toBe(['Της Μαρίας, πέρυσι', 'Από κάποιον άλλον, χθες']);
});

it('orders the writer\'s own by how recent they are', function () {
    $maria = penName('Μαρία');
    $current = piece('Αυτό που διαβάζω', [$maria]);

    piece('Παλιό της', [$maria], ['published_at' => now()->subMonths(6)]);
    piece('Πρόσφατο της', [$maria], ['published_at' => now()->subDays(2)]);

    expect($current->related()->pluck('title')->all())
        ->toBe(['Πρόσφατο της', 'Παλιό της']);
});

it('falls back to the newest in the blog when the writer has nothing else', function () {
    $current = piece('Μόνο αυτό έγραψε', [penName('Μόνη Της')]);

    piece('Παλιό ξένο', [penName('Α')], ['published_at' => now()->subYear()]);
    piece('Νέο ξένο', [penName('Β')], ['published_at' => now()->subHour()]);

    expect($current->related()->pluck('title')->all())
        ->toBe(['Νέο ξένο', 'Παλιό ξένο']);
});

it('counts a writer who shares the byline', function () {
    $maria = penName('Μαρία');
    $giorgos = penName('Γιώργος');

    $current = piece('Γραμμένο μαζί', [$maria, $giorgos], ['published_at' => now()->subDays(5)]);

    piece('Μόνο του Γιώργου', [$giorgos], ['published_at' => now()->subYear()]);
    piece('Κάποιου τρίτου, σήμερα', [penName('Τρίτος')], ['published_at' => now()->subHour()]);

    expect($current->related()->first()->title)->toBe('Μόνο του Γιώργου');
});

/* ---------------------------------------------------------- the choosing */

it('never suggests the article you are reading', function () {
    $maria = penName('Μαρία');
    $current = piece('Αυτό που διαβάζω', [$maria]);
    piece('Κάτι άλλο', [$maria]);

    expect($current->related()->modelKeys())->not->toContain($current->id);
});

it('suggests nothing that is not published yet', function () {
    $current = piece('Αυτό που διαβάζω', [penName('Μαρία')]);

    Article::factory()->draft()->create(['title' => 'Πρόχειρο']);
    Article::factory()->create([
        'title' => 'Προγραμματισμένο',
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    expect($current->related())->toHaveCount(0);
});

it('crosses categories rather than leaving the page a dead end', function () {
    $current = piece('Αυτό που διαβάζω', [penName('Μαρία')], ['category' => 'learning']);
    piece('Άλλης κατηγορίας', [penName('Άλλος')], ['category' => 'parents']);

    expect($current->related())->toHaveCount(1);
});

it('names each article only once', function () {
    $maria = penName('Μαρία');
    $current = piece('Αυτό που διαβάζω', [$maria]);

    foreach (range(1, 4) as $i) {
        piece("Της Μαρίας $i", [$maria]);
    }

    $keys = $current->related()->modelKeys();

    expect($keys)->toHaveCount(count(array_unique($keys)));
});

/* ----------------------------------------------------------- the carousel */

it('shows them all on the page, in a row that scrolls', function () {
    $maria = penName('Μαρία');
    $current = piece('Αυτό που διαβάζω', [$maria]);

    foreach (range(1, 6) as $i) {
        piece("Προτεινόμενο $i", [$maria]);
    }

    $html = $this->get(route('blog.show', $current->slug))->assertOk()->getContent();

    expect($html)->toContain('post-carousel-track')
        ->and(substr_count($html, 'class="post-card blog-card'))->toBe(6);

    foreach (range(1, 6) as $i) {
        expect($html)->toContain("Προτεινόμενο $i");
    }
});

it('offers a way through it that is not only the scrollbar', function () {
    $maria = penName('Μαρία');
    $current = piece('Αυτό που διαβάζω', [$maria]);
    piece('Ένα ακόμα', [$maria]);

    $html = $this->get(route('blog.show', $current->slug))->assertOk()->getContent();

    expect($html)->toContain('data-carousel-back')
        ->and($html)->toContain('data-carousel-next')
        // Reachable by keyboard, and announced as one group.
        ->and($html)->toContain('data-carousel-track tabindex="0"')
        ->and($html)->toContain('aria-label="Επόμενα άρθρα"');
});

it('suggests something under every article that has company', function () {
    $maria = penName('Μαρία');
    $first = piece('Πρώτο', [$maria]);
    $second = piece('Δεύτερο', [penName('Άλλος')]);

    // Both ways round: whichever one you open, the other is offered.
    $this->get(route('blog.show', $first->slug))->assertOk()->assertSeeText('Δεύτερο');
    $this->get(route('blog.show', $second->slug))->assertOk()->assertSeeText('Πρώτο');
});

it('says nothing when there is nothing else to read', function () {
    $only = piece('Το μόνο άρθρο', [penName('Μαρία')]);

    $html = $this->get(route('blog.show', $only->slug))->assertOk()->getContent();

    expect($html)->not->toContain('post-carousel')
        ->and($html)->not->toContain(e(content('blog.related_title')));
});
