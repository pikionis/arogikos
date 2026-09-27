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
 * Every scalar default declared for a page must appear on that page.
 *
 * This is the regression net for pulling ~90 Greek literals out of the Blade
 * templates: if one was dropped or mistyped, its default stops appearing.
 */
it('renders every declared default on its page', function (string $pageKey, string $routeName) {
    $html = $this->get(route($routeName))->assertOk()->getContent();

    // Keys that legitimately do not render on a bare page load. Each is
    // covered by its own test below instead of being silently tolerated.
    $conditional = [
        // The home "upcoming events" band is hidden when nothing is scheduled.
        'home.events.eyebrow', 'home.events.title', 'home.events.link',
        // Related articles live on the article page, not the blog index.
        'blog.related_eyebrow', 'blog.related_title',
        // Only shown after a failed submit.
        'contact.form.error',
    ];

    $missing = [];

    foreach (config("cms-content.{$pageKey}.groups", []) as $group) {
        foreach ($group['fields'] ?? [] as $field) {
            if (! in_array($field['type'], ['text', 'textarea'], true)) {
                continue;
            }

            if (in_array("{$pageKey}.{$field['key']}", $conditional, true)) {
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
