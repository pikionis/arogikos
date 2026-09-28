<?php

declare(strict_types=1);

use App\Models\Cms\Event;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Τι λέει η κάρτα μιας εκδήλωσης
|--------------------------------------------------------------------------
| Ώρα και τοποθεσία είναι τα δύο πράγματα που ρωτά κάποιος που σκέφτεται
| να έρθει. Η ώρα φαινόταν μόνο στις επερχόμενες.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

it('shows the time and the place of an upcoming event', function () {
    Event::factory()->create([
        'title' => 'Ημερίδα',
        'starts_at' => now()->addMonth()->setTime(18, 30),
        'location' => 'Αίθουσα Αρωγής, Κως',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('events'))
        ->assertOk()
        ->assertSeeText('18:30')
        ->assertSeeText('Αίθουσα Αρωγής, Κως');
});

it('shows the time of a past one too', function () {
    Event::factory()->create([
        'title' => 'Περσινό εργαστήρι',
        'starts_at' => now()->subYear()->setTime(10, 0),
        'location' => 'Αρωγή, Κως',
        'is_published' => true,
        'published_at' => now()->subYear()->subWeek(),
    ]);

    // Whether it was a morning workshop or an evening talk is exactly what
    // someone browsing the archive wants to know.
    $this->get(route('events'))
        ->assertOk()
        ->assertSeeText('10:00')
        ->assertSeeText('Αρωγή, Κως');
});

it('shows the year on a past event, since the badge only carries day and month', function () {
    $this->travelTo(Carbon::create(2026, 10, 1));

    Event::factory()->create([
        'title' => 'Παλιά εκδήλωση',
        'starts_at' => Carbon::create(2025, 3, 14, 17, 0),
        'is_published' => true,
        'published_at' => Carbon::create(2025, 2, 1),
    ]);

    $this->get(route('events'))->assertOk()->assertSeeText('2025');
});

it('leaves the year off an upcoming one', function () {
    $this->travelTo(Carbon::create(2026, 10, 1));

    Event::factory()->create([
        'title' => 'Επερχόμενη',
        'starts_at' => Carbon::create(2026, 11, 20, 17, 0),
        'location' => 'Κως',
        'is_published' => true,
        'published_at' => Carbon::create(2026, 9, 1),
    ]);

    $html = $this->get(route('events'))->assertOk()->getContent();

    // Nobody needs telling that a forthcoming event is this year.
    expect(substr_count($html, '📅'))->toBe(0);
});

it('names each fact for a screen reader instead of leaving an emoji to do it', function () {
    Event::factory()->create([
        'starts_at' => now()->addMonth()->setTime(18, 30),
        'location' => 'Κως',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('events'))->getContent();

    // "pin emoji, Κως" is what a reader hears otherwise.
    expect($html)->toContain('<span class="sr">Ώρα:</span>')
        ->and($html)->toContain('<span class="sr">Τοποθεσία:</span>')
        ->and($html)->toContain('aria-hidden="true">📍');
});

it('says nothing about a place that was never filled in', function () {
    Event::factory()->create([
        'starts_at' => now()->addMonth(),
        'location' => null,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $html = $this->get(route('events'))->getContent();

    expect($html)->not->toContain('Τοποθεσία:');
});
