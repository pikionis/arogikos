<?php

declare(strict_types=1);

use App\Models\Cms\Event;
use Illuminate\Support\Carbon;

it('upcoming() returns only events with starts_at today or later, ordered ascending', function () {
    $past = Event::factory()->create(['starts_at' => now()->subDays(5)]);
    $today = Event::factory()->create(['starts_at' => now()->startOfDay()]);
    $soon = Event::factory()->create(['starts_at' => now()->addDays(3)]);
    $later = Event::factory()->create(['starts_at' => now()->addDays(10)]);

    $upcoming = Event::upcoming()->get();

    expect($upcoming->pluck('id')->all())
        ->toBe([$today->id, $soon->id, $later->id]);

    expect($upcoming->pluck('id'))->not->toContain($past->id);
});

it('upcoming() excludes events with null starts_at', function () {
    $withDate = Event::factory()->create(['starts_at' => now()->addDays(2)]);
    Event::factory()->create(['starts_at' => null]);

    $upcoming = Event::upcoming()->get();

    expect($upcoming->pluck('id')->all())->toBe([$withDate->id]);
});

it('an event starting today counts as upcoming, not past', function () {
    $today = Event::factory()->create(['starts_at' => now()->startOfDay()]);

    expect(Event::upcoming()->pluck('id')->all())->toContain($today->id);
    expect(Event::past()->pluck('id')->all())->not->toContain($today->id);
});

it('past() returns only events before today, ordered descending', function () {
    $today = Event::factory()->create(['starts_at' => now()->startOfDay()]);
    $future = Event::factory()->create(['starts_at' => now()->addDays(4)]);
    $recent = Event::factory()->create(['starts_at' => now()->subDays(2)]);
    $older = Event::factory()->create(['starts_at' => now()->subDays(20)]);

    $past = Event::past()->get();

    expect($past->pluck('id')->all())
        ->toBe([$recent->id, $older->id]);

    expect($past->pluck('id'))->not->toContain($today->id);
    expect($past->pluck('id'))->not->toContain($future->id);
});

it('past() excludes events with null starts_at', function () {
    $old = Event::factory()->create(['starts_at' => now()->subDays(3)]);
    Event::factory()->create(['starts_at' => null]);

    $past = Event::past()->get();

    expect($past->pluck('id')->all())->toBe([$old->id]);
});

it('published() excludes unpublished events', function () {
    $published = Event::factory()->create(['is_published' => true]);
    $unpublished = Event::factory()->create(['is_published' => false]);

    $result = Event::published()->get();

    expect($result->pluck('id')->all())->toContain($published->id);
    expect($result->pluck('id')->all())->not->toContain($unpublished->id);
});

it('day accessor zero-pads the day of month', function () {
    $fifth = Event::factory()->create(['starts_at' => Carbon::create(2026, 3, 5, 10)]);
    $fourteenth = Event::factory()->create(['starts_at' => Carbon::create(2026, 3, 14, 10)]);

    expect($fifth->day)->toBe('05');
    expect($fourteenth->day)->toBe('14');
});

it('day accessor returns null when starts_at is null', function () {
    $event = Event::factory()->create(['starts_at' => null]);

    expect($event->day)->toBeNull();
});

it('month_short returns the correct Greek 3-letter month', function (int $month, string $expected) {
    $event = Event::factory()->create(['starts_at' => Carbon::create(2026, $month, 10, 10)]);

    expect($event->month_short)->toBe($expected);
})->with([
    'January' => [1, 'Ιαν'],
    'February' => [2, 'Φεβ'],
    'March' => [3, 'Μαρ'],
    'April' => [4, 'Απρ'],
    'May' => [5, 'Μαϊ'],
    'June' => [6, 'Ιουν'],
    'July' => [7, 'Ιουλ'],
    'August' => [8, 'Αυγ'],
    'September' => [9, 'Σεπ'],
    'October' => [10, 'Οκτ'],
    'November' => [11, 'Νοε'],
    'December' => [12, 'Δεκ'],
]);

it('month_short returns null when starts_at is null', function () {
    $event = Event::factory()->create(['starts_at' => null]);

    expect($event->month_short)->toBeNull();
});
