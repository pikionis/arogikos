<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Ποιος λογαριασμός ανήκει σε ποιο προφίλ
|--------------------------------------------------------------------------
| Η σύνδεση γινόταν μόνο τη στιγμή που κάποιος δεχόταν την πρόσκλησή του. Αν
| αστοχούσε τότε, η φόρμα συντάκτη την έδειχνε αλλά δεν την έφτιαχνε: το προφίλ
| έγραφε «Χωρίς πρόσβαση» για κάποιον που είχε λογαριασμό, και το «Το προφίλ
| μου» του ζητούσε να απευθυνθεί σε διαχειριστή που δεν είχε κουμπί να τον
| εξυπηρετήσει.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

function boss2(Role $role = Role::Owner): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

/** The whole form, so a save never silently drops the other fields. */
function authorPayload(Author $author, array $overrides = []): array
{
    return $overrides + [
        'name' => $author->name,
        'role_title' => $author->role_title ?? '',
        'bio' => $author->bio ?? '',
        'is_active' => $author->is_active ? '1' : '0',
        'user_id' => (string) ($author->user_id ?? ''),
    ];
}

/* ---------------------------------------------------------------- linking */

it('links a profile to an account from the form', function () {
    $author = Author::factory()->create(['name' => 'Ζέτα Παπανδρέου', 'is_active' => true]);
    $account = boss2(Role::Editor);

    $this->actingAs(boss2())
        ->put(route('admin.authors.update', $author), authorPayload($author, ['user_id' => (string) $account->id]))
        ->assertRedirect();

    expect($author->fresh()->user_id)->toBe($account->id);
});

it('lets that person then edit their own photo and bio', function () {
    $account = boss2(Role::Editor);
    $author = Author::factory()->create(['name' => 'Ζέτα Παπανδρέου']);

    // Before: the panel tells them to go and ask somebody.
    $this->actingAs($account)->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSeeText('δεν έχει ακόμη προφίλ συντάκτη');

    $this->actingAs(boss2())
        ->put(route('admin.authors.update', $author), authorPayload($author, ['user_id' => (string) $account->id]));

    // A fresh instance: this test already loaded the relation as null above,
    // and actingAs hands the very same object back to the next request.
    $this->actingAs($account->fresh())->put(route('admin.profile.author'), [
        'role_title' => 'Ειδική Παιδαγωγός',
        'bio' => 'Το βιογραφικό μου.',
    ])->assertRedirect();

    expect($author->fresh()->role_title)->toBe('Ειδική Παιδαγωγός');
});

it('unlinks again by choosing no account', function () {
    $account = boss2(Role::Editor);
    $author = Author::factory()->create(['user_id' => $account->id]);

    $this->actingAs(boss2())
        ->put(route('admin.authors.update', $author), authorPayload($author, ['user_id' => '']))
        ->assertRedirect();

    expect($author->fresh()->user_id)->toBeNull();
});

it('refuses an account that already belongs to another profile', function () {
    $account = boss2(Role::Editor);
    Author::factory()->create(['user_id' => $account->id, 'name' => 'Ο πρώτος']);
    $other = Author::factory()->create(['name' => 'Ο δεύτερος']);

    $this->actingAs(boss2())
        ->put(route('admin.authors.update', $other), authorPayload($other, ['user_id' => (string) $account->id]))
        ->assertSessionHasErrors('user_id');

    expect($other->fresh()->user_id)->toBeNull();
});

it('keeps the account it already has selectable on its own profile', function () {
    $account = boss2(Role::Editor);
    $author = Author::factory()->create(['user_id' => $account->id, 'name' => 'Δική της']);

    $html = $this->actingAs(boss2())
        ->get(route('admin.authors.edit', $author))->assertOk()->getContent();

    // Offered, and offered as the current choice.
    preg_match('/<select[^>]*id="user_id".*?<\/select>/s', $html, $select);

    expect($select[0])->toContain('value="'.$account->id.'"')
        ->and($select[0])->toMatch('/value="'.$account->id.'"\s+selected/');
});

it('does not offer an account somebody else already owns', function () {
    $taken = boss2(Role::Editor);
    Author::factory()->create(['user_id' => $taken->id]);
    $author = Author::factory()->create(['name' => 'Χωρίς λογαριασμό']);

    $html = $this->actingAs(boss2())
        ->get(route('admin.authors.edit', $author))->assertOk()->getContent();

    // Inside the account dropdown only: value="1" also belongs to checkboxes.
    preg_match('/<select[^>]*id="user_id".*?<\/select>/s', $html, $select);

    expect($select[0])->not->toContain('value="'.$taken->id.'"')
        ->and($select[0])->toContain('value=""');
});

/* ------------------------------------------------------------ the pointer */

it('points out a profile whose name matches a free account', function () {
    $account = boss2(Role::Editor);
    $account->forceFill(['name' => 'Ζέτα Παπανδρέου'])->save();

    // Written the other way round in the profile: Greek uppercase drops the
    // tonos, and that is exactly how the link got missed in the first place.
    Author::factory()->create(['name' => 'ΖΕΤΑ ΠΑΠΑΝΔΡΕΟΥ', 'is_active' => true]);

    $this->actingAs(boss2())->get(route('admin.authors.index'))
        ->assertOk()
        ->assertSeeText('Υπάρχει λογαριασμός με αυτό το όνομα');
});

it('says nothing when there is no account to match', function () {
    Author::factory()->create(['name' => 'Κάποιος Άλλος']);

    $this->actingAs(boss2())->get(route('admin.authors.index'))
        ->assertOk()
        ->assertDontSeeText('Υπάρχει λογαριασμός με αυτό το όνομα');
});

/* ----------------------------------------------------------------- access */

it('keeps an editor out of the author form entirely', function () {
    $author = Author::factory()->create();

    $this->actingAs(boss2(Role::Editor))
        ->put(route('admin.authors.update', $author), authorPayload($author, ['name' => 'Αλλαγμένο']))
        ->assertForbidden();

    expect($author->fresh()->name)->not->toBe('Αλλαγμένο');
});

it('lets an admin do it, not only the owner', function () {
    $author = Author::factory()->create(['name' => 'Ζέτα Παπανδρέου']);
    $account = boss2(Role::Editor);

    $this->actingAs(boss2(Role::Admin))
        ->put(route('admin.authors.update', $author), authorPayload($author, ['user_id' => (string) $account->id]))
        ->assertRedirect();

    expect($author->fresh()->user_id)->toBe($account->id);
});
