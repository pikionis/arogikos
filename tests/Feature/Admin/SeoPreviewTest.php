<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| SEO preview in the panel
|--------------------------------------------------------------------------
| Authors could type a twelve-character title with no way of knowing it was
| too short — which is exactly how the public pages ended up with titles like
| "Blog — Αρωγή". The preview shows the snippet and counts against the
| windows Google displays.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function seoUser(Role $role = Role::Owner): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

it('shows the preview when writing a new article', function () {
    $this->actingAs(seoUser(Role::Editor))
        ->get(route('admin.articles.create'))
        ->assertOk()
        ->assertSee('seoPreview(', false)
        ->assertSeeText('Πώς θα φαίνεται στη Google')
        ->assertSee('50–60', false)
        ->assertSee('150–160', false);
});

it('tells the preview which fields to fall back to', function () {
    // Leaving the SEO fields blank is normal; the preview has to show what a
    // searcher would actually see, which is the title and the excerpt.
    $html = $this->actingAs(seoUser(Role::Editor))
        ->get(route('admin.articles.create'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain("fallbackTitle: 'title'")
        ->toContain("fallbackDescription: 'excerpt'");
});

it('shows the preview when editing an existing article', function () {
    $article = Article::factory()->by(Author::factory()->create())->create();

    $this->actingAs(seoUser(Role::Editor))
        ->get(route('admin.articles.edit', $article))
        ->assertOk()
        ->assertSee('seoPreview(', false);
});

it('shows the preview on the SEO group of every page', function (string $pageKey) {
    $this->actingAs(seoUser())
        ->get(route('admin.pages.edit', $pageKey))
        ->assertOk()
        ->assertSee('seoPreview(', false)
        ->assertSee('fields[seo.title]', false);
})->with(['home', 'about', 'services', 'blog', 'events', 'gallery', 'contact']);

it('does not put a preview on a page group that has no SEO fields', function () {
    // "shared" holds navigation and the services, not page metadata.
    $this->actingAs(seoUser())
        ->get(route('admin.pages.edit', 'shared'))
        ->assertOk()
        ->assertDontSee('seoPreview(', false);
});

it('never leaks the preview into the public site', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('seoPreview', false);
});
