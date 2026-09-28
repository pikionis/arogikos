<?php

declare(strict_types=1);

use App\Models\Cms\ContentBlock;
use App\Support\Content;

/*
|--------------------------------------------------------------------------
| Content registry integrity
|--------------------------------------------------------------------------
| config/cms-content.php carries the Greek copy that used to live in the
| Blade templates. If a default goes missing, or a template asks for a key the
| registry does not declare, the page silently renders an empty string — so
| these run before and after every template rewrite.
*/

/** Walk every declared field as [pageKey, field]. */
function registryFields(): array
{
    $out = [];

    foreach (config('cms-content') as $pageKey => $page) {
        foreach ($page['groups'] ?? [] as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                $out[] = [$pageKey, $field];
            }
        }
    }

    return $out;
}

it('declares a non-empty default for every field', function () {
    $missing = [];

    foreach (registryFields() as [$pageKey, $field]) {
        // An image has no text to fall back to; it is simply absent until
        // somebody uploads one, and every template guards for that.
        if ($field['type'] === 'image') {
            continue;
        }

        $default = $field['default'] ?? null;

        $empty = $default === null
            || $default === ''
            || (is_array($default) && $default === []);

        if ($empty) {
            $missing[] = "{$pageKey}.{$field['key']}";
        }
    }

    // This is the guarantee that the site renders identically with an empty
    // content_blocks table.
    expect($missing)->toBe([]);
});

it('gives every default the shape its type implies', function () {
    $wrong = [];

    foreach (registryFields() as [$pageKey, $field]) {
        $default = $field['default'] ?? null;
        $id = "{$pageKey}.{$field['key']} ({$field['type']})";

        $ok = match ($field['type']) {
            'lines', 'repeater', 'collection' => is_array($default),
            'toggle' => is_bool($default ?? false),
            'image' => true,
            default => is_string($default),
        };

        if (! $ok) {
            $wrong[] = $id;
        }
    }

    expect($wrong)->toBe([]);
});

it('keeps every repeater default within its own min and max', function () {
    $wrong = [];

    foreach (registryFields() as [$pageKey, $field]) {
        if ($field['type'] !== 'repeater') {
            continue;
        }

        $count = count($field['default'] ?? []);
        $min = $field['min'] ?? 0;
        $max = $field['max'] ?? PHP_INT_MAX;

        if ($count < $min || $count > $max) {
            $wrong[] = "{$pageKey}.{$field['key']}: {$count} items, expected {$min}–{$max}";
        }
    }

    expect($wrong)->toBe([]);
});

it('gives every repeater item every sub-field the registry declares', function () {
    $wrong = [];

    foreach (registryFields() as [$pageKey, $field]) {
        if ($field['type'] !== 'repeater') {
            continue;
        }

        $subKeys = array_column($field['item'] ?? [], 'key');

        foreach ($field['default'] ?? [] as $i => $item) {
            foreach ($subKeys as $subKey) {
                if (! array_key_exists($subKey, $item)) {
                    $wrong[] = "{$pageKey}.{$field['key']}[{$i}] missing {$subKey}";
                }
            }
        }
    }

    expect($wrong)->toBe([]);
});

it('contains no closures, so config:cache works in production', function () {
    $hasClosure = function (mixed $value) use (&$hasClosure): bool {
        if ($value instanceof Closure) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($hasClosure($item)) {
                    return true;
                }
            }
        }

        return false;
    };

    expect($hasClosure(config('cms-content')))->toBeFalse();
});

it('declares every key the Blade templates actually ask for', function () {
    // The reverse check: a typo in a template is otherwise a silent empty
    // string on a live page.
    $used = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'))
    );

    foreach ($files as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        // Skip the admin panel: it renders the registry, it does not read keys.
        if (str_contains(str_replace('\\', '/', $file->getPathname()), '/views/admin/')) {
            continue;
        }

        preg_match_all(
            '/\bcontent(?:_lines|_items|_image)?\(\s*[\'"]([^\'"]+)[\'"]/',
            (string) file_get_contents($file->getPathname()),
            $matches
        );

        foreach ($matches[1] as $key) {
            $used[$key] = true;
        }
    }

    $declared = [];
    foreach (registryFields() as [$pageKey, $field]) {
        $declared["{$pageKey}.{$field['key']}"] = true;
    }

    expect(array_values(array_diff(array_keys($used), array_keys($declared))))->toBe([]);
});

/* --------------------------------------------------------------- behaviour */

it('falls back to the registry default when nothing is stored', function () {
    expect(ContentBlock::count())->toBe(0)
        ->and(content('home.hero.title'))->toBe('Στήριξη σε κάθε βήμα της μάθησης');
});

it('prefers a stored override', function () {
    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => 'Νέος τίτλος',
    ]);

    expect(content('home.hero.title'))->toBe('Νέος τίτλος');
});

it('falls back when a stored value is emptied', function () {
    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => '',
    ]);

    expect(content('home.hero.title'))->toBe('Στήριξη σε κάθε βήμα της μάθησης');
});

it('busts the forever cache on save', function () {
    expect(content('home.hero.title'))->toBe('Στήριξη σε κάθε βήμα της μάθησης');

    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => 'Αλλαγμένο',
    ]);

    expect(content('home.hero.title'))->toBe('Αλλαγμένο');
});

it('reads a lines field as an array', function () {
    expect(content_lines('shared.nav'))->toBeArray();

    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => "πρώτο\nδεύτερο\n\n  τρίτο  ",
    ]);

    // Blank lines dropped, each entry trimmed.
    expect(app(Content::class)->lines('home.hero.title'))
        ->toBe(['πρώτο', 'δεύτερο', 'τρίτο']);
});

it('reads a collection default as a list of items', function () {
    $services = content_items('services.services');

    // Counted from the registry rather than pinned: the centre adds and
    // removes services, and a test that says "three" would fail the day they
    // do — telling them nothing about whether anything is broken.
    expect($services)->toHaveCount(count(shippedServices()))
        ->and($services)->not->toBeEmpty()
        ->and($services[0]['name'])->toBe(shippedServices()[0]['name'])
        ->and($services[0]['bullets'])->toBeArray();
});

it('reads a stored collection override, however short', function () {
    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'services',
        'locale' => 'el',
        'value' => json_encode([
            ['name' => 'Πρώτη', 'card_body' => '', 'lead' => '', 'bullets' => ['a'], 'note' => ''],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    expect(content_items('services.services'))->toHaveCount(1)
        ->and(content_items('services.services')[0]['name'])->toBe('Πρώτη');
});

it('survives a broken stored collection by falling back to the default', function () {
    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'services',
        'locale' => 'el',
        'value' => 'this is not json',
    ]);

    expect(content_items('services.services'))->toHaveCount(count(shippedServices()));
});

it('lets a collection be emptied, unlike a repeater', function () {
    // Removing every service is a decision somebody made; a repeater coming
    // back empty is a truncated submit, and the two must not be confused.
    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'services',
        'locale' => 'el',
        'value' => '[]',
    ]);

    expect(content_items('services.services'))->toBe([]);

    ContentBlock::create([
        'page_key' => 'shared',
        'field_key' => 'nav',
        'locale' => 'el',
        'value' => '[]',
    ]);

    expect(content_items('shared.nav'))->toHaveCount(6);
});
