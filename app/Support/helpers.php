<?php

declare(strict_types=1);

use App\Support\Backdrop;
use App\Support\Content;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

if (! function_exists('home_label')) {
    /**
     * What the site calls its own front page.
     *
     * Every breadcrumb opens with this word, in six templates and in the
     * structured data. It already has an editable home — the first item of
     * the navigation menu — so it reads from there rather than becoming a
     * seventh copy that would drift the first time someone renamed the menu.
     */
    function home_label(): string
    {
        $nav = content_items('shared.nav');

        return trim((string) ($nav[0]['label'] ?? '')) ?: 'Αρχική';
    }
}

if (! function_exists('service_list')) {
    /**
     * The services, each carrying the anchor its own section will use.
     *
     * The links in the hero and the ids further down the page have to agree,
     * and they are written in two different places in the template. Deriving
     * both from one list is what keeps them in step the day a service is
     * renamed or dragged somewhere else in the panel.
     *
     * @return array<int, array<string, mixed>>
     */
    function service_list(): array
    {
        $seen = [];
        $services = [];

        foreach (content_items('services.services') as $service) {
            // Greek transliterates, so Λογοθεραπεία becomes a link a parent can
            // recognise in the address bar and send to someone else.
            $anchor = Str::slug((string) ($service['name'] ?? ''), '-', 'el') ?: 'ypiresia';

            // Two services share a name for as long as it takes to rename one
            // of them; duplicate ids would send both links to the first.
            $seen[$anchor] = ($seen[$anchor] ?? 0) + 1;

            if ($seen[$anchor] > 1) {
                $anchor .= '-'.$seen[$anchor];
            }

            $service['anchor'] = $anchor;
            $services[] = $service;
        }

        return $services;
    }
}

if (! function_exists('backdrop_scene')) {
    /** The next scene to run faintly behind a band, or null if there are none. */
    function backdrop_scene(?string $for = null): ?string
    {
        return app(Backdrop::class)->next($for);
    }
}
