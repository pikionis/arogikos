<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;

/*
|--------------------------------------------------------------------------
| Η σειρά των πεδίων στο panel ακολουθεί τη σελίδα
|--------------------------------------------------------------------------
| Όποιος διορθώνει ένα κείμενο το ψάχνει εκεί που το είδε. Αν τα πεδία στο
| panel είναι σε άλλη σειρά από τη σελίδα, πρέπει να κρατάει τη σελίδα σε
| δεύτερη καρτέλα για να βρει τι είναι τι.
*/

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/**
 * Walks the page once, asking whether each field can still be found ahead of
 * the one before it.
 *
 * Not "where does this text first appear": the same words often show up in
 * the header or the footer as well — the CTA button repeats the navigation
 * button — and the first hit would be that copy rather than the one this
 * field renders. Looking forward from the previous match answers the only
 * question that matters: can the fields be read in the order they are
 * listed?
 *
 * @return array<int, string> keys that could not be
 */
function outOfPageOrder(string $html, string $pageKey): array
{
    $cursor = 0;
    $stragglers = [];

    foreach (config("cms-content.{$pageKey}.groups", []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (! in_array($field['type'], ['text', 'textarea'], true)) {
                continue;
            }

            if (in_array("{$pageKey}.{$field['key']}", conditionalKeys(), true)) {
                continue;
            }

            // The SEO pair lives in <head>, not on the page. It is grouped
            // first in the panel on purpose — it is a setting for the page,
            // not a line of it — so page order says nothing about it.
            if (str_starts_with($field['key'], 'seo.')) {
                continue;
            }

            $default = (string) ($field['default'] ?? '');

            if ($default === '') {
                continue;
            }

            $at = mb_strpos($html, e($default), $cursor);

            if ($at !== false) {
                $cursor = $at;

                continue;
            }

            // Nothing ahead: either it renders before the field above it, or
            // it does not render at all — and the sweep in ContentRendersTest
            // already rules the second one out.
            if (mb_strpos($html, e($default)) !== false) {
                $stragglers[] = $field['key'];
            }
        }
    }

    return $stragglers;
}

it('lists the fields in the order the page shows them', function (string $pageKey, string $routeName) {
    $html = $this->get(route($routeName))->assertOk()->getContent();

    expect(outOfPageOrder($html, $pageKey))->toBe([]);
})->with([
    'home' => ['home', 'home'],
    'about' => ['about', 'about'],
    'services' => ['services', 'services'],
    'blog' => ['blog', 'blog.index'],
    'events' => ['events', 'events'],
    'gallery' => ['gallery', 'gallery'],
    'contact' => ['contact', 'contact'],
]);

it('lists each service the way the services page reads it', function () {
    $html = $this->get(route('services'))->assertOk()->getContent();

    $sub = collect(config('cms-content.services.groups'))
        ->pluck('fields')
        ->flatten(1)
        ->firstWhere('key', 'services')['item'] ?? [];

    $service = content_items('services.services')[0];

    // Forward scan, for the same reason as above: a service's name also
    // appears in the structured data in <head>, long before its section.
    $cursor = 0;
    $stragglers = [];

    foreach ($sub as $field) {
        $value = $service[$field['key']] ?? null;
        $needle = is_array($value) ? ($value[0] ?? null) : $value;

        if (! is_string($needle) || $needle === '') {
            continue;
        }

        $at = mb_strpos($html, e($needle), $cursor);

        if ($at !== false) {
            $cursor = $at;

            continue;
        }

        // Fields that belong to the home page are not on this one at all.
        if (mb_strpos($html, e($needle)) !== false) {
            $stragglers[] = $field['key'];
        }
    }

    expect($stragglers)->toBe([]);
});
