<?php

declare(strict_types=1);

use App\Models\Cms\Event;
use App\Support\Backdrop;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Κίνηση στο φόντο
|--------------------------------------------------------------------------
| Οι σκηνές των άρθρων δεν έχουν λόγο να ζουν μόνο σε ένα εξώφυλλο. Τρέχουν
| τώρα πολύ αχνά πίσω από ζώνες του site, διαφορετική κάθε φορά, ώστε η
| σελίδα να ανασαίνει. Είναι διακόσμηση και πρέπει να μείνει διακόσμηση:
| πίσω από το κείμενο, μακριά από το ποντίκι, αόρατη στους αναγνώστες οθόνης,
| και ακίνητη για όποιον έχει ζητήσει λιγότερη κίνηση.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

it('runs a scene behind a band on the home page', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('class="scene-backdrop" aria-hidden="true"');

    preg_match_all('/scene-backdrop.*?cv cv-([a-z]+) is-live/s', $html, $m);

    expect($m[1])->not->toBeEmpty()
        ->and(array_keys(config('cms-covers')))->toContain($m[1][0]);
});

it('gives each band on a page a different scene', function () {
    // The home page runs two, and the same picture twice on one page reads as
    // a mistake rather than as a decision.
    Event::factory()->create(['starts_at' => now()->addWeek()]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match_all('/class="scene-backdrop"[\s\S]*?cv cv-([a-z]+) is-live/', $html, $m);

    expect($m[1])->toHaveCount(2)
        ->and(array_unique($m[1]))->toHaveCount(2);
});

it('works through every scene before it repeats one', function () {
    $backdrop = new Backdrop;
    $all = array_keys(config('cms-covers'));

    $drawn = [];
    foreach ($all as $ignored) {
        $drawn[] = $backdrop->next();
    }

    sort($drawn);
    sort($all);

    expect($drawn)->toBe($all);
});

it('shuffles, so two visits are not the same page', function () {
    $orders = [];

    for ($i = 0; $i < 8; $i++) {
        $backdrop = new Backdrop;
        $orders[] = $backdrop->next().'|'.$backdrop->next();
    }

    // Twenty-three scenes: eight identical pairs would not be chance.
    expect(array_unique($orders))->not->toHaveCount(1);
});

it('can be pinned to one scene', function () {
    $this->instance(Backdrop::class, new class extends Backdrop
    {
        public function next(): ?string
        {
            return 'friends';
        }
    });

    expect($this->get(route('home'))->getContent())->toContain('cv cv-friends is-live');
});

it('spreads across the site, not just one page', function () {
    $pages = ['home', 'about', 'events', 'gallery', 'contact', 'blog.index', 'services'];

    $missing = [];
    foreach ($pages as $page) {
        if (! str_contains($this->get(route($page))->getContent(), 'scene-backdrop')) {
            $missing[] = $page;
        }
    }

    expect($missing)->toBe([]);
});

it('keeps it behind the words and out of the way', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    expect($css)->toContain('.scene-backdrop {')
        // Behind the container, which already claims z-index 1.
        ->and($css)->toMatch('/\.scene-backdrop \{[^}]*z-index: 0/')
        ->and($css)->toMatch('/\.scene-backdrop \{[^}]*pointer-events: none/')
        ->and($css)->toContain('.section > .container { position: relative; z-index: 1; }');
});

it('stays faint enough to read over', function () {
    $css = file_get_contents(resource_path('css/arogi.css'));

    preg_match('/\.scene-backdrop \{[^}]*opacity: ([\d.]+)/', $css, $m);

    // A wash, not a picture. Anything you can actually make out competes with
    // the text sitting on top of it.
    expect((float) $m[1])->toBeLessThanOrEqual(0.1);
});

it('stops moving when the reader has asked for less motion', function () {
    $css = file_get_contents(resource_path('css/arogi-covers.css'));

    expect($css)->toContain('@media (prefers-reduced-motion: reduce)')
        ->and($css)->toContain('.cv * { animation: none !important; }');
});

it('says nothing to a screen reader', function () {
    $html = $this->get(route('home'))->getContent();

    preg_match('/<div class="scene-backdrop"([^>]*)>/', $html, $m);

    expect($m[1])->toContain('aria-hidden="true"');
});
