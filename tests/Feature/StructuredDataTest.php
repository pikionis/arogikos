<?php

declare(strict_types=1);

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Setting;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| schema.org structured data
|--------------------------------------------------------------------------
| A malformed JSON-LD block fails silently — the page looks fine and Google
| simply ignores it. These assert the markup is present, parses, and carries
| the details that local search actually uses.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

/** Every JSON-LD node on a page, flattened out of any @graph. */
function schemaNodes(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    $nodes = [];

    foreach ($m[1] as $json) {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        foreach ($decoded['@graph'] ?? [$decoded] as $node) {
            $nodes[] = $node;
        }
    }

    return $nodes;
}

function nodeOfType(array $nodes, string $type): ?array
{
    foreach ($nodes as $node) {
        $types = (array) ($node['@type'] ?? []);
        if (in_array($type, $types, true)) {
            return $node;
        }
    }

    return null;
}

/* ------------------------------------------------------------------ parses */

it('emits structured data that parses on every public page', function (string $routeName) {
    $html = $this->get(route($routeName))->assertOk()->getContent();

    // json_decode throws inside schemaNodes if anything is malformed.
    expect(schemaNodes($html))->not->toBeEmpty();
})->with(['home', 'about', 'services', 'blog.index', 'events', 'gallery', 'contact']);

/* ---------------------------------------------------------- local business */

it('describes the centre as a local business a map can place', function () {
    $nodes = schemaNodes($this->get(route('home'))->assertOk()->getContent());
    $biz = nodeOfType($nodes, 'LocalBusiness');

    expect($biz)->not->toBeNull()
        ->and($biz['name'])->toBe('Αρωγή')
        ->and($biz['telephone'])->toBe('+302242021429')
        ->and($biz['email'])->toBe('arogicenter@gmail.com')
        // Coordinates are what put a pin on the map pack.
        ->and($biz['geo']['latitude'])->toBe(36.8884583)
        ->and($biz['geo']['longitude'])->toBe(27.2767477)
        ->and($biz['openingHours'])->toBe('Mo-Fr 13:00-21:00');
});

it('breaks the address into the parts Google indexes', function () {
    $biz = nodeOfType(schemaNodes($this->get(route('home'))->getContent()), 'LocalBusiness');

    expect($biz['address'])->toMatchArray([
        '@type' => 'PostalAddress',
        'streetAddress' => 'Ασκληπιού 12',
        'addressLocality' => 'Κως',
        'postalCode' => '85300',
        'addressCountry' => 'GR',
    ]);
});

it('is also an educational organisation, not just a shop', function () {
    $nodes = schemaNodes($this->get(route('home'))->getContent());

    expect(nodeOfType($nodes, 'EducationalOrganization'))->not->toBeNull();
});

it('lists the social profiles and the map link', function () {
    $biz = nodeOfType(schemaNodes($this->get(route('home'))->getContent()), 'LocalBusiness');

    expect($biz['sameAs'])->toContain('https://instagram.com/arogicenter')
        ->and($biz['hasMap'])->toContain('cid=');
});

it('falls back to the plain address when the parts are not filled in', function () {
    foreach (['street_address', 'locality'] as $key) {
        Setting::where('key', $key)->delete();
    }
    Settings::flush();

    $biz = nodeOfType(schemaNodes($this->get(route('home'))->getContent()), 'LocalBusiness');

    // Still an address Google can read, rather than a broken stub.
    expect($biz['address']['streetAddress'])->toBe('Ασκληπιού 12, Κως 85300');
});

it('omits coordinates rather than emitting nonsense', function () {
    Setting::updateOrCreate(['key' => 'geo_lat'], ['value' => 'όχι αριθμός']);
    Settings::flush();

    $biz = nodeOfType(schemaNodes($this->get(route('home'))->getContent()), 'LocalBusiness');

    expect($biz)->not->toHaveKey('geo');
});

/* ----------------------------------------------------------------- services */

it('publishes each service the centre offers', function () {
    $nodes = schemaNodes($this->get(route('home'))->getContent());

    $services = array_values(array_filter(
        $nodes,
        fn (array $n) => in_array('Service', (array) ($n['@type'] ?? []), true)
    ));

    // Every service the centre lists, whatever they are called today.
    expect($services)->toHaveCount(count(content_items('services.services')))
        ->and(array_column($services, 'name'))
        ->toBe(array_column(content_items('services.services'), 'name'));
});

it('follows a service renamed in the panel', function () {
    $services = content_items('services.services');
    $services[0]['name'] = 'Νέα Ονομασία';

    ContentBlock::create([
        'page_key' => 'services',
        'field_key' => 'services',
        'locale' => 'el',
        'value' => json_encode($services, JSON_UNESCAPED_UNICODE),
    ]);

    $nodes = schemaNodes($this->get(route('home'))->getContent());

    expect(array_column($nodes, 'name'))->toContain('Νέα Ονομασία');
});

/* ----------------------------------------------------------------- article */

it('marks up an article as a blog posting with its author and date', function () {
    $author = Author::factory()->create(['name' => 'Μαρία Κωνσταντίνου', 'role_title' => 'Ειδική Παιδαγωγός']);

    $article = Article::factory()->for($author)->create([
        'title' => 'Δυσλεξία στην πράξη',
        'excerpt' => 'Τι να προσέξετε.',
        'category' => 'learning',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $nodes = schemaNodes($this->get(route('blog.show', $article->slug))->assertOk()->getContent());
    $post = nodeOfType($nodes, 'BlogPosting');

    expect($post)->not->toBeNull()
        ->and($post['headline'])->toBe('Δυσλεξία στην πράξη')
        ->and($post['description'])->toBe('Τι να προσέξετε.')
        ->and($post['author']['name'])->toBe('Μαρία Κωνσταντίνου')
        ->and($post['author']['jobTitle'])->toBe('Ειδική Παιδαγωγός')
        ->and($post['articleSection'])->toBe('Μαθησιακές δυσκολίες')
        ->and($post)->toHaveKey('datePublished');
});

/* ------------------------------------------------------------- breadcrumbs */

it('gives an article a full breadcrumb trail', function () {
    $article = Article::factory()->for(Author::factory())->create([
        'title' => 'Ένα άρθρο',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $trail = nodeOfType(
        schemaNodes($this->get(route('blog.show', $article->slug))->getContent()),
        'BreadcrumbList'
    );

    expect(array_column($trail['itemListElement'], 'name'))
        ->toBe(['Αρχική', 'Blog', 'Ένα άρθρο'])
        ->and($trail['itemListElement'][0]['position'])->toBe(1);
});

it('gives the inner pages a breadcrumb trail too', function (string $routeName, string $label) {
    $this->seed(PageSeeder::class);

    $trail = nodeOfType(schemaNodes($this->get(route($routeName))->getContent()), 'BreadcrumbList');

    expect($trail)->not->toBeNull()
        ->and(array_column($trail['itemListElement'], 'name'))->toBe(['Αρχική', $label]);
})->with([
    ['about', 'Σχετικά με εμάς'],
    ['services', 'Υπηρεσίες'],
    ['contact', 'Επικοινωνία'],
    ['gallery', 'Gallery'],
]);

/* ------------------------------------------------------------- resilience */

it('cannot be broken out of by content containing a script tag', function () {
    // A settings value is editable in the panel; an unescaped </script> in one
    // would end the JSON-LD block and spill the rest of the page as markup.
    Setting::updateOrCreate(['key' => 'footer_tagline'], ['value' => 'Κακό </script><img src=x> κείμενο']);
    Settings::flush();

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('</script><img src=x>');

    // And the block still parses.
    expect(schemaNodes($html))->not->toBeEmpty();
});
