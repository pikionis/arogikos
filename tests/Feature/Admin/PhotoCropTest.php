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

    saveAuthor($author, ['circle_x' => 200, 'circle_y' => 100, 'circle_w' => 500, 'circle_h' => 500])
        ->assertRedirect()->assertSessionHasNoErrors();

    $author->refresh();

    expect($author->hasCrop('circle'))->toBeTrue()
        ->and([$author->circle_x, $author->circle_y, $author->circle_w, $author->circle_h])
        ->toBe([200, 100, 500, 500]);
});

it('cuts the picture the site shows down to it', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, ['circle_x' => 100, 'circle_y' => 0, 'circle_w' => 400, 'circle_h' => 400]);

    $media = $author->refresh()->getFirstMedia('photo');
    [$width, $height] = getimagesize($media->getPath('circle'));

    // A square crop, then the 400px resize: a square is what comes out.
    expect($width)->toBe($height);

    // And the upload itself is untouched, so it can be re-framed tomorrow.
    [$originalWidth] = getimagesize($media->getPath());
    expect($originalWidth)->toBe(1200);
});

it('pulls a square that reaches past the edge back inside', function () {
    $author = withPhoto(1200, 800);

    // A tab left open while the photo was replaced could send anything.
    saveAuthor($author, ['circle_x' => 1100, 'circle_y' => 700, 'circle_w' => 900, 'circle_h' => 900]);

    $author->refresh();

    expect($author->circle_w)->toBeLessThanOrEqual(1200)
        ->and($author->circle_h)->toBeLessThanOrEqual(800)
        ->and($author->circle_x + $author->circle_w)->toBeLessThanOrEqual(1200)
        ->and($author->circle_y + $author->circle_h)->toBeLessThanOrEqual(800);

    // And it still produced a file rather than failing the save.
    expect($author->getFirstMedia('photo')->getPath('circle'))->toBeFile();
});

it('treats the whole picture as no crop at all', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, ['circle_x' => 0, 'circle_y' => 0, 'circle_w' => 1200, 'circle_h' => 800]);

    expect($author->refresh()->hasCrop('circle'))->toBeFalse();
});

it('starts a newly uploaded photo whole', function () {
    $author = withPhoto(1200, 800);
    saveAuthor($author, ['circle_x' => 100, 'circle_y' => 100, 'circle_w' => 400, 'circle_h' => 400]);
    expect($author->refresh()->hasCrop('circle'))->toBeTrue();

    // A different picture: the numbers on the form describe the old one.
    saveAuthor($author, [
        'photo' => UploadedFile::fake()->image('another.jpg', 900, 900),
        'circle_x' => 100, 'circle_y' => 100, 'circle_w' => 400, 'circle_h' => 400,
    ])->assertRedirect();

    expect($author->refresh()->hasCrop('circle'))->toBeFalse();
});

it('forgets the crop when the photo is removed', function () {
    $author = withPhoto();
    saveAuthor($author, ['circle_x' => 10, 'circle_y' => 10, 'circle_w' => 300, 'circle_h' => 300]);

    saveAuthor($author, ['remove_photo' => '1'])->assertRedirect();

    expect($author->refresh()->hasCrop('circle'))->toBeFalse()
        ->and($author->getFirstMedia('photo'))->toBeNull();
});

/* ------------------------------------------------------ two frames, two answers */

it('frames the circle and the team photo separately', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, [
        // The face, for the small round one.
        'circle_x' => 300, 'circle_y' => 80, 'circle_w' => 400, 'circle_h' => 400,
        // More of them, for the tall one on the team page.
        'arch_x' => 250, 'arch_y' => 0, 'arch_w' => 540, 'arch_h' => 600,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $author->refresh();

    expect($author->hasCrop('circle'))->toBeTrue()
        ->and($author->hasCrop('arch'))->toBeTrue()
        ->and($author->circle_x)->not->toBe($author->arch_x);
});

it('gives each shape a picture of its own shape', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, [
        'circle_x' => 300, 'circle_y' => 80, 'circle_w' => 400, 'circle_h' => 400,
        'arch_x' => 250, 'arch_y' => 0, 'arch_w' => 540, 'arch_h' => 600,
    ]);

    $media = $author->refresh()->getFirstMedia('photo');

    [$cw, $ch] = getimagesize($media->getPath('circle'));
    [$aw, $ah] = getimagesize($media->getPath('arch'));

    // Square for the circle, taller than wide for the arch — and the two are
    // different files, not the same crop resized twice.
    expect($cw)->toBe($ch)
        ->and($ah)->toBeGreaterThan($aw)
        ->and(round($aw / $ah, 2))->toBe(0.9);
});

it('lets one shape be framed while the other is left alone', function () {
    $author = withPhoto(1200, 800);

    saveAuthor($author, ['arch_x' => 250, 'arch_y' => 0, 'arch_w' => 540, 'arch_h' => 600]);

    $author->refresh();

    expect($author->hasCrop('arch'))->toBeTrue()
        ->and($author->hasCrop('circle'))->toBeFalse();
});

it('shows each shape the picture it was given', function () {
    $author = withPhoto(1200, 800);
    saveAuthor($author, [
        'circle_x' => 300, 'circle_y' => 80, 'circle_w' => 400, 'circle_h' => 400,
        'arch_x' => 250, 'arch_y' => 0, 'arch_w' => 540, 'arch_h' => 600,
    ]);

    $author->refresh();

    // The byline avatar and the team portrait must not reach for the same
    // file, or one of them is showing the other one's framing.
    expect($author->photoUrl('circle'))->not->toBe($author->photoUrl('arch'));

    $html = $this->get(route('about'))->assertOk()->getContent();

    expect($html)->toContain(basename((string) parse_url($author->photoUrl('arch'), PHP_URL_PATH)));
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
        'circle_x' => 150, 'circle_y' => 50, 'circle_w' => 450, 'circle_h' => 450,
        'arch_x' => 100, 'arch_y' => 0, 'arch_w' => 540, 'arch_h' => 600,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $author->refresh();

    expect($author->circle_w)->toBe(450)
        ->and($author->arch_h)->toBe(600);
});

/* -------------------------------------------------------------- the tool */

it('offers the tool once there is a photo to frame', function () {
    $html = $this->actingAs(cropper())
        ->get(route('admin.authors.edit', withPhoto()))->assertOk()->getContent();

    // One window per shape the site draws, each with its own drag and zoom.
    expect(substr_count($html, 'a-crop-stage'))->toBe(count(Author::FRAMES))
        ->and(substr_count($html, 'type="range"'))->toBe(count(Author::FRAMES))
        ->and($html)->toContain('photoCrop(1)')
        ->and($html)->toContain('photoCrop(0.9)')
        ->and($html)->toContain('@pointerdown')
        ->and($html)->toContain('name="circle_x"')
        ->and($html)->toContain('name="arch_x"');
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
