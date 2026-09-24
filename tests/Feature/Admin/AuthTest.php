<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| Admin authentication
|--------------------------------------------------------------------------
| The panel has no registration route by design, so these cover the only two
| ways in: the login form and (elsewhere) an invitation link.
*/

beforeEach(function () {
    // The guest layout reads setting('org_name').
    $this->seed(SettingsSeeder::class);
    RateLimiter::clear('');
});

function owner(array $attributes = []): User
{
    $user = User::factory()->create($attributes + ['password' => Hash::make('correct-horse-battery')]);
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    return $user->refresh();
}

/* ------------------------------------------------------------------ guard */

it('redirects a guest from the dashboard to the admin login', function () {
    // Regression guard: without redirectGuestsTo() this throws
    // RouteNotFoundException (no route named "login") and 500s.
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('shows the login form to a guest', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSeeText('Είσοδος');
});

it('keeps the admin panel out of search engines', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

/* ------------------------------------------------------------------ login */

it('signs in a valid active user', function () {
    $user = owner(['email' => 'petros@example.com']);

    $this->post(route('admin.login.store'), [
        'email' => 'petros@example.com',
        'password' => 'correct-horse-battery',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('accepts the email case-insensitively', function () {
    owner(['email' => 'petros@example.com']);

    $this->post(route('admin.login.store'), [
        'email' => 'Petros@Example.COM',
        'password' => 'correct-horse-battery',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticated();
});

it('stamps last_login_at on a successful sign-in', function () {
    $user = owner();

    expect($user->last_login_at)->toBeNull();

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery',
    ]);

    expect($user->refresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password without saying why', function () {
    $user = owner();

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'not-the-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
});

it('gives an unknown email the very same message as a wrong password', function () {
    // Anything more specific enumerates which addresses have accounts.
    $this->post(route('admin.login.store'), [
        'email' => 'nobody@example.com',
        'password' => 'whatever',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
    $this->assertGuest();
});

it('refuses to sign in a deactivated account', function () {
    $user = owner();
    $user->forceFill(['is_active' => false])->save();

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('regenerates the session id on login to defeat fixation', function () {
    $user = owner();

    $this->get(route('admin.login'));
    $before = session()->getId();

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'correct-horse-battery',
    ]);

    expect(session()->getId())->not->toBe($before);
});

/* --------------------------------------------------------------- throttle */

it('throttles repeated failed logins for the same address', function () {
    $user = owner();

    foreach (range(1, 5) as $ignored) {
        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});

/* ----------------------------------------------------------------- logout */

it('logs out and invalidates the session', function () {
    $user = owner();

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

/* ------------------------------------------------- deactivated mid-session */

it('kicks out a user deactivated while signed in', function () {
    $user = owner();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

/* -------------------------------------------------------------- dashboard */

it('shows the dashboard to a signed-in user', function () {
    $this->actingAs(owner())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeText('Επισκόπηση');
});
