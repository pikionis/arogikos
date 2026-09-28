<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\Article;
use App\Models\Cms\Event;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Γράψε τώρα, ανέβασε αργότερα
|--------------------------------------------------------------------------
| Μελλοντική ημερομηνία σημαίνει «ανέβα μόνο σου τότε». Για τις εκδηλώσεις
| αυτό είναι ΑΛΛΟ πεδίο από την ημερομηνία της εκδήλωσης: η ανακοίνωση
| υπάρχει για να διαβαστεί ΠΡΙΝ γίνει η εκδήλωση.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

/* --------------------------------------------------------------- articles */

it('keeps an article back until its date arrives', function () {
    $article = Article::factory()->create([
        'title' => 'Γραμμένο σήμερα, για την άλλη βδομάδα',
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $this->get(route('blog.index'))->assertOk()->assertDontSee('Γραμμένο σήμερα');
    $this->get(route('blog.show', $article->slug))->assertNotFound();
});

it('publishes that article by itself when the day comes', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'published_at' => now()->addWeek(),
    ]);

    $this->travel(8)->days();

    // Nothing ran, nobody pressed anything: the scope simply became true.
    $this->get(route('blog.show', $article->slug))->assertOk();
});

/* ----------------------------------------------------------------- events */

it('keeps an announcement back until its date arrives', function () {
    Event::factory()->create([
        'title' => 'Ανακοίνωση για αργότερα',
        'is_published' => true,
        'published_at' => now()->addWeek(),
        'starts_at' => now()->addMonths(2),
    ]);

    $this->get(route('events'))->assertOk()->assertDontSee('Ανακοίνωση για αργότερα');
});

it('publishes the announcement by itself when the day comes', function () {
    Event::factory()->create([
        'title' => 'Ανακοίνωση για αργότερα',
        'is_published' => true,
        'published_at' => now()->addWeek(),
        'starts_at' => now()->addMonths(2),
    ]);

    $this->travel(8)->days();

    $this->get(route('events'))->assertOk()->assertSeeText('Ανακοίνωση για αργότερα');
});

it('announces an event well before the event happens', function () {
    // The whole point: people have to be able to read about it in time to
    // come. Hiding the announcement until the day would empty the room.
    Event::factory()->create([
        'title' => 'Ημερίδα Νοεμβρίου',
        'is_published' => true,
        'published_at' => now()->subDay(),
        'starts_at' => now()->addMonths(3),
    ]);

    $this->get(route('events'))
        ->assertOk()
        ->assertSeeText('Ημερίδα Νοεμβρίου');
});

it('keeps the two dates apart in the listing and the year filter', function () {
    $this->travelTo(Carbon::create(2026, 10, 1));

    Event::factory()->create([
        'title' => 'Γράφτηκε πέρσι, γίνεται του χρόνου',
        'is_published' => true,
        'published_at' => Carbon::create(2026, 9, 1),
        'starts_at' => Carbon::create(2027, 2, 20),
    ]);
    Event::factory()->create([
        'title' => 'Παλιά εκδήλωση',
        'is_published' => true,
        'published_at' => Carbon::create(2025, 1, 1),
        'starts_at' => Carbon::create(2025, 3, 1),
    ]);

    // The year filter follows when it happens, not when it was announced.
    $this->get(route('events', ['year' => 2027]))
        ->assertOk()
        ->assertSeeText('Γράφτηκε πέρσι, γίνεται του χρόνου')
        ->assertDontSee('Παλιά εκδήλωση');
});

/* ------------------------------------------------------------- the panel */

it('fills in the publish date when an event is published without one', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    $this->actingAs($user->refresh())->post(route('admin.events.store'), [
        'title' => 'Νέα εκδήλωση',
        'starts_at' => now()->addMonth()->format('Y-m-d H:i'),
        'is_published' => 1,
    ])->assertRedirect();

    // Publishing with no date would otherwise hide it for ever.
    expect(Event::latest('id')->first()->published_at)->not->toBeNull();
});

it('honours a publish date the author chose', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    $when = now()->addDays(10)->startOfMinute();

    $this->actingAs($user->refresh())->post(route('admin.events.store'), [
        'title' => 'Προγραμματισμένη',
        'starts_at' => now()->addMonths(2)->format('Y-m-d H:i'),
        'is_published' => 1,
        'published_at' => $when->format('Y-m-d H:i'),
    ])->assertRedirect();

    expect(Event::latest('id')->first()->published_at->format('Y-m-d H:i'))
        ->toBe($when->format('Y-m-d H:i'));
});

it('lets staff preview a scheduled event and says it is scheduled', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Editor, 'is_active' => true])->save();

    $event = Event::factory()->scheduled()->create(['title' => 'Δεν φάνηκε ακόμη']);

    $this->actingAs($user->refresh())
        ->get(route('admin.preview.event', $event))
        ->assertOk()
        ->assertSee('preview-scheduled', false)
        ->assertSeeText('Δεν φάνηκε ακόμη');
});

it('does not lose events that were public before this existed', function () {
    // The migration backfills published_at from created_at, so nothing that
    // was already announced disappears on deploy.
    $existing = Event::factory()->create(['published_at' => null, 'is_published' => true]);

    expect(Event::published()->pluck('id'))->not->toContain($existing->id);

    $existing->forceFill(['published_at' => $existing->created_at])->save();

    expect(Event::published()->pluck('id'))->toContain($existing->id);
});
