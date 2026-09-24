<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Author photos
|--------------------------------------------------------------------------
| An author's photo has to reach both places a reader meets them: the blog
| (so it is obvious who wrote a piece) and the About page team grid. Without
| a photo the branded initials disc stands in.
*/

beforeEach(function () {
    Storage::fake('public');
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function authorWithPhoto(string $name = 'Μαρία Κωνσταντίνου'): Author
{
    $author = Author::factory()->create(['name' => $name, 'is_active' => true]);

    $author->addMedia(UploadedFile::fake()->image('maria.jpg', 600, 600))
        ->toMediaCollection('photo');

    return $author->refresh();
}

function articleBy(Author $author, string $title = 'Ένα άρθρο'): Article
{
    return Article::factory()->for($author)->create([
        'title' => $title,
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

/* ------------------------------------------------------------------ upload */

it('lets an author photo be uploaded from the admin', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $author = Author::factory()->create(['name' => 'Νέα Συντάκτρια']);

    $this->actingAs($user->refresh())->put(route('admin.authors.update', $author), [
        'name' => 'Νέα Συντάκτρια',
        'role_title' => 'Ψυχολόγος',
        'is_active' => '1',
        'photo' => UploadedFile::fake()->image('portrait.jpg', 800, 800),
    ])->assertSessionHasNoErrors();

    $media = $author->refresh()->getFirstMedia('photo');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('web'))->toBeTrue();
});

it('refuses a non-image as an author photo', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $author = Author::factory()->create();

    $this->actingAs($user->refresh())->put(route('admin.authors.update', $author), [
        'name' => $author->name,
        'photo' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf'),
    ])->assertSessionHasErrors('photo');
});

/* --------------------------------------------------------------------- blog */

it('shows the author photo on the article page', function () {
    $author = authorWithPhoto();
    $article = articleBy($author);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    expect($html)->toContain($author->photoUrl('thumb'))
        // The header avatar and the bio box at the foot.
        ->and(substr_count($html, 'class="avatar g has-photo"'))->toBeGreaterThanOrEqual(2);
});

it('shows the author photo on the blog listing card', function () {
    $author = authorWithPhoto();
    articleBy($author, 'Άρθρο με φωτογραφία συντάκτη');

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee($author->photoUrl('thumb'), false);
});

it('shows the author photo on the home page article cards', function () {
    $author = authorWithPhoto();
    articleBy($author);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($author->photoUrl('thumb'), false);
});

it('falls back to the initials disc when an author has no photo', function () {
    $author = Author::factory()->create(['name' => 'Χωρίς Φωτογραφία']);
    $article = articleBy($author);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        // Greek uppercase drops the tonos: "Χωρίς Φωτογραφία" → "ΧΦ".
        ->assertSeeText('ΧΦ')
        ->assertDontSee('has-photo', false);
});

it('uses the author name as the photo alt text', function () {
    $author = authorWithPhoto('Γιώργος Παπαδάκης');
    $article = articleBy($author);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSee('alt="Γιώργος Παπαδάκης"', false);
});

/* -------------------------------------------------------------------- about */

it('shows the author photo in the About page team grid', function () {
    $this->seed(PageSeeder::class);

    $author = authorWithPhoto('Ελένη Σαββίδη');

    $this->get(route('about'))
        ->assertOk()
        ->assertSeeText('Ελένη Σαββίδη')
        // The team card uses the larger web conversion.
        ->assertSee($author->photoUrl('web'), false);
});

it('keeps an author with no photo on the team grid with their motif', function () {
    $this->seed(PageSeeder::class);

    Author::factory()->create(['name' => 'Νίκος Βλαχάκης', 'is_active' => true]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSeeText('Νίκος Βλαχάκης')
        ->assertSee('data-motif="one"', false);
});

it('does not put an inactive author on the team grid even with a photo', function () {
    $this->seed(PageSeeder::class);

    $author = authorWithPhoto('Κρυφός Συνεργάτης');
    $author->forceFill(['is_active' => false])->save();

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSeeText('Κρυφός Συνεργάτης');
});

it('still shows an inactive author photo on their articles', function () {
    // Invited panel members start inactive — they are off the team grid, but
    // their byline on an article is still theirs.
    $author = authorWithPhoto('Ανενεργό Μέλος');
    $author->forceFill(['is_active' => false])->save();

    $article = articleBy($author);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Ανενεργό Μέλος')
        ->assertSee($author->photoUrl('thumb'), false);
});
