<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Event;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Editable copy actually reaches the page
|--------------------------------------------------------------------------
| The registry tests prove config/cms-content.php is well-formed. These prove
| the templates are wired to it: every default still renders (so the rewrite
| changed nothing a visitor can see), and an override really does replace it.
*/

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

/**
 * Keys that legitimately do not render on a bare page load. Each has its own
 * test below rather than being silently tolerated.
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
        'blog.article_crumb', 'blog.read_suffix',
        // Shown only when a filter comes back with nothing.
        'blog.empty', 'blog.empty_link',
    ];
}

/**
 * Every scalar default declared for a page must appear on that page.
 *
 * This is the regression net for pulling ~90 Greek literals out of the Blade
 * templates: if one was dropped or mistyped, its default stops appearing.
 */
it('renders every declared default on its page', function (string $pageKey, string $routeName) {
    $html = $this->get(route($routeName))->assertOk()->getContent();

    $missing = [];

    foreach (config("cms-content.{$pageKey}.groups", []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (! in_array($field['type'], ['text', 'textarea'], true)) {
                continue;
            }

            if (in_array("{$pageKey}.{$field['key']}", conditionalKeys(), true)) {
                continue;
            }

            $default = $field['default'] ?? '';

            if ($default === '' || ! str_contains($html, e($default))) {
                $missing[] = "{$pageKey}.{$field['key']} — ".mb_substr($default, 0, 40);
            }
        }
    }

    expect($missing)->toBe([]);
})->with([
    'home' => ['home', 'home'],
    'about' => ['about', 'about'],
    'services' => ['services', 'services'],
    'blog' => ['blog', 'blog.index'],
    'events' => ['events', 'events'],
    'gallery' => ['gallery', 'gallery'],
    'contact' => ['contact', 'contact'],
]);

it('renders the shared service names on home, services and the footer', function () {
    // One repeater, three templates — the drift this was built to prevent.
    foreach (['home', 'services', 'contact'] as $routeName) {
        $html = $this->get(route($routeName))->assertOk()->getContent();

        // The footer column appears on every page.
        expect($html)->toContain('Μαθησιακή Υποστήριξη');
    }
});

it('renders the four why-Arogi points on home', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    // Looked up by key, not by position: adding a group to the page must not
    // silently point this test at something else.
    foreach (content_items('home.why.items') as $item) {
        expect($html)->toContain(e($item['title']));
    }
});

it('renders the service bullet lists on the services page', function () {
    $html = $this->get(route('services'))->assertOk()->getContent();

    foreach (content_items('shared.services') as $service) {
        foreach ($service['bullets'] as $bullet) {
            expect($html)->toContain(e($bullet));
        }
    }
});

/* --------------------------------------------------------------- overrides */

it('renders the upcoming-events band once an event is scheduled', function () {
    Event::factory()->upcoming()->create(['is_published' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Επερχόμενες εκδηλώσεις')
        ->assertSeeText('Όλες οι εκδηλώσεις');
});

it('renders the related-articles heading on an article page', function () {
    $article = Article::published()->firstOrFail();

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Σχετικά άρθρα')
        ->assertSeeText('Διαβάστε επίσης');
});

it('renders the article breadcrumb and reading time on an article page', function () {
    $article = Article::published()->firstOrFail();

    $this->get(route('blog.show', $article->slug))
        ->assertOk()
        ->assertSeeText('Άρθρο')
        ->assertSeeText('ανάγνωση');
});

it('renders the empty-list copy when a filter finds nothing', function () {
    Article::query()->update(['category' => 'learning']);

    $this->get(route('blog.index', ['category' => 'career']))
        ->assertOk()
        ->assertSeeText('Δεν βρέθηκαν άρθρα σε αυτή την κατηγορία.')
        ->assertSeeText('Δείτε όλα τα άρθρα');
});

it('renders the contact form error message after a failed submit', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), ['name' => '', 'email' => '', 'message' => ''])
        ->assertRedirect(route('contact'));

    $this->followingRedirects()
        ->from(route('contact'))
        ->post(route('contact.store'), ['name' => '', 'email' => '', 'message' => ''])
        ->assertSeeText('Παρακαλούμε ελέγξτε τα πεδία με κόκκινο και δοκιμάστε ξανά.');
});

it('shows an edited hero title on the live home page', function () {
    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => 'Ένας εντελώς νέος τίτλος',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Ένας εντελώς νέος τίτλος')
        ->assertDontSeeText('Στήριξη σε κάθε βήμα της μάθησης');
});

it('propagates an edited service name to home, services and the footer', function () {
    $services = content_items('shared.services');
    $services[0]['name'] = 'Μετονομασμένη Υπηρεσία';

    ContentBlock::create([
        'page_key' => 'shared',
        'field_key' => 'services',
        'locale' => 'el',
        'value' => json_encode($services, JSON_UNESCAPED_UNICODE),
    ]);

    // Home card, services section heading, and the footer column on both.
    $this->get(route('home'))->assertOk()->assertSeeText('Μετονομασμένη Υπηρεσία');
    $this->get(route('services'))->assertOk()->assertSeeText('Μετονομασμένη Υπηρεσία');
    $this->get(route('gallery'))->assertOk()->assertSeeText('Μετονομασμένη Υπηρεσία');
});

it('shows edited navigation labels in both the header and the footer', function () {
    $nav = content_items('shared.nav');
    $nav[0]['label'] = 'Αρχική Σελίδα';

    ContentBlock::create([
        'page_key' => 'shared',
        'field_key' => 'nav',
        'locale' => 'el',
        'value' => json_encode($nav, JSON_UNESCAPED_UNICODE),
    ]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    // Header nav, mobile menu and footer column — three occurrences.
    expect(substr_count($html, 'Αρχική Σελίδα'))->toBeGreaterThanOrEqual(3);
});

it('shows edited 404 copy on the branded error page', function () {
    $this->seed(SettingsSeeder::class);

    ContentBlock::create([
        'page_key' => 'error404',
        'field_key' => 'title',
        'locale' => 'el',
        'value' => 'Ουπς, χαθήκαμε',
    ]);

    $this->get('/mia-diadromi-pou-den-yparxei')
        ->assertNotFound()
        ->assertSeeText('Ουπς, χαθήκαμε');
});

/* ------------------------------------------------- every field, every page */

/**
 * The promise the panel makes: change a word, see it on the site.
 *
 * The test above proves the defaults arrive. This proves the wiring runs the
 * other way too — that each field is actually read from the database and not
 * quietly pinned to its default by a template that forgot to ask. Every
 * scalar on a page is overwritten with its own sentinel and the page is
 * fetched once, so a field that is declared but never rendered cannot hide.
 */
it('puts an edit to any field straight onto the page', function (string $pageKey, string $routeName) {
    $sentinels = [];

    foreach (config("cms-content.{$pageKey}.groups", []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (! in_array($field['type'], ['text', 'textarea'], true)) {
                continue;
            }

            if (in_array("{$pageKey}.{$field['key']}", conditionalKeys(), true)) {
                continue;
            }

            $mark = 'ΑΛΛΑΓΜΕΝΟ'.count($sentinels).'ΤΕΛΟΣ';
            $sentinels["{$pageKey}.{$field['key']}"] = $mark;

            ContentBlock::create([
                'page_key' => $pageKey,
                'field_key' => $field['key'],
                'locale' => 'el',
                'value' => $mark,
            ]);
        }
    }

    expect($sentinels)->not->toBeEmpty("{$pageKey} declares no editable text at all");

    $html = $this->get(route($routeName))->assertOk()->getContent();

    $stuck = [];
    foreach ($sentinels as $key => $mark) {
        if (! str_contains($html, $mark)) {
            $stuck[] = $key;
        }
    }

    expect($stuck)->toBe([]);
})->with([
    'home' => ['home', 'home'],
    'about' => ['about', 'about'],
    'services' => ['services', 'services'],
    'blog' => ['blog', 'blog.index'],
    'events' => ['events', 'events'],
    'gallery' => ['gallery', 'gallery'],
    'contact' => ['contact', 'contact'],
]);

it('puts an edit to the shared header and footer onto every page', function () {
    $sentinels = [];

    foreach (config('cms-content.shared.groups', []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (! in_array($field['type'], ['text', 'textarea'], true)) {
                continue;
            }

            $mark = 'ΚΟΙΝΟ'.count($sentinels).'ΤΕΛΟΣ';
            $sentinels[$field['key']] = $mark;

            ContentBlock::create([
                'page_key' => 'shared',
                'field_key' => $field['key'],
                'locale' => 'el',
                'value' => $mark,
            ]);
        }
    }

    // Whatever is shared must be shared everywhere, not just on the home page.
    $stuck = [];

    foreach (['home', 'services', 'gallery', 'contact'] as $routeName) {
        $html = $this->get(route($routeName))->assertOk()->getContent();

        foreach ($sentinels as $key => $mark) {
            if (! str_contains($html, $mark)) {
                $stuck[] = "shared.$key on $routeName";
            }
        }
    }

    expect($stuck)->toBe([]);
});
