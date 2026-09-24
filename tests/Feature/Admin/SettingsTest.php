<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Site settings
|--------------------------------------------------------------------------
| The brief requires zero hard-coded contact details: everything the footer
| and contact page show comes from here.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function settingsUser(Role $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

it('forbids an editor from the settings screen', function () {
    $this->actingAs(settingsUser(Role::Editor))
        ->get(route('admin.settings.edit'))
        ->assertForbidden();
});

it('lets an admin open the settings screen', function () {
    $this->actingAs(settingsUser(Role::Admin))
        ->get(route('admin.settings.edit'))
        ->assertOk();
});

it('saves a setting and shows it on the public site immediately', function () {
    $this->actingAs(settingsUser(Role::Owner))->put(route('admin.settings.update'), [
        'settings' => [
            'org_name' => 'Αρωγή Κω',
            'phone' => '22420 11 111',
            'email' => 'nea@arogikos.gr',
        ],
    ])->assertRedirect();

    // The settings blob is cached forever; Setting::booted() must bust it.
    expect(setting('org_name'))->toBe('Αρωγή Κω');

    $this->get(route('contact'))->assertOk()->assertSeeText('22420 11 111');
});

it('rejects an invalid email', function () {
    $this->actingAs(settingsUser(Role::Owner))
        ->put(route('admin.settings.update'), [
            'settings' => ['email' => 'not-an-email'],
        ])->assertSessionHasErrors('settings.email');
});

it('rejects an invalid url', function () {
    $this->actingAs(settingsUser(Role::Owner))
        ->put(route('admin.settings.update'), [
            'settings' => ['instagram' => 'not a url'],
        ])->assertSessionHasErrors('settings.instagram');
});

it('lets a setting be cleared', function () {
    $this->actingAs(settingsUser(Role::Owner))->put(route('admin.settings.update'), [
        'settings' => ['ga_id' => ''],
    ]);

    // An empty ga_id must keep the analytics snippet off the public pages.
    $this->get(route('home'))->assertOk()->assertDontSee('googletagmanager', false);
});

it('turns analytics on when a ga_id is saved', function () {
    $this->actingAs(settingsUser(Role::Owner))->put(route('admin.settings.update'), [
        'settings' => ['ga_id' => 'G-ADMIN123'],
    ]);

    $this->get(route('home'))->assertOk()->assertSee('G-ADMIN123', false);
});

it('covers every key the seeder writes', function () {
    // A seeded key missing from the registry would be invisible in the admin
    // and silently un-editable.
    $registryKeys = collect(config('cms-settings'))
        ->flatMap(fn (array $group) => collect($group['fields'])->pluck('key'))
        ->all();

    $seededKeys = Setting::query()->pluck('key')->all();

    expect(array_diff($seededKeys, $registryKeys))->toBe([]);
});
