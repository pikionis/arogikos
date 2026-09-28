<?php

declare(strict_types=1);

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
    ];
}
