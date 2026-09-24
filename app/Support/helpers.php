<?php

declare(strict_types=1);

use App\Support\Content;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

if (! function_exists('el_date')) {
    /**
     * Format a date the Greek way: "12 Μαΐου 2026" (genitive month).
     */
    function el_date(Carbon|string|null $date): string
    {
        if ($date === null) {
            return '';
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        $months = [
            1 => 'Ιανουαρίου', 2 => 'Φεβρουαρίου', 3 => 'Μαρτίου', 4 => 'Απριλίου',
            5 => 'Μαΐου', 6 => 'Ιουνίου', 7 => 'Ιουλίου', 8 => 'Αυγούστου',
            9 => 'Σεπτεμβρίου', 10 => 'Οκτωβρίου', 11 => 'Νοεμβρίου', 12 => 'Δεκεμβρίου',
        ];

        return $date->day.' '.$months[(int) $date->month].' '.$date->year;
    }
}

if (! function_exists('setting')) {
    /**
     * Read a site setting by key, with an optional default.
     *
     * Backed by a forever-cache (see {@see Settings}); safe to
     * call repeatedly in views without hitting the database.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('cms_model')) {
    /**
     * Resolve a CMS model class name by its logical key.
     *
     * Indirection point for the eventual swap to pikionis/cms-core: today the
     * map in config/cms-models.php points at App\Models\Cms\*; after the swap
     * it points at Pikionis\Cms\Models\* and nothing else changes.
     *
     * @return class-string<Model>
     */
    function cms_model(string $key): string
    {
        $class = config("cms-models.$key");

        if (! $class) {
            throw new InvalidArgumentException("Unknown CMS model [$key].");
        }

        return $class;
    }
}

if (! function_exists('content')) {
    /**
     * Editable page copy, by dotted key (e.g. content('home.hero.title')).
     *
     * Falls back to the Greek text declared in config/cms-content.php, so a
     * page renders correctly with an empty content_blocks table. Backed by a
     * forever-cache (see {@see Content}) — safe to call
     * repeatedly in views.
     */
    function content(string $key, ?string $default = null): string
    {
        return app(Content::class)->get($key, $default);
    }
}

if (! function_exists('content_lines')) {
    /**
     * A `lines` field as an array — one entry per line of the textarea.
     *
     * Used for the bullet lists that a flat key/value field cannot express
     * without freezing their length.
     *
     * @return array<int, string>
     */
    function content_lines(string $key): array
    {
        return app(Content::class)->lines($key);
    }
}

if (! function_exists('content_items')) {
    /**
     * A `repeater` field as a list of associative arrays.
     *
     * The count is fixed by the registry (min === max) because the layouts
     * are hand-built per item; only the text inside each is editable.
     *
     * @return array<int, array<string, mixed>>
     */
    function content_items(string $key): array
    {
        return app(Content::class)->items($key);
    }
}

if (! function_exists('content_image')) {
    /** URL of an uploaded image for an `image` field, or null. */
    function content_image(string $key, string $conversion = 'web'): ?string
    {
        return app(Content::class)->image($key, $conversion);
    }
}
