<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Event;
use App\Models\User;
use App\Support\StructuredData;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Η σελίδα μιας εκδήλωσης
|--------------------------------------------------------------------------
| Η εκδήλωση ήταν ένας τίτλος με μια ημερομηνία. Έχει τώρα δική της σελίδα,
| με κείμενο όπως ένα άρθρο, και με τις φωτογραφίες της μέρας που ανεβαίνουν
| μετά. Η κάρτα στη λίστα είναι η πόρτα για εκεί.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

function happening(array $attributes = []): Event
{
    return Event::factory()->create($attributes + [
        'title' => 'Εργαστήρι για γονείς',
        'starts_at' => now()->addWeeks(2)->setTime(18, 30),
        'location' => 'Αίθουσα Αρωγής',
        'body' => '<p>Τι θα κάνουμε μαζί.</p><p>Και μια δεύτερη παράγραφος.</p>',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
}

function boss3(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    return $user->refresh();
}

/* ---------------------------------------------------------------- the page */

it('gives an event a page of its own', function () {
    $event = happening();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSeeText('Εργαστήρι για γονείς')
        ->assertSeeText(content('events.event_crumb'));
});

it('carries the description as prose, not as tags', function () {
    $event = happening();

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    // The panel offers a rich-text editor for this field; the page has to
    // render what it writes rather than print it.
    expect($html)->toContain('<p>Τι θα κάνουμε μαζί.</p>')
        ->and($html)->not->toContain('&lt;p&gt;');
});

it('says when and where at the top', function () {
    $event = happening();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSeeText('18:30')
        ->assertSeeText('Αίθουσα Αρωγής')
        ->assertSeeText(el_date($event->starts_at));
});

it('stays out of sight until its announcement is due', function () {
    $later = happening(['published_at' => now()->addWeek()]);
    $draft = happening(['is_published' => false, 'published_at' => null]);

    $this->get(route('events.show', $later))->assertNotFound();
    $this->get(route('events.show', $draft))->assertNotFound();
});

/* -------------------------------------------------------------- the photos */

it('shows the photographs once they are there', function () {
    Storage::fake('public');

    $event = happening();
    $event->addMedia(UploadedFile::fake()->image('day-one.jpg', 1200, 800))->toMediaCollection('photos');
    $event->addMedia(UploadedFile::fake()->image('day-two.jpg', 1200, 800))->toMediaCollection('photos');

    $html = $this->get(route('events.show', $event->fresh()))->assertOk()->getContent();

    expect(substr_count($html, 'data-lightbox'))->toBe(2)
        ->and($html)->toContain(e(content('events.photos_title')));
});

it('leaves the photo section out when there are none', function () {
    $html = $this->get(route('events.show', happening()))->assertOk()->getContent();

    expect($html)->not->toContain(e(content('events.photos_title')))
        ->and($html)->not->toContain('data-lightbox');
});

it('counts them on the card so people know there are some', function () {
    Storage::fake('public');

    $event = happening();
    $event->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('photos');

    $this->get(route('events'))->assertOk()->assertSeeText('1 φωτογραφία');
});

/* ---------------------------------------------------------------- the card */

it('makes the whole card open the event', function () {
    $event = happening();

    $this->get(route('events'))
        ->assertOk()
        ->assertSee('href="'.route('events.show', $event).'"', false)
        ->assertSeeText(content('events.card_link'));
});

it('teases the description on the card without its markup', function () {
    happening(['body' => '<p><strong>Έντονα</strong> και απλά.</p>']);

    $html = $this->get(route('events'))->assertOk()->getContent();

    expect($html)->toContain('Έντονα και απλά.')
        ->and($html)->not->toContain('<strong>Έντονα</strong> και απλά.');
});

/* ------------------------------------------------------------- the marking */

it('tells Google it is an event, with the date and the place', function () {
    $event = happening();

    $node = StructuredData::event($event);

    expect($node['@type'])->toBe('Event')
        ->and($node['name'])->toBe('Εργαστήρι για γονείς')
        ->and($node['startDate'])->toBe($event->starts_at->toIso8601String())
        ->and($node['location']['name'])->toBe('Αίθουσα Αρωγής')
        ->and($node['url'])->toBe(route('events.show', $event));

    $this->get(route('events.show', $event))->assertSee('"@type":"Event"', false);
});

it('shares with a picture, a title and a line of description', function () {
    $event = happening();

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    expect($html)->toContain('property="og:type" content="article"')
        ->and($html)->toContain('Εργαστήρι για γονείς')
        ->and($html)->toContain('Τι θα κάνουμε μαζί.');
});

/* -------------------------------------------------------------- the panel */

it('takes several photographs in one save', function () {
    Storage::fake('public');

    $event = happening();

    $this->actingAs(boss3())->put(route('admin.events.update', ['event' => $event->id]), [
        'title' => $event->title,
        'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
        'location' => $event->location,
        'body' => $event->body,
        'is_published' => '1',
        'photos' => [
            UploadedFile::fake()->image('a.jpg', 900, 600),
            UploadedFile::fake()->image('b.jpg', 900, 600),
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($event->fresh()->getMedia('photos'))->toHaveCount(2);
});

it('drops the ones that were ticked', function () {
    Storage::fake('public');

    $event = happening();
    $keep = $event->addMedia(UploadedFile::fake()->image('keep.jpg'))->toMediaCollection('photos');
    $drop = $event->addMedia(UploadedFile::fake()->image('drop.jpg'))->toMediaCollection('photos');

    $this->actingAs(boss3())->put(route('admin.events.update', ['event' => $event->id]), [
        'title' => $event->title,
        'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
        'is_published' => '1',
        'remove_photos' => [$drop->id],
    ])->assertRedirect();

    expect($event->fresh()->getMedia('photos')->modelKeys())->toBe([$keep->id]);
});

it('offers the uploader on the event form', function () {
    $html = $this->actingAs(boss3())
        ->get(route('admin.events.edit', ['event' => happening()->id]))
        ->assertOk()->getContent();

    expect($html)->toContain('name="photos[]"')
        ->and($html)->toContain('multiple')
        ->and($html)->toContain('Φωτογραφίες από την εκδήλωση');
});

/* --------------------------------------------------------------- the rest */

it('points back at the list and forward at what else is coming', function () {
    $event = happening(['title' => 'Αυτή εδώ']);
    happening(['title' => 'Η επόμενη', 'starts_at' => now()->addWeeks(4)]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSeeText(content('events.back'))
        ->assertSeeText(content('events.more_title'))
        ->assertSeeText('Η επόμενη');
});

it('does not offer itself as something else to see', function () {
    $event = happening(['title' => 'Μόνη της']);

    $html = $this->get(route('events.show', $event))->assertOk()->getContent();

    expect($html)->not->toContain(e(content('events.more_title')));
});
