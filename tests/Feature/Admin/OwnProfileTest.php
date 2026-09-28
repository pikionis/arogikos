<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Ο καθένας το δικό του προφίλ
|--------------------------------------------------------------------------
| Η φωτογραφία και η ιδιότητα ενός συντάκτη είναι δικά του να τα γράψει.
| Μέχρι τώρα κάθε συντάκτης μπορούσε να ανοίξει — και να διαγράψει — το
| προφίλ οποιουδήποτε άλλου.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function editorWithProfile(array $authorAttributes = []): User
{
    $user = User::factory()->create(['name' => 'Ραφαέλα Ντινώρη']);
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    Author::factory()->create($authorAttributes + [
        'user_id' => $user->getKey(),
        'name' => $user->name,
        'is_active' => false,
    ]);

    return $user->refresh();
}

/* --------------------------------------------------------- own profile */

it('offers the public profile on the account screen', function () {
    $this->actingAs(editorWithProfile())
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSeeText('Το δημόσιο προφίλ μου')
        ->assertSeeText('Ιδιότητα')
        ->assertSee('name="bio"', false)
        ->assertSee('name="photo"', false);
});

it('saves the role, the bio and the photo', function () {
    Storage::fake('public');
    $user = editorWithProfile();

    $this->actingAs($user)->put(route('admin.profile.author'), [
        'role_title' => 'Εργοθεραπεύτρια',
        'bio' => 'Εργάζεται με παιδιά προσχολικής ηλικίας.',
        'photo' => UploadedFile::fake()->image('me.jpg', 800, 900),
    ])->assertRedirect();

    $author = $user->author->refresh();

    expect($author->role_title)->toBe('Εργοθεραπεύτρια')
        ->and($author->bio)->toBe('Εργάζεται με παιδιά προσχολικής ηλικίας.')
        ->and($author->photoUrl('thumb'))->not->toBeNull();
});

it('will not let anyone put themselves on the public team page', function () {
    $user = editorWithProfile();

    $this->actingAs($user)->put(route('admin.profile.author'), [
        'role_title' => 'Εργοθεραπεύτρια',
        'is_active' => 1,
    ])->assertRedirect();

    // Appearing on the site is the centre's call, not the colleague's.
    expect($user->author->refresh()->is_active)->toBeFalse();
});

it('keeps one name for the person, not two that can disagree', function () {
    $user = editorWithProfile();

    $this->actingAs($user)->put(route('admin.profile.update'), [
        'name' => 'Ραφαέλα Ντινώρη-Παππά',
        'email' => $user->email,
    ])->assertRedirect();

    expect($user->author->refresh()->name)->toBe('Ραφαέλα Ντινώρη-Παππά');
});

it('says so plainly when an account has no author profile', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $this->actingAs($user->refresh())
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSeeText('δεν έχει ακόμη προφίλ συντάκτη');
});

/* ------------------------------------------------ no duplicate profiles */

it('adopts the profile the centre already wrote, instead of opening a second', function () {
    // The real case: the centre wrote Ζέτα's profile — photo, role, articles
    // — long before she had an account.
    $existing = Author::factory()->create([
        'name' => 'Ζέτα Παπανδρέου',
        'role_title' => 'Ειδική Παιδαγωγός',
        'bio' => 'Συνεργάζεται με την Αρωγή από το 2019.',
        'user_id' => null,
        'is_active' => true,
    ]);
    $article = Article::factory()->create(['author_id' => $existing->id]);

    [, $token] = Invitation::issue('papandreouzeta@example.com', 'Ζέτα Παπανδρέου', Role::Editor);

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Ζέτα Παπανδρέου',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertRedirect(route('admin.dashboard'));

    // One profile, still hers, still holding her article.
    expect(Author::where('name', 'Ζέτα Παπανδρέου')->count())->toBe(1);

    $existing->refresh();

    expect($existing->user_id)->not->toBeNull()
        ->and($existing->role_title)->toBe('Ειδική Παιδαγωγός')
        ->and($existing->is_active)->toBeTrue()
        ->and($article->refresh()->author_id)->toBe($existing->id);
});

it('opens a fresh profile when there is nobody to adopt', function () {
    [, $token] = Invitation::issue('kainouryios@example.com', 'Καινούριος Συνεργάτης', Role::Editor);

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Καινούριος Συνεργάτης',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertRedirect();

    $author = Author::where('name', 'Καινούριος Συνεργάτης')->firstOrFail();

    // Hidden until somebody decides they belong on the site.
    expect($author->user_id)->not->toBeNull()
        ->and($author->is_active)->toBeFalse();
});

it('never adopts a profile that already belongs to another account', function () {
    $taken = Author::factory()->create(['name' => 'Κοινό Όνομα', 'user_id' => User::factory()->create()->id]);

    [, $token] = Invitation::issue('allos@example.com', 'Κοινό Όνομα', Role::Editor);

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Κοινό Όνομα',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertRedirect();

    expect(Author::where('name', 'Κοινό Όνομα')->count())->toBe(2)
        ->and($taken->refresh()->user_id)->not->toBe(User::latest('id')->first()->id);
});
