<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Category;
use App\Models\User;
use App\Support\Categories;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Blog categories
|--------------------------------------------------------------------------
| Managed inline from the article form. The rule that matters: removing a
| category that articles already use must NOT delete those articles and must
| NOT change the category they were given — label, colour and motif included.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function catUser(Role $role = Role::Editor): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

function publishedArticleIn(string $categoryKey, string $title = 'Άρθρο'): Article
{
    return Article::factory()->for(Author::factory())->create([
        'title' => $title,
        'category' => $categoryKey,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

/* ------------------------------------------------------------------ seeding */

it('imports the four categories that used to live in config', function () {
    expect(Category::count())->toBe(4);

    foreach (array_keys(config('arogi.categories')) as $key) {
        expect(Category::where('key', $key)->exists())->toBeTrue();
    }
});

it('is idempotent, so it is safe to re-run on production', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(4);
});

it('never undoes category edits made in the admin when re-deployed', function () {
    // The deploy script seeds on every release. If this overwrote, a renamed
    // or withdrawn category would silently snap back to the shipped defaults.
    $category = Category::where('key', 'parents')->firstOrFail();
    $category->update(['label' => 'Γονείς & Οικογένεια', 'color' => 'coral']);

    $withdrawn = Category::where('key', 'career')->firstOrFail();
    $withdrawn->forceFill(['archived_at' => now()])->save();

    $this->seed(CategorySeeder::class);

    expect($category->refresh()->label)->toBe('Γονείς & Οικογένεια')
        ->and($category->color)->toBe('coral')
        ->and($withdrawn->refresh()->isArchived())->toBeTrue();
});

/* ------------------------------------------------------------------ access */

it('refuses category management to a guest', function () {
    $this->getJson(route('admin.categories.index'))->assertUnauthorized();
});

it('lets any panel member manage categories', function () {
    // Editors write articles, so they need this.
    $this->actingAs(catUser(Role::Editor))
        ->getJson(route('admin.categories.index'))
        ->assertOk()
        ->assertJsonCount(4, 'categories');
});

it('addresses categories by key in its URLs, matching what the panel sends', function () {
    // Regression: the Alpine panel calls /admin/categories/<key>. With the
    // default id binding every edit and delete from the live panel 404s.
    $category = Category::where('key', 'parents')->firstOrFail();

    expect(route('admin.categories.update', $category))->toEndWith('/admin/categories/parents');

    $this->actingAs(catUser())
        ->putJson('/admin/categories/parents', [
            'label' => 'Γονείς', 'color' => 'yellow', 'motif' => 'group',
        ])->assertOk();
});

/* ------------------------------------------------------------------ create */

it('creates a category and transliterates the Greek label into a key', function () {
    $this->actingAs(catUser())->postJson(route('admin.categories.store'), [
        'label' => 'Εφηβεία',
        'color' => 'coral',
        'motif' => 'one',
    ])->assertOk()->assertJsonPath('created', 'efivia');

    $category = Category::where('key', 'efivia')->firstOrFail();

    expect($category->label)->toBe('Εφηβεία')
        ->and($category->color)->toBe('coral')
        ->and($category->motif)->toBe('one')
        ->and($category->isArchived())->toBeFalse();
});

it('keeps keys unique when two labels transliterate the same', function () {
    $user = catUser();

    $this->actingAs($user)->postJson(route('admin.categories.store'),
        ['label' => 'Εφηβεία', 'color' => 'green', 'motif' => 'book']);
    $this->actingAs($user)->postJson(route('admin.categories.store'),
        ['label' => 'Εφηβεία', 'color' => 'green', 'motif' => 'book'])
        ->assertOk()->assertJsonPath('created', 'efivia-2');
});

it('rejects a colour the design has no styling for', function () {
    $this->actingAs(catUser())->postJson(route('admin.categories.store'), [
        'label' => 'Δοκιμή',
        'color' => 'purple',
        'motif' => 'book',
    ])->assertStatus(422)->assertJsonValidationErrors('color');
});

it('rejects a motif the SVG generator does not know', function () {
    $this->actingAs(catUser())->postJson(route('admin.categories.store'), [
        'label' => 'Δοκιμή',
        'color' => 'green',
        'motif' => 'dragon',
    ])->assertStatus(422)->assertJsonValidationErrors('motif');
});

it('requires a label', function () {
    $this->actingAs(catUser())->postJson(route('admin.categories.store'), [
        'label' => '', 'color' => 'green', 'motif' => 'book',
    ])->assertStatus(422)->assertJsonValidationErrors('label');
});

it('makes a new category immediately usable on an article and the public blog', function () {
    $user = catUser();

    $this->actingAs($user)->postJson(route('admin.categories.store'), [
        'label' => 'Εφηβεία', 'color' => 'coral', 'motif' => 'one',
    ]);

    $this->actingAs($user)->post(route('admin.articles.store'), [
        'title' => 'Άρθρο για εφήβους',
        'category' => 'efivia',
        'is_published' => '1',
    ])->assertSessionHasNoErrors();

    $this->get(route('blog.index', ['category' => 'efivia']))
        ->assertOk()
        ->assertSeeText('Άρθρο για εφήβους')
        ->assertSeeText('Εφηβεία');
});

/* ------------------------------------------------------------------ update */

it('renames a category everywhere it is shown', function () {
    publishedArticleIn('parents', 'Άρθρο γονέων');
    $category = Category::where('key', 'parents')->firstOrFail();

    $this->actingAs(catUser())->putJson(route('admin.categories.update', $category), [
        'label' => 'Γονείς & Οικογένεια',
        'color' => 'yellow',
        'motif' => 'group',
    ])->assertOk();

    // The key never changes, so the article still points at it.
    expect($category->refresh()->key)->toBe('parents');

    $this->get(route('blog.index'))->assertOk()->assertSeeText('Γονείς & Οικογένεια');
});

it('changes the pill colour of the articles that use it', function () {
    $article = publishedArticleIn('parents');
    $category = Category::where('key', 'parents')->firstOrFail();

    expect($article->category_color)->toBe('yellow');

    $this->actingAs(catUser())->putJson(route('admin.categories.update', $category), [
        'label' => 'Γονείς', 'color' => 'coral', 'motif' => 'group',
    ])->assertOk();

    Categories::flush();

    expect($article->fresh()->category_color)->toBe('coral');
});

/* ------------------------------------------- delete / withdraw — the point */

it('deletes a category outright when no article uses it', function () {
    $category = Category::where('key', 'career')->firstOrFail();

    $this->actingAs(catUser())
        ->deleteJson(route('admin.categories.destroy', $category))
        ->assertOk();

    expect(Category::where('key', 'career')->exists())->toBeFalse();
});

it('withdraws instead of deleting when articles use it, and touches no article', function () {
    $article = publishedArticleIn('learning', 'Άρθρο για δυσλεξία');
    $category = Category::where('key', 'learning')->firstOrFail();

    $this->actingAs(catUser())
        ->deleteJson(route('admin.categories.destroy', $category))
        ->assertOk();

    // The row survives, flagged.
    expect($category->refresh()->isArchived())->toBeTrue();

    // The article survives, still in the same category.
    $article->refresh();
    expect(Article::whereKey($article->getKey())->exists())->toBeTrue()
        ->and($article->category)->toBe('learning');
});

it('keeps the label, colour and motif of an article whose category was withdrawn', function () {
    $article = publishedArticleIn('learning', 'Άρθρο για δυσλεξία');
    $category = Category::where('key', 'learning')->firstOrFail();

    $before = [$article->category_label, $article->category_color, $article->cover_motif];

    $this->actingAs(catUser())->deleteJson(route('admin.categories.destroy', $category));
    Categories::flush();

    $article->refresh();

    // Exactly as it was chosen — this is the requirement.
    expect([$article->category_label, $article->category_color, $article->cover_motif])
        ->toBe($before)
        ->and($article->category_label)->toBe('Μαθησιακές δυσκολίες');

    // And it still renders that way publicly.
    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Μαθησιακές δυσκολίες');
});

it('drops a withdrawn category from the public filter chips', function () {
    publishedArticleIn('learning');
    $category = Category::where('key', 'learning')->firstOrFail();

    $this->get(route('blog.index'))->assertOk()->assertSeeText('Μαθησιακές δυσκολίες');

    $this->actingAs(catUser())->deleteJson(route('admin.categories.destroy', $category));
    Categories::flush();

    // No longer a chip, but the article's own pill still reads the same.
    $html = $this->get(route('blog.index'))->assertOk()->getContent();

    expect(substr_count($html, 'Μαθησιακές δυσκολίες'))->toBe(1);
});

it('stops offering a withdrawn category for new articles', function () {
    publishedArticleIn('learning');
    $category = Category::where('key', 'learning')->firstOrFail();
    $user = catUser();

    $this->actingAs($user)->deleteJson(route('admin.categories.destroy', $category));
    Categories::flush();

    $this->actingAs($user)
        ->get(route('admin.articles.create'))
        ->assertOk()
        ->assertDontSee('Μαθησιακές δυσκολίες');
});

it('still offers a withdrawn category when editing the article that carries it', function () {
    // Otherwise opening an old article and saving it would silently blank the
    // category.
    $article = publishedArticleIn('learning');
    $category = Category::where('key', 'learning')->firstOrFail();
    $user = catUser();

    $this->actingAs($user)->deleteJson(route('admin.categories.destroy', $category));
    Categories::flush();

    $this->actingAs($user)
        ->get(route('admin.articles.edit', $article))
        ->assertOk()
        ->assertSee('Μαθησιακές δυσκολίες', false);

    // And saving keeps it.
    $this->actingAs($user)->put(route('admin.articles.update', $article), [
        'title' => $article->title,
        'category' => 'learning',
        'is_published' => '1',
    ])->assertSessionHasNoErrors();

    expect($article->refresh()->category)->toBe('learning');
});

it('brings a withdrawn category back', function () {
    publishedArticleIn('learning');
    $category = Category::where('key', 'learning')->firstOrFail();
    $user = catUser();

    $this->actingAs($user)->deleteJson(route('admin.categories.destroy', $category));
    $this->actingAs($user)->postJson(route('admin.categories.restore', $category))->assertOk();

    expect($category->refresh()->isArchived())->toBeFalse();

    Categories::flush();
    $this->get(route('blog.index'))->assertOk()->assertSeeText('Μαθησιακές δυσκολίες');
});

it('reports how many articles each category has', function () {
    publishedArticleIn('parents');
    publishedArticleIn('parents', 'Δεύτερο');

    $response = $this->actingAs(catUser())->getJson(route('admin.categories.index'))->assertOk();

    $parents = collect($response->json('categories'))->firstWhere('key', 'parents');

    expect($parents['articles'])->toBe(2);
});

/* --------------------------------------------------------------- rendering */

it('mounts the manage panel on the article form', function () {
    $this->actingAs(catUser())
        ->get(route('admin.articles.create'))
        ->assertOk()
        // The Alpine component is mounted. (The endpoint URL itself is
        // JSON-escaped by @js, so assert on the component instead.)
        ->assertSee('categoryPicker(', false)
        ->assertSee('admin\/categories', false)
        // Real text inside the panel.
        ->assertSeeText('Νέα κατηγορία')
        // The four seeded categories are handed to it as initial state.
        ->assertSee('Μαθησιακές δυσκολίες', false);
});
