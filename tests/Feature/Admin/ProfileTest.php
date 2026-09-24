<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| "Το προφίλ μου"
|--------------------------------------------------------------------------
| Production runs MAIL_MAILER=log, so forgot-password delivers nothing. This
| screen is the only route by which anyone can change the password they were
| handed — and it must not become a way to hand yourself a bigger role.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function profileUser(Role $role = Role::Editor): User
{
    $user = User::factory()->create(['password' => Hash::make('the-old-password')]);
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

it('is reachable by every role, editors included', function (string $role) {
    $this->actingAs(profileUser(Role::from($role)))
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSeeText('Αλλαγή κωδικού');
})->with(['owner', 'admin', 'editor']);

it('redirects a guest to the login', function () {
    $this->get(route('admin.profile.edit'))->assertRedirect(route('admin.login'));
});

/* ------------------------------------------------------------------ details */

it('updates name and email', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.update'), [
        'name' => 'Νέο Όνομα',
        'email' => 'Neo@Example.com',
    ])->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Νέο Όνομα')
        // Lower-cased so the login lookup matches.
        ->and($user->email)->toBe('neo@example.com');
});

it('refuses an email another account already uses', function () {
    $user = profileUser();
    User::factory()->create(['email' => 'piasmeno@example.com']);

    $this->actingAs($user)
        ->put(route('admin.profile.update'), ['name' => 'Κάποιος', 'email' => 'piasmeno@example.com'])
        ->assertSessionHasErrors('email');
});

it('cannot be used to promote yourself', function () {
    $user = profileUser(Role::Editor);

    $this->actingAs($user)->put(route('admin.profile.update'), [
        'name' => 'Επίδοξος',
        'email' => $user->email,
        'role' => 'owner',
        'is_active' => true,
    ]);

    expect($user->refresh()->role)->toBe(Role::Editor);
});

/* ----------------------------------------------------------------- password */

it('changes the password when the current one is given', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'the-old-password',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect();

    expect(Hash::check('a-brand-new-password', $user->refresh()->password))->toBeTrue();
});

it('lets the new password actually sign in', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'the-old-password',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ]);

    auth()->logout();

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'a-brand-new-password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticated();
});

it('refuses without the correct current password', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'wrong',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('the-old-password', $user->refresh()->password))->toBeTrue();
});

it('requires the new password to be confirmed', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'the-old-password',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'something-different',
    ])->assertSessionHasErrors('password');
});

it('keeps you signed in after changing your own password', function () {
    $user = profileUser();

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'the-old-password',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ]);

    $this->assertAuthenticatedAs($user);
    $this->get(route('admin.dashboard'))->assertOk();
});

it('rotates the remember token so other devices are signed out', function () {
    $user = profileUser();
    $before = $user->remember_token;

    $this->actingAs($user)->put(route('admin.profile.password'), [
        'current_password' => 'the-old-password',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ]);

    expect($user->refresh()->remember_token)->not->toBe($before);
});
