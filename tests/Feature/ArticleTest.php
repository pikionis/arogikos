<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/* ------------------------------------------------------------- scopePublished */

it('published() excludes drafts (is_published = false)', function () {
    $published = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $draft = Article::factory()->draft()->create();

    $ids = Article::published()->pluck('id');

    expect($ids)->toContain($published->id)
        ->not->toContain($draft->id);
});

it('published() excludes articles with a future published_at', function () {
    $past = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->subWeek(),
    ]);
    $future = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $ids = Article::published()->pluck('id');

    expect($ids)->toContain($past->id)
        ->not->toContain($future->id);
});

it('published() excludes articles with a null published_at even when is_published', function () {
    $live = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $nullDate = Article::factory()->create([
        'is_published' => true,
        'published_at' => null,
    ]);

    $ids = Article::published()->pluck('id');

    expect($ids)->toContain($live->id)
        ->not->toContain($nullDate->id);
});

it('published() includes a past published article', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => Carbon::parse('2020-01-15 10:00:00'),
    ]);

    expect(Article::published()->pluck('id'))->toContain($article->id);
});

/* ------------------------------------------------------------- scopeInCategory */

it("inCategory('learning') returns only learning articles", function () {
    $learning = Article::factory()->count(2)->create(['category' => 'learning']);
    $other = Article::factory()->create(['category' => 'counseling']);

    $results = Article::inCategory('learning')->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->all())
        ->toEqualCanonicalizing($learning->pluck('id')->all())
        ->and($results->pluck('id'))->not->toContain($other->id);
});

it('inCategory(null) applies no filter and returns all articles', function () {
    Article::factory()->create(['category' => 'learning']);
    Article::factory()->create(['category' => 'counseling']);
    Article::factory()->create(['category' => 'parents']);

    expect(Article::inCategory(null)->count())->toBe(3);
});

it('inCategory() with an unknown category key applies no filter and returns all', function () {
    Article::factory()->create(['category' => 'learning']);
    Article::factory()->create(['category' => 'career']);

    expect(Article::inCategory('bogus')->count())->toBe(2);
});

/* -------------------------------------------------------------------- related */

it('related() returns published articles excluding itself, whatever their category', function () {
    $subject = Article::factory()->create([
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $others = Article::factory()->count(2)->create([
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDays(2),
    ]);

    // Used to be excluded. A category with two articles in it left the end of
    // a page with one card on it, so the section reaches wider now; see
    // RelatedArticlesTest for the order they come back in.
    $elsewhere = Article::factory()->create([
        'category' => 'counseling',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $related = $subject->related();

    expect($related->pluck('id'))->not->toContain($subject->id)
        ->and($related->pluck('id')->all())
        ->toEqualCanonicalizing([...$others->pluck('id')->all(), $elsewhere->id]);
});

it('related() never returns the article itself', function () {
    $subject = Article::factory()->create([
        'category' => 'parents',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    expect($subject->related()->pluck('id'))->not->toContain($subject->id);
});

it('related() excludes drafts and future-dated articles', function () {
    $subject = Article::factory()->create([
        'category' => 'career',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $visible = Article::factory()->create([
        'category' => 'career',
        'is_published' => true,
        'published_at' => now()->subDays(3),
    ]);

    $draft = Article::factory()->draft()->create(['category' => 'career']);
    $future = Article::factory()->create([
        'category' => 'career',
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $ids = $subject->related()->pluck('id');

    expect($ids)->toContain($visible->id)
        ->not->toContain($draft->id)
        ->not->toContain($future->id);
});

it('related() honours the limit argument', function () {
    $subject = Article::factory()->create([
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    Article::factory()->count(12)->create([
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDays(2),
    ]);

    // The default is a carousel's worth; a caller may ask for fewer.
    expect($subject->related())->toHaveCount(9)
        ->and($subject->related(2))->toHaveCount(2);
});

/* ------------------------------------------------------------------ accessors */

it('category_label, category_color and cover_motif match config for learning', function () {
    $article = Article::factory()->create(['category' => 'learning']);

    expect($article->category_label)->toBe('Μαθησιακές δυσκολίες')
        ->and($article->category_color)->toBe('green')
        ->and($article->cover_motif)->toBe('book');
});

it('accessors match config for counseling', function () {
    $article = Article::factory()->create(['category' => 'counseling']);

    expect($article->category_label)->toBe('Συμβουλευτική')
        ->and($article->category_color)->toBe('coral')
        ->and($article->cover_motif)->toBe('one');
});

it('categoryMeta() returns the full config array for the category', function () {
    $article = Article::factory()->create(['category' => 'career']);

    expect($article->categoryMeta())->toBe([
        'label' => 'Προσανατολισμός',
        'color' => 'green',
        'motif' => 'sun',
    ]);
});

/* ----------------------------------------------------------------------- slug */

it('auto-generates the slug from the title on create', function () {
    $article = Article::factory()->create([
        'title' => 'Μαθησιακές δυσκολίες στο σχολείο',
        'slug' => null,
    ]);

    expect($article->slug)->not->toBeEmpty()
        ->and($article->slug)->toBe(Str::slug('Μαθησιακές δυσκολίες στο σχολείο'));
});

it('does not regenerate the slug when the title is updated', function () {
    $article = Article::factory()->create([
        'title' => 'Αρχικός τίτλος',
        'slug' => null,
    ]);

    $original = $article->slug;

    $article->update(['title' => 'Νέος εντελώς διαφορετικός τίτλος']);

    expect($article->fresh()->slug)->toBe($original);
});
