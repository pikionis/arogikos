<?php

declare(strict_types=1);

use App\Models\Cms\Setting;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| The address opens the map
|--------------------------------------------------------------------------
| Wherever the address appears, tapping it should take a visitor to the
| centre on Google Maps — that is the point of putting it on a phone screen.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

it('links the address on the contact page', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee(setting('maps_url'), false)
        ->assertSeeText(setting('address'));
});

it('links the address in the footer, so it works from every page', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee(setting('maps_url'), false);
})->with(['home', 'blog.index', 'gallery', 'events']);

it('opens the map in a new tab without leaking the referrer', function () {
    $html = $this->get(route('contact'))->assertOk()->getContent();

    expect($html)->toContain('rel="noopener noreferrer"');
});

it('shows the address as plain text when no map link is set', function () {
    // A link that goes nowhere is worse than no link.
    Setting::where('key', 'maps_url')->delete();
    Settings::flush();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSeeText(setting('address'))
        ->assertDontSee('title="Άνοιγμα στους χάρτες"', false);
});

it('follows a map link changed in the panel', function () {
    Setting::updateOrCreate(['key' => 'maps_url'], ['value' => 'https://maps.google.com/?cid=999']);
    Settings::flush();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('https://maps.google.com/?cid=999', false);
});

it('embeds a map of the centre, not a generic view of the island', function () {
    // The seeded default used to be Kos at zoom 14 — the island, with no
    // indication of where the centre actually is.
    $embed = setting('maps_embed_url');

    expect($embed)
        ->toContain('36.888')
        ->toContain('27.276')
        ->toContain('output=embed');

    // Blade escapes the & in the query string, so assert on the iframe's
    // coordinates rather than the raw URL.
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('36.8884583,27.2767477', false)
        ->assertSee('output=embed', false);
});

it('offers the map link as an editable setting', function () {
    $keys = collect(config('cms-settings'))
        ->flatMap(fn (array $group) => collect($group['fields'])->pluck('key'))
        ->all();

    expect($keys)->toContain('maps_url');
});
