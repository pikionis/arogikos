<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Ένωση διπλότυπων προφίλ
|--------------------------------------------------------------------------
| Όποιος αποδέχτηκε πρόσκληση πριν διορθωθεί η ροή απέκτησε δεύτερο, κενό
| προφίλ. Το site έδειχνε το πραγματικό, ο λογαριασμός του έδειχνε το κενό.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function accountWithBlankProfile(string $name): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    Author::factory()->create([
        'user_id' => $user->getKey(),
        'name' => $name,
        'role_title' => null,
        'bio' => null,
        'is_active' => false,
    ]);

    return $user->refresh();
}

it('hands the account the profile that has the photo and the articles', function () {
    Storage::fake('public');

    $real = Author::factory()->create([
        'name' => 'Ραφαέλα Ντινώρη',
        'role_title' => 'Εργοθεραπεύτρια',
        'user_id' => null,
        'is_active' => true,
    ]);
    $real->addMedia(UploadedFile::fake()->image('r.jpg', 600, 700))->toMediaCollection('photo');
    $article = Article::factory()->create(['author_id' => $real->id]);

    $user = accountWithBlankProfile('Ραφαέλα Ντινώρη');
    $blankId = $user->author->id;

    $this->artisan('cms:merge-author-duplicates --apply')->assertSuccessful();

    expect(Author::find($blankId))->toBeNull()
        ->and($real->refresh()->user_id)->toBe($user->getKey())
        ->and($real->is_active)->toBeTrue()
        ->and($real->role_title)->toBe('Εργοθεραπεύτρια')
        ->and($article->refresh()->author_id)->toBe($real->id)
        ->and($user->refresh()->author->id)->toBe($real->id);
});

it('changes nothing without --apply', function () {
    Author::factory()->create(['name' => 'Ραφαέλα Ντινώρη', 'user_id' => null]);
    $user = accountWithBlankProfile('Ραφαέλα Ντινώρη');
    $blankId = $user->author->id;

    $this->artisan('cms:merge-author-duplicates')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(Author::find($blankId))->not->toBeNull();
});

it('refuses to drop a profile that holds anything', function () {
    Author::factory()->create(['name' => 'Κάποιος Συντάκτης', 'user_id' => null]);

    $user = accountWithBlankProfile('Κάποιος Συντάκτης');
    // The account's own profile turns out to carry an article.
    Article::factory()->create(['author_id' => $user->author->id]);

    $this->artisan('cms:merge-author-duplicates --apply')
        ->expectsOutputToContain('left alone')
        ->assertSuccessful();

    expect(Author::find($user->author->id))->not->toBeNull()
        ->and(Author::where('name', 'Κάποιος Συντάκτης')->count())->toBe(2);
});

it('leaves an account alone when nobody shares its name', function () {
    $user = accountWithBlankProfile('Μοναδικό Όνομα');

    $this->artisan('cms:merge-author-duplicates --apply')->assertSuccessful();

    expect($user->refresh()->author)->not->toBeNull();
});

it('never touches an invited author who has not accepted yet', function () {
    // Nobody has this person's account, so there is no duplicate — and their
    // profile must survive untouched until they click the link.
    $waiting = Author::factory()->create([
        'name' => 'Ζέτα Παπανδρέου',
        'role_title' => 'Ειδική Παιδαγωγός',
        'user_id' => null,
    ]);
    Article::factory()->create(['author_id' => $waiting->id]);

    $this->artisan('cms:merge-author-duplicates --apply')->assertSuccessful();

    expect($waiting->refresh())->not->toBeNull()
        ->and($waiting->user_id)->toBeNull()
        ->and($waiting->articles()->count())->toBe(1);
});

it('matches Greek names regardless of case', function () {
    Author::factory()->create(['name' => 'ΡΑΦΑΕΛΑ ΝΤΙΝΩΡΗ', 'user_id' => null, 'role_title' => 'Εργοθεραπεύτρια']);
    $user = accountWithBlankProfile('Ραφαέλα Ντινώρη');

    $this->artisan('cms:merge-author-duplicates --apply')->assertSuccessful();

    expect(Author::where('user_id', $user->getKey())->first()->role_title)
        ->toBe('Εργοθεραπεύτρια');
});
