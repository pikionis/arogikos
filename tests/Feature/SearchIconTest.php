<?php

declare(strict_types=1);

use App\Support\StructuredData;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Το σήμα δίπλα στο αποτέλεσμα αναζήτησης
|--------------------------------------------------------------------------
| Η σελίδα δήλωνε εικονίδιο 32 pixel. Η Google δεν δέχεται μικρότερο από 48,
| ούτε πλευρά που δεν είναι πολλαπλάσιό του — οπότε δίπλα στο αποτέλεσμα δεν
| ζωγράφιζε τίποτα. Το .ico κουβαλούσε καρέ 48 από την αρχή· απλώς δεν το
| έλεγε πουθενά.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

/** @return array<int, int> */
function iconSides(): array
{
    return [48, 96, 192, 480];
}

it('ships an icon at every side Google will take', function () {
    $missing = [];

    foreach (iconSides() as $side) {
        $path = public_path("icon-{$side}.png");

        if (! is_file($path)) {
            $missing[] = "icon-{$side}.png";

            continue;
        }

        [$width, $height] = getimagesize($path);

        if ($width !== $side || $height !== $side) {
            $missing[] = "icon-{$side}.png is {$width}x{$height}";
        }
    }

    expect($missing)->toBe([]);
});

it('offers them on every page', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('<link rel="icon" href="'.asset('icon-192.png').'" type="image/png" sizes="192x192">')
        ->and($html)->toContain('sizes="480x480"')
        // The .ico carries a 48 frame, so that is what it should claim.
        ->and($html)->toContain('href="'.asset('favicon.ico').'" sizes="48x48"');
});

it('never claims a size Google would refuse', function () {
    $html = $this->get(route('home'))->getContent();

    preg_match_all('/<link rel="(?:shortcut )?icon"[^>]*sizes="(\d+)x(\d+)"/', $html, $m, PREG_SET_ORDER);

    expect($m)->not->toBeEmpty();

    $refused = [];
    foreach ($m as [, $width, $height]) {
        if ($width !== $height || (int) $width % 48 !== 0) {
            $refused[] = "{$width}x{$height}";
        }
    }

    expect($refused)->toBe([]);
});

it('keeps the icon where a crawler looks for it', function () {
    // Not under /admin, which robots.txt closes off.
    foreach (['favicon.ico', 'icon-192.png', 'apple-touch-icon.png'] as $file) {
        expect(is_file(public_path($file)))->toBeTrue("public/$file is missing");
    }

    expect(file_get_contents(public_path('robots.txt')))->not->toContain('Disallow: /'.PHP_EOL);
});

it('draws the mark rather than an empty square', function () {
    // A blank icon would look exactly like no icon in a search result, and
    // this file is generated — nobody would notice it had gone white.
    $image = imagecreatefrompng(public_path('icon-192.png'));

    $paper = imagecolorat($image, 0, 0);
    $sampled = 0;
    $drawn = 0;

    for ($x = 0; $x < 192; $x += 2) {
        for ($y = 0; $y < 192; $y += 2) {
            $sampled++;
            $drawn += imagecolorat($image, $x, $y) === $paper ? 0 : 1;
        }
    }

    imagedestroy($image);

    // The mark is three thin strokes, so it never covers much — but a blank
    // square covers none of it, and that is what this is guarding against.
    expect($drawn / $sampled)->toBeGreaterThan(0.08);
});

it('tells Google which picture is the logo', function () {
    $graph = StructuredData::site()['@graph'];

    $organisation = collect($graph)->first(
        fn (array $node) => in_array('LocalBusiness', (array) $node['@type'], true)
    );

    expect($organisation['logo'])->toBe(asset('icon-480.png'))
        // And it is still not the same thing as the wide share card.
        ->and($organisation['logo'])->not->toBe($organisation['image'] ?? null);
});

/*
|--------------------------------------------------------------------------
| Ποια σελίδα απαντά στο όνομα του κέντρου
|--------------------------------------------------------------------------
*/

it('puts the name and the town together on the front page, and nowhere else', function () {
    $home = $this->get(route('home'))->assertOk()->getContent();
    $contact = $this->get(route('contact'))->assertOk()->getContent();

    $titleOf = function (string $html): string {
        preg_match('/<title>([^<]*)<\/title>/', $html, $m);

        return html_entity_decode($m[1] ?? '', ENT_QUOTES, 'UTF-8');
    };

    // "αρωγή κως" is how people look for it. Two pages answering that query
    // equally well is how the wrong one came back.
    expect($titleOf($home))->toContain('Αρωγή Κως')
        ->and($titleOf($contact))->not->toContain('Κως');
});

it('tells Google the centre also goes by its name and town', function () {
    $graph = StructuredData::site()['@graph'];

    $organisation = collect($graph)->first(
        fn (array $node) => in_array('LocalBusiness', (array) $node['@type'], true)
    );

    expect($organisation['alternateName'])->toBe('Αρωγή Κως');
});

it('ranks the front page above the rest in the sitemap', function () {
    $xml = $this->get(route('sitemap'))->assertOk()->getContent();

    preg_match_all('/<loc>([^<]+)<\/loc>\s*(?:<lastmod>[^<]*<\/lastmod>\s*)?<priority>([\d.]+)<\/priority>/', $xml, $m, PREG_SET_ORDER);

    $priorities = [];
    foreach ($m as [, $loc, $priority]) {
        $priorities[$loc] = (float) $priority;
    }

    $home = $priorities[route('home')] ?? 0.0;

    expect($home)->toBe(1.0)
        ->and(collect($priorities)->except(route('home'))->max())->toBeLessThan($home);
});
