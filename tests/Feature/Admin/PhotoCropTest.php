<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/*
|--------------------------------------------------------------------------
| Ποιο κομμάτι της φωτογραφίας κρατάει το site
|--------------------------------------------------------------------------
| Ο κόσμος στέλνει ό,τι έχει: μια ομαδική, μια από διακοπές, τον εαυτό του
| στην άκρη του κάδρου. Το site κρατούσε τη μέση, που συχνά είναι ο ώμος
| κάποιου άλλου. Τώρα διαλέγει ο ίδιος, σέρνοντας τη φωτογραφία πίσω από ένα
| σταθερό παράθυρο.
|
| Το πρωτότυπο δεν αγγίζεται: κρατιούνται τέσσερις αριθμοί, ώστε το πλαίσιο
| να ξαναμετακινείται αύριο χωρίς να ξαναζητηθεί το αρχείο.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Storage::fake('public');
});

function cropper(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    return $user->refresh();
}

function withPhoto(int $width = 1200, int $height = 800): Author
{
    $author = Author::factory()->create(['name' => 'Με Φωτογραφία', 'is_active' => true]);
    $author->addMedia(UploadedFile::fake()->image('portrait.jpg', $width, $height))
        ->toMediaCollection('photo');

    return $author->refresh();
}

/**
 * @param  array<string, mixed>  $extra
 */
function saveAuthor(Author $author, array $extra = []): TestResponse
{
    return test()->actingAs(cropper())->put(route('admin.authors.update', $author), $extra + [
        'name' => $author->name,
        'role_title' => $author->role_title ?? '',
        'bio' => $author->bio ?? '',
        'is_active' => '1',
        'user_id' => (string) ($author->user_id ?? ''),
    ]);
}

/* ------------------------------------------------------------ the saving */

it('keeps the square somebody chose', function () {
    $author = withPhoto();

    saveAuthor($author, ['crop_x' => 200, 'crop_y' => 100, 'crop_w' => 500, 'crop_h' => 500])
        ->assertRedirect()->assertSessionHasNoErrors();

    $author->refresh();

    expect($author->hasCrop())->toBeTrue()
        ->and([$author->crop_x, $author->crop_y, $author->crop_w, $author->crop_h])
        ->toBe([200, 100, 500, 500]);
});

it('cuts the picture the site shows down to it', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, ['crop_x' => 100, 'crop_y' => 0, 'crop_w' => 400, 'crop_h' => 400]);

    $media = $author->refresh()->getFirstMedia('photo');
    [$width, $height] = getimagesize($media->getPath('thumb'));

    // A square crop, then the 400px resize: a square is what comes out.
    expect($width)->toBe($height);

    // And the upload itself is untouched, so it can be re-framed tomorrow.
    [$originalWidth] = getimagesize($media->getPath());
    expect($originalWidth)->toBe(1200);
});

it('pulls a square that reaches past the edge back inside', function () {
    $author = withPhoto(1200, 800);

    // A tab left open while the photo was replaced could send anything.
    saveAuthor($author, ['crop_x' => 1100, 'crop_y' => 700, 'crop_w' => 900, 'crop_h' => 900]);

    $author->refresh();

    expect($author->crop_w)->toBeLessThanOrEqual(1200)
        ->and($author->crop_h)->toBeLessThanOrEqual(800)
        ->and($author->crop_x + $author->crop_w)->toBeLessThanOrEqual(1200)
        ->and($author->crop_y + $author->crop_h)->toBeLessThanOrEqual(800);

    // And it still produced a file rather than failing the save.
    expect($author->getFirstMedia('photo')->getPath('thumb'))->toBeFile();
});

it('treats the whole picture as no crop at all', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, ['crop_x' => 0, 'crop_y' => 0, 'crop_w' => 1200, 'crop_h' => 800]);

    expect($author->refresh()->hasCrop())->toBeFalse();
});

it('starts a newly uploaded photo whole', function () {
    $author = withPhoto(1200, 800);
    saveAuthor($author, ['crop_x' => 100, 'crop_y' => 100, 'crop_w' => 400, 'crop_h' => 400]);
    expect($author->refresh()->hasCrop())->toBeTrue();

    // A different picture: the numbers on the form describe the old one.
    saveAuthor($author, [
        'photo' => UploadedFile::fake()->image('another.jpg', 900, 900),
        'crop_x' => 100, 'crop_y' => 100, 'crop_w' => 400, 'crop_h' => 400,
    ])->assertRedirect();

    expect($author->refresh()->hasCrop())->toBeFalse();
});

it('forgets the crop when the photo is removed', function () {
    $author = withPhoto();
    saveAuthor($author, ['crop_x' => 10, 'crop_y' => 10, 'crop_w' => 300, 'crop_h' => 300]);

    saveAuthor($author, ['remove_photo' => '1'])->assertRedirect();

    expect($author->refresh()->hasCrop())->toBeFalse()
        ->and($author->getFirstMedia('photo'))->toBeNull();
});

/* -------------------------------------------------------- own profile */

it('lets somebody frame their own photo', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $author = withPhoto();
    $author->forceFill(['user_id' => $user->id])->save();

    $this->actingAs($user->refresh())->put(route('admin.profile.author'), [
        'role_title' => 'Ψυχολόγος',
        'bio' => 'Λίγα λόγια.',
        'crop_x' => 150, 'crop_y' => 50, 'crop_w' => 450, 'crop_h' => 450,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($author->refresh()->crop_w)->toBe(450);
});

/* -------------------------------------------------------------- the tool */

it('offers the tool once there is a photo to frame', function () {
    $html = $this->actingAs(cropper())
        ->get(route('admin.authors.edit', withPhoto()))->assertOk()->getContent();

    expect($html)->toContain('photoCrop')
        ->and($html)->toContain('a-crop-stage')
        // Drag and zoom, which is the whole tool.
        ->and($html)->toContain('@pointerdown')
        ->and($html)->toContain('type="range"')
        ->and($html)->toContain('name="crop_x"');
});

it('says nothing until a photo has been uploaded', function () {
    $author = Author::factory()->create(['name' => 'Χωρίς Φωτογραφία']);

    $html = $this->actingAs(cropper())
        ->get(route('admin.authors.edit', $author))->assertOk()->getContent();

    expect($html)->not->toContain('a-crop-stage');
});

it('stops the tool taking the page over on a phone', function () {
    $css = file_get_contents(resource_path('css/admin.css'));

    // Without touch-action the drag scrolls the page instead of moving the
    // picture, which makes the tool unusable exactly where it is fiddliest.
    expect($css)->toMatch('/\.a-crop-stage \{[^}]*touch-action: none/')
        ->and($css)->toContain('.a-crop-stage:focus-visible');
});
