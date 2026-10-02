<?php

declare(strict_types=1);

use App\Models\Cms\ContentBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Every test in Feature/ and Unit/ runs against the application TestCase
| with a fresh in-memory SQLite schema (RefreshDatabase). The testing DB is
| :memory: (see phpunit.xml), so tests never touch the seeded dev database.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Shared helpers
|--------------------------------------------------------------------------
*/

/**
 * Registry keys that legitimately do not render on a bare page load.
 *
 * Each has a test of its own; listing them here keeps a field that simply
 * was never wired from hiding behind "it is conditional".
 *
 * @return array<int, string>
 */
function conditionalKeys(): array
{
    return [
        // The home "upcoming events" band is hidden when nothing is scheduled.
        'home.events.eyebrow', 'home.events.title', 'home.events.link',
        // Related articles live on the article page, not the blog index.
        'blog.related_eyebrow', 'blog.related_title',
        // Only shown after a failed submit.
        'contact.form.error',
        // These belong to a single article, not to the list of them.
        'blog.article_crumb', 'blog.read_suffix', 'blog.author_eyebrow',
        // Shown only when a filter comes back with nothing.
        'blog.empty', 'blog.empty_link',
        // The share bar sits on a single article or event, not on the pages
        // that list them; ShareBarTest asserts each one where it does show.
        'shared.share.title', 'shared.share.button', 'shared.share.copy', 'shared.share.copied',
        'shared.share.more',
        // These belong to a single event's own page, not to the list of
        // them; EventPageTest asserts each one where it does show.
        'events.event_crumb', 'events.photos_title', 'events.more_title', 'events.back',
    ];
}

/**
 * The services as the registry ships them.
 *
 * Tests count from here rather than from a number written in the test: the
 * centre adds and removes services, and a pinned count would fail on the day
 * they do while telling nobody whether anything actually broke.
 *
 * @return array<int, array<string, mixed>>
 */
function shippedServices(): array
{
    foreach (config('cms-content.services.groups', []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (($field['key'] ?? '') === 'services') {
                return array_values((array) ($field['default'] ?? []));
            }
        }
    }

    return [];
}

/**
 * Put a list of services in the database, as the panel would.
 *
 * @param  array<int, array<string, mixed>>  $services
 */
function storeServices(array $services): void
{
    ContentBlock::updateOrCreate(
        ['page_key' => 'services', 'field_key' => 'services', 'locale' => 'el'],
        ['value' => json_encode($services, JSON_UNESCAPED_UNICODE)]
    );
}

/**
 * One service, filled in the way the panel fills one.
 *
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function aService(string $name, array $extra = []): array
{
    return $extra + [
        'icon' => 'network',
        'name' => $name,
        'lead' => "Εισαγωγή για $name.",
        'signs' => ["Σημάδι για $name"],
        'bullets' => ["Περιλαμβάνει για $name"],
        'process' => ["Βήμα για $name"],
        'note' => '',
        'card_body' => "Κάρτα για $name.",
        'on_home' => true,
    ];
}
