<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Invitation acceptance
|--------------------------------------------------------------------------
| Accounts exist only by invitation — there is no registration route — so
| this token flow is the entire attack surface for creating a user.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function issueInvitation(Role $role = Role::Editor, array $overrides = []): array
{
    [$invitation, $token] = Invitation::issue(
        $overrides['email'] ?? 'nea@example.com',
        $overrides['name'] ?? 'Νέα Συνεργάτιδα',
        $role,
    );

    return [$invitation, $token];
}

/* ------------------------------------------------------------------ token */

it('stores only a hash of the token, never the token itself', function () {
    [$invitation, $token] = issueInvitation();

    expect($invitation->token_hash)
        ->not->toBe($token)
        ->toBe(hash('sha256', $token));

    $this->assertDatabaseMissing('invitations', ['token_hash' => $token]);
});

it('shows the acceptance form for a valid token', function () {
    [$invitation, $token] = issueInvitation();

    $this->get(route('admin.invitation.show', $token))
        ->assertOk()
        ->assertSeeText($invitation->email);
});

it('404s on a token that does not exist', function () {
    $this->get(route('admin.invitation.show', str_repeat('a', 64)))
        ->assertNotFound();
});

it('404s on an expired token', function () {
    [$invitation, $token] = issueInvitation();
    $invitation->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->get(route('admin.invitation.show', $token))->assertNotFound();
});

it('404s on a token that was already used', function () {
    [$invitation, $token] = issueInvitation();
    $invitation->forceFill(['accepted_at' => now()])->save();

    $this->get(route('admin.invitation.show', $token))->assertNotFound();
});

/* ------------------------------------------------------------- acceptance */

it('creates the account, signs it in and marks the invitation used', function () {
    [$invitation, $token] = issueInvitation();

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Ελένη Παπαδάκη',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ])->assertRedirect(route('admin.dashboard'));

    $user = User::where('email', 'nea@example.com')->firstOrFail();

    expect($user->name)->toBe('Ελένη Παπαδάκη')
        ->and($user->role)->toBe(Role::Editor)
        ->and($user->is_active)->toBeTrue();

    $this->assertAuthenticatedAs($user);
    expect($invitation->refresh()->accepted_at)->not->toBeNull();
});

it('grants the role recorded on the invitation, not one posted by the visitor', function () {
    [, $token] = issueInvitation(Role::Editor);

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Επίδοξος Ιδιοκτήτης',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
        // Privilege escalation attempt — `role` is not mass-assignable and the
        // controller reads it off the invitation row.
        'role' => 'owner',
        'is_active' => true,
    ]);

    expect(User::where('email', 'nea@example.com')->firstOrFail()->role)
        ->toBe(Role::Editor);
});

it('creates an INACTIVE author profile so invitees are not published on the About page', function () {
    [, $token] = issueInvitation();

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Ελένη Παπαδάκη',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ]);

    $user = User::where('email', 'nea@example.com')->firstOrFail();
    $author = Author::where('user_id', $user->getKey())->firstOrFail();

    expect($author->is_active)->toBeFalse()
        ->and($author->name)->toBe('Ελένη Παπαδάκη');

    // And the public team grid must not show them.
    $this->get(route('about'))->assertOk()->assertDontSeeText('Ελένη Παπαδάκη');
});

it('refuses to reuse a token', function () {
    [, $token] = issueInvitation();

    $payload = [
        'name' => 'Πρώτη Χρήση',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ];

    $this->post(route('admin.invitation.accept', $token), $payload);

    // Fresh session, same link.
    auth()->logout();

    $this->post(route('admin.invitation.accept', $token), $payload)->assertNotFound();

    expect(User::where('email', 'nea@example.com')->count())->toBe(1);
});

it('burns other pending invitations for the same address', function () {
    [$first, $firstToken] = issueInvitation();
    [$second] = issueInvitation(Role::Editor);

    $this->post(route('admin.invitation.accept', $firstToken), [
        'name' => 'Ελένη Παπαδάκη',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ]);

    expect($second->refresh()->isPending())->toBeFalse();
});

it('requires a confirmed password', function () {
    [, $token] = issueInvitation();

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Ελένη Παπαδάκη',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'nea@example.com']);
});

it('rejects acceptance when the address was registered in the meantime', function () {
    [, $token] = issueInvitation();

    User::factory()->create(['email' => 'nea@example.com']);

    $this->post(route('admin.invitation.accept', $token), [
        'name' => 'Ελένη Παπαδάκη',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
    ])->assertSessionHasErrors('name');

    expect(User::where('email', 'nea@example.com')->count())->toBe(1);
});
