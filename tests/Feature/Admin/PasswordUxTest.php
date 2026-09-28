<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Password;

/*
|--------------------------------------------------------------------------
| Να βλέπεις τι πληκτρολογείς, και να βρίσκεις το προφίλ σου
|--------------------------------------------------------------------------
| Ένας κωδικός που ορίζεις στα τυφλά, δύο φορές, με έναν χαρακτήρα λάθος,
| σε στέλνει πίσω χωρίς να ξέρεις γιατί. Το κουμπί εμφάνισης δεν είναι
| ευκολία — είναι ο λόγος που δεν θα κλειδωθεί κανείς έξω.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function everyPasswordScreen(): array
{
    return ['admin.login', 'admin.password.request'];
}

/* ------------------------------------------------------- show the password */

it('lets you see the password you are setting, on the invitation', function () {
    [, $token] = Invitation::issue('neos@example.com', 'Νέος Συντάκτης', Role::Editor);

    $html = $this->get(route('admin.invitation.show', $token))->assertOk()->getContent();

    // Both fields, each with its own toggle.
    expect(substr_count($html, 'a-password-toggle'))->toBe(2)
        ->and($html)->toContain('Εμφάνιση');
});

it('lets you see it on the reset screen too', function () {
    $user = User::factory()->create(['email' => 'kapoios@example.com']);
    $token = Password::broker()->createToken($user);

    $html = $this->get(route('admin.password.reset', $token))->assertOk()->getContent();

    expect(substr_count($html, 'a-password-toggle'))->toBe(2);
});

it('lets you see it when changing it from your profile', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $html = $this->actingAs($user->refresh())
        ->get(route('admin.profile.edit'))->assertOk()->getContent();

    // Current, new, and confirmation.
    expect(substr_count($html, 'a-password-toggle'))->toBe(3);
});

it('lets you see it on the login screen', function () {
    $html = $this->get(route('admin.login'))->assertOk()->getContent();

    expect(substr_count($html, 'a-password-toggle'))->toBe(1);
});

it('starts hidden even if the script never runs', function () {
    [, $token] = Invitation::issue('neos@example.com', 'Νέος', Role::Editor);

    $html = $this->get(route('admin.invitation.show', $token))->getContent();

    // type="password" is in the markup and only then bound: a slow or blocked
    // script must not leave the field readable over someone's shoulder.
    expect($html)->toContain('type="password"')
        ->and($html)->toContain(':type="shown ? \'text\' : \'password\'"');
});

it('says which state the button will put you in', function () {
    $html = $this->get(route('admin.login'))->getContent();

    // A word, not an eye: the icon is ambiguous about whether it shows the
    // current state or what pressing it does.
    expect($html)->toContain(':aria-pressed="shown"')
        ->and($html)->toContain("shown ? 'Απόκρυψη' : 'Εμφάνιση'");
});

it('still tells a password manager which kind of field this is', function () {
    $html = $this->get(route('admin.login'))->getContent();

    expect($html)->toContain('autocomplete="current-password"');

    [, $token] = Invitation::issue('neos@example.com', 'Νέος', Role::Editor);
    $invite = $this->get(route('admin.invitation.show', $token))->getContent();

    expect($invite)->toContain('autocomplete="new-password"');
});

/* ------------------------------------------------------ finding the profile */

it('makes the profile in the topbar look like something you can press', function () {
    $user = User::factory()->create(['name' => 'Άννα Γεωργαντή']);
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $html = $this->actingAs($user->refresh())
        ->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('a-profile-link')
        // Initials, so it reads as a person even with the name hidden on a
        // narrow screen.
        ->and($html)->toContain('>ΑΓ<')
        ->and($html)->toContain('a-profile-go')
        // The old version said what it was only in a tooltip, which does not
        // exist on a touch screen.
        ->and($html)->toContain('Το προφίλ μου')
        ->and($html)->not->toContain('title="Το προφίλ μου"');
});

it('marks the profile as the current page while you are on it', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $this->actingAs($user->refresh())
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});

it('drops the tonos from Greek initials', function () {
    $user = User::factory()->create(['name' => 'Άννα Ίριδα']);

    // Greek uppercase loses the accent; "ΆΊ" would be wrong.
    expect($user->initials())->toBe('ΑΙ');
});
