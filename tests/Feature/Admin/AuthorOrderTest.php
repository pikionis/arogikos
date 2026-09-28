<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\User;
use App\Support\AuthorPicker;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Η σειρά της ομάδας
|--------------------------------------------------------------------------
| Η σελίδα «Σχετικά με εμάς» έδειχνε όποιον είχε μπει πρώτος στη βάση — τυχαίο
| αποτέλεσμα του πότε δέχτηκε κάποιος την πρόσκλησή του, όχι απόφαση κανενός.
| Τώρα τη σειρά τη δίνει το panel με drag and drop: ψηλότερα εδώ σημαίνει πιο
| αριστερά στο πλέγμα της ομάδας.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function person(string $name, array $attributes = []): Author
{
    return Author::factory()->create($attributes + ['name' => $name, 'is_active' => true]);
}

function panelUser(Role $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

function reorder(User $actor, array $authors): TestResponse
{
    return test()->actingAs($actor)->patch(route('admin.authors.order'), [
        'order' => implode(',', array_map(fn (Author $a) => $a->id, $authors)),
    ]);
}

/* ----------------------------------------------------------------- basics */

it('starts everyone in the order they were created', function () {
    $first = person('Πρώτη');
    $second = person('Δεύτερος');

    expect(Author::ordered()->pluck('name')->all())->toBe(['Πρώτη', 'Δεύτερος'])
        ->and($first->position)->toBeLessThan($second->position);
});

it('puts a new profile at the end, not in front of the team', function () {
    person('Πρώτη');
    person('Δεύτερος');
    $newcomer = person('Ήρθε τελευταίος');

    expect(Author::ordered()->pluck('name')->last())->toBe('Ήρθε τελευταίος')
        ->and($newcomer->position)->toBe(3);
});

/* ------------------------------------------------------------- the saving */

it('saves the order the rows were dragged into', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');
    $c = person('Γάμα');

    reorder(panelUser(Role::Owner), [$c, $a, $b])->assertRedirect();

    expect(Author::ordered()->pluck('name')->all())->toBe(['Γάμα', 'Άλφα', 'Βήτα']);
});

it('moves the team on the public page with it', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');

    $html = $this->get(route('about'))->assertOk()->getContent();
    expect(mb_strpos($html, 'Άλφα'))->toBeLessThan(mb_strpos($html, 'Βήτα'));

    reorder(panelUser(Role::Owner), [$b, $a]);

    $html = $this->get(route('about'))->assertOk()->getContent();

    // Source order is left-to-right across the grid, so this is the order a
    // visitor reads them in.
    expect(mb_strpos($html, 'Βήτα'))->toBeLessThan(mb_strpos($html, 'Άλφα'));
});

it('follows through to every list of authors', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');

    reorder(panelUser(Role::Owner), [$b, $a]);

    // The article byline picker in the panel.
    expect(AuthorPicker::groups()['Άλλοι συντάκτες']->pluck('name')->all())
        ->toBe(['Βήτα', 'Άλφα']);

    // And the panel's own list, so what you dragged is where you left it.
    $html = $this->actingAs(panelUser(Role::Owner))
        ->get(route('admin.authors.index'))->assertOk()->getContent();

    expect(mb_strpos($html, 'Βήτα'))->toBeLessThan(mb_strpos($html, 'Άλφα'));
});

it('ignores an id that was deleted while the tab was open', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');
    $gone = person('Έφυγε');
    $gone->delete();

    reorder(panelUser(Role::Owner), [$b, $gone, $a])->assertRedirect();

    expect(Author::ordered()->pluck('name')->all())->toBe(['Βήτα', 'Άλφα']);
});

it('leaves the order alone when nothing was sent', function () {
    person('Άλφα');
    person('Βήτα');

    $this->actingAs(panelUser(Role::Owner))
        ->patch(route('admin.authors.order'), ['order' => ''])
        ->assertRedirect();

    expect(Author::ordered()->pluck('name')->all())->toBe(['Άλφα', 'Βήτα']);
});

/* -------------------------------------------------------------- who may */

it('lets an admin order the team, as well as the owner', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');

    reorder(panelUser(Role::Admin), [$b, $a])->assertRedirect();

    expect(Author::ordered()->pluck('name')->all())->toBe(['Βήτα', 'Άλφα']);
});

it('keeps an editor out of it', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');

    reorder(panelUser(Role::Editor), [$b, $a])->assertForbidden();

    expect(Author::ordered()->pluck('name')->all())->toBe(['Άλφα', 'Βήτα']);
});

it('keeps a stranger out of it', function () {
    $a = person('Άλφα');
    $b = person('Βήτα');

    $this->patch(route('admin.authors.order'), ['order' => "{$b->id},{$a->id}"])
        ->assertRedirect(route('admin.login'));

    expect(Author::ordered()->pluck('name')->all())->toBe(['Άλφα', 'Βήτα']);
});

/* --------------------------------------------------------------- the panel */

it('offers a handle on every row and a way to save', function () {
    person('Άλφα');
    person('Βήτα');

    $html = $this->actingAs(panelUser(Role::Owner))
        ->get(route('admin.authors.index'))->assertOk()->getContent();

    expect(substr_count($html, 'class="a-drag"'))->toBe(2)
        ->and($html)->toContain('sortableRows')
        ->and($html)->toContain('a-sortbar')
        ->and($html)->toContain(e('Αποθήκευση σειράς'));
});
