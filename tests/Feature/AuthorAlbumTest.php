<?php

declare(strict_types=1);

use App\Models\Cms\Album;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Photo;

/*
|--------------------------------------------------------------------------
| Author
|--------------------------------------------------------------------------
*/

it('active scope excludes inactive authors', function () {
    $active = Author::factory()->create();
    $inactive = Author::factory()->inactive()->create();

    $ids = Author::active()->pluck('id');

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($inactive->id)
        ->and(Author::active()->count())->toBe(1);
});

it('active scope returns every active author', function () {
    Author::factory()->count(3)->create();
    Author::factory()->inactive()->count(2)->create();

    expect(Author::active()->count())->toBe(3)
        ->and(Author::count())->toBe(5);
});

it('initials returns the uppercased first letters of the first two words', function () {
    $author = Author::factory()->create(['name' => 'Μαρία Κωνσταντίνου']);

    expect($author->initials())->toBe('ΜΚ');
});

it('initials uses only the first two words and drops the Greek tonos', function () {
    $author = Author::factory()->create(['name' => 'Άννα Μαρία Παπαδοπούλου']);

    // Greek uppercase drops the accent: "Άννα" → "Α" (not "Ά").
    expect($author->initials())->toBe('ΑΜ');
});

it('initials returns a single letter for a single-word name', function () {
    $author = Author::factory()->create(['name' => 'Νίκος']);

    expect($author->initials())->toBe('Ν');
});

it('initials collapses extra whitespace between words', function () {
    $author = Author::factory()->create(['name' => 'Μαρία   Κωνσταντίνου']);

    expect($author->initials())->toBe('ΜΚ');
});

it('author hasMany articles relation works', function () {
    $author = Author::factory()->create();
    Article::factory()->count(2)->by($author)->create();
    // an article belonging to a different author must not leak in
    Article::factory()->create();

    expect($author->articles)->toHaveCount(2)
        ->and($author->articles->first())->toBeInstanceOf(Article::class)
        ->and($author->articles()->count())->toBe(2);
});

it('article belongsTo author resolves back to the owner', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->by($author)->create();

    expect($article->authors->first())->toBeInstanceOf(Author::class)
        ->and($article->authors->modelKeys())->toBe([$author->id]);
});

/*
|--------------------------------------------------------------------------
| Album / Photo
|--------------------------------------------------------------------------
*/

it('album photos relation returns photos ordered by sort_order ascending', function () {
    $album = Album::factory()->create();

    // Create photos out of order on purpose.
    $third = Photo::factory()->for($album)->create(['sort_order' => 30]);
    $first = Photo::factory()->for($album)->create(['sort_order' => 10]);
    $second = Photo::factory()->for($album)->create(['sort_order' => 20]);

    $ordered = $album->photos()->get();

    expect($ordered->pluck('id')->all())->toBe([$first->id, $second->id, $third->id])
        ->and($ordered->pluck('sort_order')->all())->toBe([10, 20, 30]);
});

it('album photos relation only contains its own photos', function () {
    $album = Album::factory()->create();
    Photo::factory()->count(2)->for($album)->create();
    Photo::factory()->create(); // belongs to a different album

    expect($album->photos)->toHaveCount(2)
        ->and($album->photos->pluck('album_id')->unique()->all())->toBe([$album->id]);
});

it('photo belongsTo album resolves back to its album', function () {
    $album = Album::factory()->create();
    $photo = Photo::factory()->for($album)->create();

    expect($photo->album)->toBeInstanceOf(Album::class)
        ->and($photo->album->id)->toBe($album->id);
});

it('photo imageUrl returns null when no media is attached', function () {
    $photo = Photo::factory()->create();

    expect($photo->imageUrl())->toBeNull()
        ->and($photo->imageUrl('thumb'))->toBeNull();
});
