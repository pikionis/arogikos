<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Author;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\PanelInvitation;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Accounts, roles and the lockout guards
|--------------------------------------------------------------------------
| The panel has no registration route, so if the last owner can demote,
| deactivate or delete themselves there is no way back in. Those guards are
| the point of most of this file.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Notification::fake();
});

function userWithRole(Role $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

/* -------------------------------------------------------------- visibility */

it('lets an owner open the users screen', function () {
    $this->actingAs(userWithRole(Role::Owner))
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSeeText('Πρόσκληση μέλους');
});

it('forbids an editor from the users screen', function () {
    $this->actingAs(userWithRole(Role::Editor))
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('hides the Χρήστες link from an editor', function () {
    $this->actingAs(userWithRole(Role::Editor))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.users.index'), false);
});

/* ----------------------------------------------------------- invitations */

it('lets an owner invite an editor and emails the link', function () {
    $owner = userWithRole(Role::Owner);

    $this->actingAs($owner)->post(route('admin.users.invite'), [
        'name' => 'Νέος Συντάκτης',
        'email' => 'Neos@Example.com',
        'role' => 'editor',
    ])->assertRedirect();

    // Stored lower-cased so the login lookup matches.
    $this->assertDatabaseHas('invitations', [
        'email' => 'neos@example.com',
        'role' => 'editor',
    ]);

    Notification::assertSentOnDemand(PanelInvitation::class);
});

it('forbids an admin from inviting another admin', function () {
    // Role::Admin->assignableRoles() is [Editor] only.
    $this->actingAs(userWithRole(Role::Admin))
        ->post(route('admin.users.invite'), [
            'name' => 'Επίδοξος Διαχειριστής',
            'email' => 'admin2@example.com',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('invitations', ['email' => 'admin2@example.com']);
});

it('forbids anyone from inviting an owner', function () {
    $this->actingAs(userWithRole(Role::Owner))
        ->post(route('admin.users.invite'), [
            'name' => 'Δεύτερος Ιδιοκτήτης',
            'email' => 'owner2@example.com',
            'role' => 'owner',
        ])->assertSessionHasErrors('role');
});

it('refuses to invite an address that already has an account', function () {
    $owner = userWithRole(Role::Owner);
    User::factory()->create(['email' => 'yparxei@example.com']);

    $this->actingAs($owner)->post(route('admin.users.invite'), [
        'name' => 'Διπλό',
        'email' => 'yparxei@example.com',
        'role' => 'editor',
    ])->assertSessionHasErrors('email');
});

it('supersedes an earlier pending invitation for the same address', function () {
    $owner = userWithRole(Role::Owner);

    [$first] = Invitation::issue('idia@example.com', 'Πρώτη', Role::Editor, $owner);

    $this->actingAs($owner)->post(route('admin.users.invite'), [
        'name' => 'Δεύτερη',
        'email' => 'idia@example.com',
        'role' => 'editor',
    ]);

    expect($first->refresh()->isPending())->toBeFalse()
        ->and(Invitation::pending()->where('email', 'idia@example.com')->count())->toBe(1);
});

it('forbids an editor from inviting anyone', function () {
    $this->actingAs(userWithRole(Role::Editor))
        ->post(route('admin.users.invite'), [
            'name' => 'Λαθραίος',
            'email' => 'x@example.com',
            'role' => 'editor',
        ])->assertForbidden();
});

/* -------------------------------------------------------- lockout guards */

it('stops the sole owner from demoting themselves', function () {
    $owner = userWithRole(Role::Owner);

    $this->actingAs($owner)
        ->patch(route('admin.users.role', $owner), ['role' => 'editor'])
        ->assertForbidden();

    expect($owner->refresh()->role)->toBe(Role::Owner);
});

it('stops an owner from demoting themselves even when another owner exists', function () {
    // Changing your OWN role is never allowed — it has no legitimate use and
    // is the classic way to lose access.
    $owner = userWithRole(Role::Owner);
    userWithRole(Role::Owner, ['email' => 'second-owner@example.com']);

    $this->actingAs($owner)
        ->patch(route('admin.users.role', $owner), ['role' => 'editor'])
        ->assertForbidden();
});

it('stops the sole owner from deactivating themselves', function () {
    $owner = userWithRole(Role::Owner);

    $this->actingAs($owner)
        ->patch(route('admin.users.active', $owner))
        ->assertRedirect();

    expect($owner->refresh()->is_active)->toBeTrue();
});

it('stops the sole owner from deleting themselves', function () {
    $owner = userWithRole(Role::Owner);

    $this->actingAs($owner)
        ->delete(route('admin.users.destroy', $owner))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $owner->getKey()]);
});

it('stops an admin from deleting an owner', function () {
    $admin = userWithRole(Role::Admin);
    $owner = userWithRole(Role::Owner, ['email' => 'o@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $owner))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $owner->getKey()]);
});

it('stops an admin from deleting a fellow admin', function () {
    $admin = userWithRole(Role::Admin);
    $peer = userWithRole(Role::Admin, ['email' => 'peer@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $peer))
        ->assertForbidden();
});

/* ---------------------------------------------------- permitted changes */

it('lets an owner deactivate an editor', function () {
    $owner = userWithRole(Role::Owner);
    $editor = userWithRole(Role::Editor, ['email' => 'e@example.com']);

    $this->actingAs($owner)->patch(route('admin.users.active', $editor));

    expect($editor->refresh()->is_active)->toBeFalse();
});

it('lets an owner promote an editor to admin', function () {
    $owner = userWithRole(Role::Owner);
    $editor = userWithRole(Role::Editor, ['email' => 'e@example.com']);

    $this->actingAs($owner)->patch(route('admin.users.role', $editor), ['role' => 'admin']);

    expect($editor->refresh()->role)->toBe(Role::Admin);
});

it('lets an owner delete an editor and keeps their author profile for the byline', function () {
    $owner = userWithRole(Role::Owner);
    $editor = userWithRole(Role::Editor, ['email' => 'e@example.com']);

    $author = Author::create([
        'user_id' => $editor->getKey(),
        'name' => 'Ο Συντάκτης',
        'is_active' => true,
    ]);

    $this->actingAs($owner)->delete(route('admin.users.destroy', $editor));

    $this->assertDatabaseMissing('users', ['id' => $editor->getKey()]);

    // authors.user_id is nullOnDelete — the profile (and any article byline
    // pointing at it) survives the account being removed.
    expect($author->refresh()->user_id)->toBeNull()
        ->and($author->name)->toBe('Ο Συντάκτης');
});

/*
|--------------------------------------------------------------------------
| Πώς λέγεται ο ρόλος
|--------------------------------------------------------------------------
*/

it('calls the top role Admin, not the owner of the centre', function () {
    $boss = userWithRole(Role::Owner, ['name' => 'Petros Pikionis']);

    $html = $this->actingAs($boss)->get(route('admin.users.index'))->assertOk()->getContent();

    expect($html)->toContain('Admin')
        ->and($html)->not->toContain('Ιδιοκτήτης');

    // Wherever the panel names your role back to you.
    $this->actingAs($boss)->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('Admin')
        ->assertDontSee('Ιδιοκτήτης');
});

it('never offers that role when someone hands one out', function () {
    // Owner is not in anyone's assignable list, so "Admin" and "Διαχειριστής"
    // can never sit in the same dropdown looking like the same word.
    foreach (Role::cases() as $role) {
        expect($role->assignableRoles())->not->toContain(Role::Owner);
    }
});
