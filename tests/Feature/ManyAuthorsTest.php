<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\User;
use App\Support\StructuredData;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Ένα άρθρο, όσοι το έγραψαν
|--------------------------------------------------------------------------
| Το άρθρο κρατούσε έναν συντάκτη, οπότε ο δεύτερος και ο τρίτος απλώς δεν
| εμφανίζονταν πουθενά. Η υπογραφή είναι λίστα τώρα, και η σειρά της είναι η
| σειρά των «Συντακτών» — εκεί όπου η ομάδα σύρεται ήδη με το χέρι.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function signer(string $name, array $attributes = []): Author
{
    return Author::factory()->create($attributes + [
        'name' => $name,
        'role_title' => 'Ειδική Παιδαγωγός',
        'is_active' => true,
    ]);
}

function editorUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    return $user->refresh();
}

/* ------------------------------------------------------------- the byline */

it('signs an article with everyone who wrote it', function () {
    $article = Article::factory()
        ->by(signer('Άλφα Αλφα'), signer('Βήτα Βήτα'), signer('Γάμα Γάμα'))
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    expect($article->authors)->toHaveCount(3)
        ->and($article->byline())->toBe('Άλφα Αλφα, Βήτα Βήτα και Γάμα Γάμα');
});

it('reads as one name when only one person wrote it', function () {
    $article = Article::factory()->by(signer('Μόνη Της'))->create();

    expect($article->byline())->toBe('Μόνη Της');
});

it('leaves the byline empty rather than inventing one', function () {
    $article = Article::factory()->create([
        'is_published' => true, 'published_at' => now()->subDay(),
    ]);

    expect($article->byline())->toBe('');

    // And the page still stands.
    $this->get(route('blog.show', $article->slug))->assertOk();
});

it('follows the order of the team, not the order they were ticked', function () {
    $second = signer('Δεύτερη Στη Λίστα', ['position' => 2]);
    $first = signer('Πρώτος Στη Λίστα', ['position' => 1]);

    // Attached the other way round on purpose.
    $article = Article::factory()->by($second, $first)->create();

    expect($article->byline())->toBe('Πρώτος Στη Λίστα και Δεύτερη Στη Λίστα');

    // Drag the other one to the top of the team and the byline follows.
    $second->update(['position' => 0]);

    expect($article->fresh()->byline())->toBe('Δεύτερη Στη Λίστα και Πρώτος Στη Λίστα');
});

/* -------------------------------------------------------------- the pages */

it('shows every face and name on the article page', function () {
    $article = Article::factory()
        ->by(signer('Άλφα Αλφα'), signer('Βήτα Βήτα'))
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Άλφα Αλφα και Βήτα Βήτα')
        ->assertSee('<meta property="article:author" content="Άλφα Αλφα">', false)
        ->assertSee('<meta property="article:author" content="Βήτα Βήτα">', false);
});

it('gives each of them their own box to open', function () {
    $long = str_repeat('Κείμενο βιογραφικού. ', 20);
    $a = signer('Άλφα Αλφα', ['bio' => $long]);
    $b = signer('Βήτα Βήτα', ['bio' => $long]);

    $article = Article::factory()->by($a, $b)
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    expect(substr_count($html, 'class="author-card reveal"'))->toBe(2)
        ->and($html)->toContain('<dialog id="author-'.$a->id.'"')
        ->and($html)->toContain('<dialog id="author-'.$b->id.'"');
});

it('leaves out a box for somebody with no bio', function () {
    $withBio = signer('Έχει Βιογραφικό', ['bio' => str_repeat('Κείμενο. ', 30)]);
    $without = signer('Δεν Έχει', ['bio' => null]);

    $article = Article::factory()->by($withBio, $without)
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $html = $this->get(route('blog.show', $article->slug))->assertOk()->getContent();

    // Named in the byline either way — there is just nothing to open.
    expect(substr_count($html, 'class="author-card reveal"'))->toBe(1)
        ->and($html)->toContain('Έχει Βιογραφικό και Δεν Έχει')
        ->and($html)->not->toContain('<dialog id="author-'.$without->id.'"');
});

it('stacks the faces on a card in the listing', function () {
    Article::factory()->by(signer('Άλφα Αλφα'), signer('Βήτα Βήτα'))
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    expect($html)->toContain('byline-faces')
        ->and($html)->toContain('Άλφα Αλφα και Βήτα Βήτα');
});

/* ------------------------------------------------------------- the filter */

it('finds the article under either of its authors', function () {
    $a = signer('Άλφα Αλφα');
    $b = signer('Βήτα Βήτα');

    Article::factory()->by($a, $b)->create([
        'title' => 'Γραμμένο από δύο', 'is_published' => true, 'published_at' => now()->subDay(),
    ]);
    Article::factory()->by($b)->create([
        'title' => 'Γραμμένο μόνο από τη Βήτα', 'is_published' => true, 'published_at' => now()->subDay(),
    ]);

    $this->get(route('blog.index', ['author' => $a->id]))
        ->assertOk()
        ->assertSeeText('Γραμμένο από δύο')
        ->assertDontSeeText('Γραμμένο μόνο από τη Βήτα');

    $this->get(route('blog.index', ['author' => $b->id]))
        ->assertOk()
        ->assertSeeText('Γραμμένο από δύο')
        ->assertSeeText('Γραμμένο μόνο από τη Βήτα');
});

/* ------------------------------------------------------------ the marking */

it('names every one of them to Google', function () {
    $article = Article::factory()
        ->by(signer('Άλφα Αλφα'), signer('Βήτα Βήτα'))
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    $node = StructuredData::article($article->fresh());

    expect($node['author'])->toHaveCount(2)
        ->and(array_column($node['author'], 'name'))->toBe(['Άλφα Αλφα', 'Βήτα Βήτα']);
});

it('keeps a single author a single node, not a list of one', function () {
    $article = Article::factory()->by(signer('Μόνη Της'))
        ->create(['is_published' => true, 'published_at' => now()->subDay()]);

    expect(StructuredData::article($article->fresh())['author']['name'])->toBe('Μόνη Της');
});

/* -------------------------------------------------------------- the panel */

it('ticks whoever already signs the article', function () {
    $signing = signer('Υπογράφει');
    $not = signer('Δεν υπογράφει');
    $article = Article::factory()->by($signing)->create();

    $html = $this->actingAs(editorUser())
        ->get(route('admin.articles.edit', $article))->assertOk()->getContent();

    expect($html)->toMatch('/value="'.$signing->id.'"\s+checked/')
        ->and($html)->not->toMatch('/value="'.$not->id.'"\s+checked/');
});

it('saves however many were ticked', function () {
    $a = signer('Άλφα Αλφα');
    $b = signer('Βήτα Βήτα');

    $this->actingAs(editorUser())->post(route('admin.articles.store'), [
        'title' => 'Γραμμένο μαζί',
        'body' => '<p>Κείμενο.</p>',
        'category' => 'learning',
        'author_ids' => ['', (string) $a->id, (string) $b->id],
        'is_published' => '1',
    ])->assertRedirect();

    expect(Article::firstOrFail()->authors->modelKeys())->toBe([$a->id, $b->id]);
});

it('takes one of them off again', function () {
    $a = signer('Μένει');
    $b = signer('Φεύγει');
    $article = Article::factory()->by($a, $b)->create();

    $this->actingAs(editorUser())->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'category' => $article->category,
        'author_ids' => ['', (string) $a->id],
    ])->assertRedirect();

    expect($article->fresh()->authors->modelKeys())->toBe([$a->id]);
});

it('takes them all off when nobody is ticked', function () {
    $article = Article::factory()->by(signer('Είχε υπογραφή'))->create();

    $this->actingAs(editorUser())->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'category' => $article->category,
        // What an all-unticked list actually posts.
        'author_ids' => [''],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($article->fresh()->authors)->toHaveCount(0);
});

/* ------------------------------------------------------------- the losses */

it('leaves an article standing when one of its authors is deleted', function () {
    $staying = signer('Μένει');
    $leaving = signer('Διαγράφεται');
    $article = Article::factory()->by($staying, $leaving)->create([
        'is_published' => true, 'published_at' => now()->subDay(),
    ]);

    $leaving->delete();

    expect($article->fresh()->authors->modelKeys())->toBe([$staying->id]);

    $this->get(route('blog.show', $article->slug))->assertOk()->assertSeeText('Μένει');
});
