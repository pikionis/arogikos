<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Mail\ContactMessageMail;
use App\Models\Cms\ContactMessage;
use App\Models\Cms\Setting;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\PanelInvitation;
use App\Support\Settings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Who the site writes as, and where replies land
|--------------------------------------------------------------------------
| The centre uses one address: arogicenter@gmail.com. It must be the only one
| a visitor ever sees, and the only one enquiries reach.
|
| The envelope sender is the exception, and deliberately so: gmail.com's SPF
| authorises Google's servers only, so sending as @gmail.com from this host
| would look like spoofing and be filtered harder than sending as our own
| domain. Hence noreply@arogikos.gr as the sender, with Reply-To pointing at
| the real inbox.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

it('ships a sender on our own domain, never the centre gmail', function (string $envFile) {
    // Assert the templates that actually get deployed, not the runtime config,
    // which varies with whatever a developer has in their local .env.
    $contents = file_get_contents(base_path($envFile));

    preg_match('/^MAIL_FROM_ADDRESS="?([^"
]+)"?/m', $contents, $m);

    expect($m[1] ?? null)
        ->toEndWith('@arogikos.gr')
        // gmail.com's SPF authorises Google's servers only; sending as the
        // centre's own address from this host would read as spoofing.
        ->not->toContain('gmail.com');
})->with(['.env.example', '.env.production']);

it('seeds the centre address as both the public email and the form recipient', function () {
    expect(setting('email'))->toBe('arogicenter@gmail.com')
        ->and(setting('contact_form_recipient'))->toBe('arogicenter@gmail.com');
});

it('shows no other contact address anywhere on the public site', function (string $routeName) {
    $html = $this->get(route($routeName))->assertOk()->getContent();

    // Strip the form field's placeholder, which is an illustration, not an
    // address the centre can be reached at.
    $html = str_replace('email@example.com', '', $html);

    preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $html, $m);

    expect(array_values(array_unique($m[0])))
        ->toBeIn([[], ['arogicenter@gmail.com']]);
})->with(['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact']);

it('delivers a contact form enquiry to the centre', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Γονέας',
        'email' => 'goneas@example.com',
        'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
    ])->assertRedirect(route('contact'));

    Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('arogicenter@gmail.com'));
});

it('lets the centre reply straight to the visitor', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Γονέας',
        'email' => 'goneas@example.com',
        'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
    ]);

    Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasReplyTo('goneas@example.com'));
});

it('follows the recipient when it is changed in the panel', function () {
    Mail::fake();

    Setting::updateOrCreate(['key' => 'contact_form_recipient'], ['value' => 'allo@arogikos.gr']);
    Settings::flush();

    $this->post(route('contact.store'), [
        'name' => 'Γονέας',
        'email' => 'goneas@example.com',
        'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
    ]);

    Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('allo@arogikos.gr'));
});

it('points invitation replies at the centre, not the noreply sender', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $owner->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    [$invitation, $token] = Invitation::issue('neos@example.com', 'Νέος', Role::Editor, $owner->refresh());

    $mail = (new PanelInvitation($invitation, route('admin.invitation.show', $token)))
        ->toMail($owner);

    // arogikos.gr has no MX, so a reply to the sender would bounce.
    expect($mail->replyTo)->toBe([['arogicenter@gmail.com', null]]);
});

it('keeps the enquiry in the panel even when mail cannot be sent', function () {
    // The safety net that matters: an enquiry is never lost to a mail problem.
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('SMTP down'));

    $this->post(route('contact.store'), [
        'name' => 'Γονέας',
        'email' => 'goneas@example.com',
        'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
    ])->assertRedirect(route('contact'));

    expect(ContactMessage::where('email', 'goneas@example.com')->exists())->toBeTrue();
});
