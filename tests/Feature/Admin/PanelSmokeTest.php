<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Album;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\Event;
use App\Models\User;
use App\Models\User as UserModel;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Panel smoke test
|--------------------------------------------------------------------------
| Every admin screen must render. Blade typos and missing view variables are
| invisible until something actually renders the template, and the panel has
| a lot of templates.
*/

function panelOwner(): UserModel
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    return $user->refresh();
}

it('renders every admin screen for an owner', function (string $routeName) {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(panelOwner())
        ->get(route($routeName))
        ->assertOk();
})->with([
    'dashboard' => 'admin.dashboard',
    'articles.index' => 'admin.articles.index',
    'articles.create' => 'admin.articles.create',
    'events.index' => 'admin.events.index',
    'events.create' => 'admin.events.create',
    'authors.index' => 'admin.authors.index',
    'authors.create' => 'admin.authors.create',
    'users.index' => 'admin.users.index',
    'gallery.index' => 'admin.gallery.index',
    'gallery.create' => 'admin.gallery.create',
    'messages.index' => 'admin.messages.index',
    'settings.edit' => 'admin.settings.edit',
    'profile.edit' => 'admin.profile.edit',
]);

it('renders every edit screen for an owner', function () {
    $this->seed(DatabaseSeeder::class);
    $owner = panelOwner();

    $article = Article::firstOrFail();
    $event = Event::firstOrFail();
    $author = Author::firstOrFail();

    $this->actingAs($owner)->get(route('admin.articles.edit', $article))->assertOk();
    $this->actingAs($owner)->get(route('admin.events.edit', $event))->assertOk();
    $this->actingAs($owner)->get(route('admin.authors.edit', $author))->assertOk();
    $this->actingAs($owner)->get(route('admin.gallery.edit', Album::firstOrFail()))->assertOk();
});

it('renders the guest auth screens', function (string $routeName) {
    $this->seed(SettingsSeeder::class);

    $this->get(route($routeName))->assertOk();
})->with([
    'login' => 'admin.login',
    'password.request' => 'admin.password.request',
]);

it('renders every admin screen an editor is allowed to see', function (string $routeName) {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $this->actingAs($user->refresh())->get(route($routeName))->assertOk();
})->with([
    'dashboard' => 'admin.dashboard',
    'articles.index' => 'admin.articles.index',
    'articles.create' => 'admin.articles.create',
    'events.index' => 'admin.events.index',
    'messages.index' => 'admin.messages.index',
    'profile.edit' => 'admin.profile.edit',
]);

/* ------------------------------------------------- whose profile is whose */

it('keeps an editor out of everybody else\'s profile', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();
    $editor = $user->refresh();

    $someoneElse = Author::firstOrFail();

    // Writing a colleague's bio and photo is not an editor's business, and
    // the list is also where authors get deleted.
    $this->actingAs($editor)->get(route('admin.authors.index'))->assertForbidden();
    $this->actingAs($editor)->get(route('admin.authors.edit', $someoneElse))->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.authors.destroy', $someoneElse))->assertForbidden();
});

it('hides the authors link from an editor sidebar', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $this->actingAs($user->refresh())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.authors.index'));
});

it('still lets an owner reach the authors list', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(panelOwner())->get(route('admin.authors.index'))->assertOk();
});
