<?php

declare(strict_types=1);

use App\Models\Cms\Setting;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Analytics and Ads tracking
|--------------------------------------------------------------------------
| Both are off until an id is entered in the panel — no third-party script
| loads on a site that has not asked for one. When they are on, an enquiry is
| reported as a conversion, because that is the thing the centre pays for.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function setSetting(string $key, string $value): void
{
    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    Settings::flush();
}

/* ------------------------------------------------------------------- off */

it('loads no tracking script when nothing is configured', function () {
    // The shipped default is empty, and a site that tracks nobody by default
    // is the right default.
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager', false)
        ->assertDontSee('gtag(', false);
});

/* ------------------------------------------------------------- analytics */

it('loads Analytics once an id is entered', function () {
    setSetting('ga_id', 'G-TEST12345');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST12345', false)
        ->assertSee('"G-TEST12345"', false);
});

/* ------------------------------------------------------------------ ads */

it('loads Ads on its own, without Analytics', function () {
    // The two are independent; a centre may run ads before it measures traffic.
    setSetting('google_ads_id', 'AW-987654321');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=AW-987654321', false)
        ->assertSee('"AW-987654321"', false);
});

it('configures both from one script when both are set', function () {
    setSetting('ga_id', 'G-TEST12345');
    setSetting('google_ads_id', 'AW-987654321');

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(substr_count($html, 'googletagmanager.com/gtag/js'))->toBe(1)
        ->and($html)->toContain('"G-TEST12345"')
        ->and($html)->toContain('"AW-987654321"');
});

/* ----------------------------------------------------------- conversions */

it('reports an enquiry as an Ads conversion', function () {
    setSetting('google_ads_id', 'AW-987654321');
    setSetting('google_ads_conversion_label', 'AbC-D_efGh');

    $this->followingRedirects()
        ->post(route('contact.store'), [
            'name' => 'Γονέας',
            'email' => 'goneas@example.com',
            'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
        ])
        ->assertOk()
        ->assertSee('AW-987654321\/AbC-D_efGh', false);
});

it('reports an enquiry as a lead in Analytics', function () {
    setSetting('ga_id', 'G-TEST12345');

    $this->followingRedirects()
        ->post(route('contact.store'), [
            'name' => 'Γονέας',
            'email' => 'goneas@example.com',
            'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
        ])
        ->assertOk()
        ->assertSee('generate_lead', false);
});

it('fires no conversion on a plain visit to the contact page', function () {
    setSetting('google_ads_id', 'AW-987654321');
    setSetting('google_ads_conversion_label', 'AbC-D_efGh');

    // Counting a conversion for anyone who merely opened the page would make
    // the campaign numbers meaningless.
    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('AbC-D_efGh', false);
});

it('fires no conversion while the label is missing', function () {
    setSetting('google_ads_id', 'AW-987654321');

    $this->followingRedirects()
        ->post(route('contact.store'), [
            'name' => 'Γονέας',
            'email' => 'goneas@example.com',
            'message' => 'Θα ήθελα πληροφορίες για τα προγράμματά σας.',
        ])
        ->assertOk()
        ->assertDontSee("'conversion'", false);
});

it('offers all three ids in the panel', function () {
    $keys = collect(config('cms-settings'))
        ->flatMap(fn (array $g) => collect($g['fields'])->pluck('key'))
        ->all();

    expect($keys)->toContain('ga_id', 'google_ads_id', 'google_ads_conversion_label');
});
