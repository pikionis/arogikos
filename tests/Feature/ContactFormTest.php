<?php

declare(strict_types=1);

use App\Mail\ContactMessageMail;
use App\Models\Cms\ContactMessage;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;

it('shows the contact form on GET', function () {
    $response = $this->get(route('contact'));

    $response->assertOk();
    $response->assertSee('Στείλτε μας μήνυμα', escape: false);
});

it('stores a valid submission, redirects with flash, and mails the recipient', function () {
    $this->seed(SettingsSeeder::class);
    Mail::fake();

    $payload = [
        'name' => 'Μαρία Κωνσταντίνου',
        'email' => 'maria@example.com',
        'phone' => '+30 6912345678',
        'message' => 'Θα ήθελα να κλείσω μια πρώτη γνωριμία για το παιδί μου.',
        'company' => '', // honeypot empty => real visitor
    ];

    $response = $this->from(route('contact'))->post(route('contact.store'), $payload);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHas('contact_sent', true);

    expect(ContactMessage::count())->toBe(1);

    $message = ContactMessage::first();
    expect($message->name)->toBe('Μαρία Κωνσταντίνου')
        ->and($message->email)->toBe('maria@example.com')
        ->and($message->phone)->toBe('+30 6912345678')
        ->and($message->message)->toBe('Θα ήθελα να κλείσω μια πρώτη γνωριμία για το παιδί μου.');

    Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
        return $mail->hasTo(setting('contact_form_recipient'));
    });
});

it('treats a filled honeypot as spam: no record, no mail, but fake success', function () {
    $this->seed(SettingsSeeder::class);
    Mail::fake();

    $payload = [
        'name' => 'Spam Bot',
        'email' => 'bot@example.com',
        'phone' => '0000000000',
        'message' => 'This is an automated spam submission attempt.',
        'company' => 'Acme Bots Ltd', // honeypot filled => bot
    ];

    $response = $this->from(route('contact'))->post(route('contact.store'), $payload);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHas('contact_sent', true);

    $this->assertDatabaseCount('contact_messages', 0);
    Mail::assertNothingSent();
});

it('rejects a submission missing the name', function () {
    Mail::fake();

    $response = $this->from(route('contact'))->post(route('contact.store'), [
        'name' => '',
        'email' => 'someone@example.com',
        'message' => 'A perfectly valid length message here.',
        'company' => '',
    ]);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHasErrors('name');

    $this->assertDatabaseCount('contact_messages', 0);
    Mail::assertNothingSent();
});

it('rejects a submission with an invalid email', function () {
    Mail::fake();

    $response = $this->from(route('contact'))->post(route('contact.store'), [
        'name' => 'Γιώργος Παπαδόπουλος',
        'email' => 'not-an-email',
        'message' => 'A perfectly valid length message here.',
        'company' => '',
    ]);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHasErrors('email');

    $this->assertDatabaseCount('contact_messages', 0);
});

it('rejects a message shorter than ten characters', function () {
    Mail::fake();

    $response = $this->from(route('contact'))->post(route('contact.store'), [
        'name' => 'Γιώργος Παπαδόπουλος',
        'email' => 'giorgos@example.com',
        'message' => 'short', // < 10 chars
        'company' => '',
    ]);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHasErrors('message');

    $this->assertDatabaseCount('contact_messages', 0);
});

it('renders the success state after following the post-submit redirect', function () {
    $this->seed(SettingsSeeder::class);
    Mail::fake();

    $response = $this->from(route('contact'))
        ->followingRedirects()
        ->post(route('contact.store'), [
            'name' => 'Ελένη Νικολάου',
            'email' => 'eleni@example.com',
            'phone' => null,
            'message' => 'Ευχαριστώ για τον χρόνο σας, περιμένω την απάντησή σας.',
            'company' => '',
        ]);

    $response->assertOk();
    $response->assertSee('Το μήνυμά σας έφτασε', escape: false);
});
