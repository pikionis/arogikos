<?php

declare(strict_types=1);

use App\Models\Cms\Album;
use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\ContactMessage;
use App\Models\Cms\Event;
use App\Models\Cms\Page;
use App\Models\Cms\Photo;
use App\Models\Cms\Setting;
use App\Support\Settings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| setting() helper
|--------------------------------------------------------------------------
*/

it('returns the stored value for a known setting key', function () {
    Setting::create(['key' => 'phone', 'value' => '22420 29 100']);

    expect(setting('phone'))->toBe('22420 29 100');
});

it('returns the fallback for a missing setting key', function () {
    expect(setting('missing', 'fallback'))->toBe('fallback');
});

it('returns null for a missing setting key when no default is given', function () {
    expect(setting('missing'))->toBeNull();
});

it('returns the provided default when the stored value is an empty string', function () {
    // Seeder stores ga_id as '' by default.
    $this->seed(SettingsSeeder::class);

    // An empty stored value is treated as absent: no default => null.
    expect(setting('ga_id'))->toBeNull();
    // SettingsSeeder default is '', so setting() must fall back to the given default.
    expect(setting('ga_id', 'NONE'))->toBe('NONE');
});

it('treats an explicitly empty-string value as absent and uses the default', function () {
    Setting::create(['key' => 'tagline', 'value' => '']);

    expect(setting('tagline', 'DEFAULT'))->toBe('DEFAULT');
});

it('busts the cache when a setting is updated so the next read is fresh', function () {
    $setting = Setting::create(['key' => 'phone', 'value' => '11111']);

    // Prime the forever-cache via a read.
    expect(setting('phone'))->toBe('11111');

    // Update the row — the model's saved() hook flushes the cache.
    $setting->update(['value' => '22222']);

    expect(setting('phone'))->toBe('22222');
});

it('busts the cache when a setting is created after a prior read', function () {
    // Read a missing key first to prime the cache as an array without it.
    expect(setting('email', 'NONE'))->toBe('NONE');

    Setting::create(['key' => 'email', 'value' => 'info@arogi.gr']);

    expect(setting('email'))->toBe('info@arogi.gr');
});

it('busts the cache when a setting is deleted so the next read falls back', function () {
    $setting = Setting::create(['key' => 'phone', 'value' => '12345']);

    expect(setting('phone'))->toBe('12345');

    $setting->delete();

    expect(setting('phone', 'GONE'))->toBe('GONE');
});

/*
|--------------------------------------------------------------------------
| App\Support\Settings service
|--------------------------------------------------------------------------
*/

it('reads settings through the Settings service get() with default handling', function () {
    Setting::create(['key' => 'org_name', 'value' => 'Αρωγή']);
    Setting::create(['key' => 'blank', 'value' => '']);

    $settings = app(Settings::class);

    expect($settings->get('org_name'))->toBe('Αρωγή')
        ->and($settings->get('blank', 'X'))->toBe('X')
        ->and($settings->get('absent', 'Y'))->toBe('Y')
        ->and($settings->get('absent'))->toBeNull();
});

it('exposes the full settings array via all()', function () {
    Setting::create(['key' => 'phone', 'value' => '22420 29 100']);
    Setting::create(['key' => 'email', 'value' => 'info@arogi.gr']);

    $all = app(Settings::class)->all();

    expect($all)->toBeArray()
        ->and($all)->toHaveKey('phone', '22420 29 100')
        ->and($all)->toHaveKey('email', 'info@arogi.gr');
});

/*
|--------------------------------------------------------------------------
| cms_model() swap indirection
|--------------------------------------------------------------------------
*/

it('resolves the article model class through cms_model', function () {
    expect(cms_model('article'))->toBe(Article::class);
});

it('resolves every CMS model key to its configured class', function () {
    expect(cms_model('page'))->toBe(Page::class)
        ->and(cms_model('article'))->toBe(Article::class)
        ->and(cms_model('author'))->toBe(Author::class)
        ->and(cms_model('event'))->toBe(Event::class)
        ->and(cms_model('album'))->toBe(Album::class)
        ->and(cms_model('photo'))->toBe(Photo::class)
        ->and(cms_model('contact_message'))->toBe(ContactMessage::class)
        ->and(cms_model('setting'))->toBe(Setting::class);
});

it('throws InvalidArgumentException for an unknown CMS model key', function () {
    cms_model('bogus');
})->throws(InvalidArgumentException::class);

/*
|--------------------------------------------------------------------------
| el_date() Greek date formatter
|--------------------------------------------------------------------------
*/

it('formats a date the Greek way with the genitive month', function () {
    expect(el_date(Carbon::parse('2026-05-12')))->toBe('12 Μαΐου 2026');
});

it('returns an empty string when the date is null', function () {
    expect(el_date(null))->toBe('');
});

it('accepts a string date and formats it', function () {
    expect(el_date('2026-01-01'))->toBe('1 Ιανουαρίου 2026');
});

it('formats each genitive month correctly', function () {
    $expected = [
        '01' => 'Ιανουαρίου', '02' => 'Φεβρουαρίου', '03' => 'Μαρτίου',
        '04' => 'Απριλίου', '05' => 'Μαΐου', '06' => 'Ιουνίου',
        '07' => 'Ιουλίου', '08' => 'Αυγούστου', '09' => 'Σεπτεμβρίου',
        '10' => 'Οκτωβρίου', '11' => 'Νοεμβρίου', '12' => 'Δεκεμβρίου',
    ];

    foreach ($expected as $month => $greek) {
        expect(el_date(Carbon::parse("2026-{$month}-15")))->toBe("15 {$greek} 2026");
    }
});
