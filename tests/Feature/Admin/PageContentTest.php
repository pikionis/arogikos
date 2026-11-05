<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\ContentBlock;
use App\Models\Cms\Page;
use App\Models\User;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| "Κείμενα σελίδων"
|--------------------------------------------------------------------------
| The generated per-page copy editor. The behaviour worth pinning down is the
| override lifecycle: a row is written only when the value differs from the
| registry default, and deleted again when it matches — so restoring the
| original is free, and new default copy shipped in a deploy still reaches
| pages nobody has customised.
*/

beforeEach(fn () => $this->seed(SettingsSeeder::class));

function pagesUser(Role $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user->refresh();
}

/* ------------------------------------------------------------------ access */

it('forbids an editor from the page copy screens', function () {
    $editor = pagesUser(Role::Editor);

    $this->actingAs($editor)->get(route('admin.pages.index'))->assertForbidden();
    $this->actingAs($editor)->get(route('admin.pages.edit', 'home'))->assertForbidden();
});

it('lets an admin open the page list and one page', function () {
    $admin = pagesUser(Role::Admin);

    $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk()->assertSeeText('Αρχική');
    $this->actingAs($admin)->get(route('admin.pages.edit', 'home'))->assertOk();
});

it('404s on a page key that is not in the registry', function () {
    $this->actingAs(pagesUser(Role::Owner))
        ->get(route('admin.pages.edit', 'den-yparxei'))
        ->assertNotFound();
});

it('renders every page of the registry', function (string $pageKey) {
    $this->actingAs(pagesUser(Role::Owner))
        ->get(route('admin.pages.edit', $pageKey))
        ->assertOk();
})->with([
    'shared', 'home', 'about', 'services', 'blog', 'events', 'gallery', 'contact', 'error404',
]);

it('prefills the form with the copy the site currently shows', function () {
    $this->actingAs(pagesUser(Role::Owner))
        ->get(route('admin.pages.edit', 'home'))
        ->assertOk()
        ->assertSee('Στήριξη σε κάθε βήμα της μάθησης', false);
});

it('has a dataset above covering every page in the registry', function () {
    // The list is hard-coded because Pest resolves datasets before the app
    // boots; this keeps it honest if a page is added to the registry.
    expect(array_keys(config('cms-content')))->toBe([
        'shared', 'home', 'about', 'services', 'blog', 'events', 'gallery', 'contact', 'error404',
    ]);
});

/* ---------------------------------------------------------------- saving */

it('writes an override and shows it on the public page', function () {
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['hero.title' => 'Νέος τίτλος ήρωα'],
    ])->assertRedirect();

    $this->assertDatabaseHas('content_blocks', [
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'value' => 'Νέος τίτλος ήρωα',
    ]);

    $this->get(route('home'))->assertOk()->assertSeeText('Νέος τίτλος ήρωα');
});

it('writes no row when the submitted value equals the shipped default', function () {
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['hero.title' => 'Στήριξη σε κάθε βήμα της μάθησης'],
    ]);

    // Nothing was customised, so nothing is stored — and a future change to
    // the default in config still reaches this page.
    $this->assertDatabaseMissing('content_blocks', [
        'page_key' => 'home',
        'field_key' => 'hero.title',
    ]);
});

it('deletes the override when the value is set back to the default', function () {
    ContentBlock::create([
        'page_key' => 'home',
        'field_key' => 'hero.title',
        'locale' => 'el',
        'value' => 'Κάτι άλλο',
    ]);

    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['hero.title' => 'Στήριξη σε κάθε βήμα της μάθησης'],
    ]);

    $this->assertDatabaseMissing('content_blocks', [
        'page_key' => 'home',
        'field_key' => 'hero.title',
    ]);

    $this->get(route('home'))->assertOk()->assertSeeText('Στήριξη σε κάθε βήμα της μάθησης');
});

it('records who made the change', function () {
    $owner = pagesUser(Role::Owner);

    $this->actingAs($owner)->put(route('admin.pages.update', 'home'), [
        'fields' => ['hero.title' => 'Αλλαγμένο από τον owner'],
    ]);

    $this->assertDatabaseHas('content_blocks', [
        'field_key' => 'hero.title',
        'updated_by' => $owner->getKey(),
    ]);
});

/* ----------------------------------------------------------- collections */

it('saves a repeater and propagates it to every template that reads it', function () {
    $services = content_items('services.services');
    $services[0]['name'] = 'Νέα Ονομασία Υπηρεσίας';

    $payload = [];
    foreach ($services as $i => $service) {
        $payload[$i] = [
            'name' => $service['name'],
            'card_body' => $service['card_body'],
            'lead' => $service['lead'],
            'bullets' => implode("\n", $service['bullets']),
            'note' => $service['note'],
        ];
    }

    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => $payload],
    ])->assertRedirect();

    $this->get(route('home'))->assertOk()->assertSeeText('Νέα Ονομασία Υπηρεσίας');
    $this->get(route('services'))->assertOk()->assertSeeText('Νέα Ονομασία Υπηρεσίας');
});

it('takes as many services as somebody adds, in the order given', function () {
    // The zig-zag rhythm is positional, so it carries on however many there
    // are — no clamping, unlike a repeater.
    $payload = [];
    foreach (range(0, 5) as $i) {
        $payload[$i] = ['name' => "Υπηρεσία {$i}", 'card_body' => '', 'lead' => '', 'bullets' => '', 'note' => ''];
    }

    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => $payload],
    ]);

    $saved = content_items('services.services');

    expect($saved)->toHaveCount(6)
        ->and($saved[0]['name'])->toBe('Υπηρεσία 0')
        ->and($saved[5]['name'])->toBe('Υπηρεσία 5');
});

it('drops a row nobody gave a name', function () {
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => [
            ['name' => 'Κρατιέται', 'card_body' => '', 'lead' => '', 'bullets' => '', 'note' => ''],
            ['name' => '  ', 'card_body' => '', 'lead' => '', 'bullets' => '', 'note' => ''],
        ]],
    ]);

    expect(content_items('services.services'))->toHaveCount(1);
});

it('stores a lines sub-field as a real list', function () {
    $services = content_items('services.services');

    $payload = [];
    foreach ($services as $i => $service) {
        $payload[$i] = [
            'name' => $service['name'],
            'card_body' => $service['card_body'],
            'lead' => $service['lead'],
            'bullets' => $i === 0 ? "Πρώτο\nΔεύτερο\n\n  Τρίτο  " : implode("\n", $service['bullets']),
            'note' => $service['note'],
        ];
    }

    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => $payload],
    ]);

    expect(content_items('services.services')[0]['bullets'])->toBe(['Πρώτο', 'Δεύτερο', 'Τρίτο']);

    $this->get(route('services'))->assertOk()->assertSeeText('Δεύτερο');
});

/* ------------------------------------------------- partial / malformed */

it('leaves fields out of the payload untouched', function () {
    // Regression: a submit carrying only one field used to write an empty
    // override for every OTHER field on the page.
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['hero.title' => 'Μόνο ο τίτλος'],
    ]);

    expect(ContentBlock::where('page_key', 'home')->pluck('field_key')->all())
        ->toBe(['hero.title']);

    // And the rest of the page still shows its original copy.
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Μόνο ο τίτλος')
        ->assertSeeText('Δίπλα στις οικογένειες της Κω από το 2022.')
        ->assertSeeText('Πώς μπορούμε να στηρίξουμε το παιδί σας');
});

it('never blanks a repeater band, even on a truncated submit', function () {
    // Regression: an empty repeater payload used to store "[]", which wiped
    // the four points of "Οι αρχές μας" off the home page.
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['why.items' => []],
    ]);

    expect(content_items('home.why.items'))->toHaveCount(4);

    $this->get(route('home'))->assertOk()->assertSeeText('Σεβασμός στον ρυθμό');
});

it('tops a short repeater submit back up from the shipped copy', function () {
    // The navigation is still a repeater: its six slots match six routes, so
    // a truncated submit must not leave the menu half missing.
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'shared'), [
        'fields' => ['nav' => [
            0 => ['label' => 'Μόνο μία'],
        ]],
    ]);

    $nav = content_items('shared.nav');

    expect($nav)->toHaveCount(6)
        ->and($nav[0]['label'])->toBe('Μόνο μία')
        // The rest fell back to the shipped labels.
        ->and($nav[1]['label'])->toBe('Η ομάδα μας');

    $this->get(route('home'))->assertOk()->assertSeeText('Μόνο μία');
});

it('survives a repeater row missing a sub-field', function () {
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['why.items' => [
            0 => ['title' => 'Μόνο τίτλος'],
            1 => ['title' => 'Β', 'text' => 'Κείμενο Β'],
            2 => ['title' => 'Γ', 'text' => 'Κείμενο Γ'],
            3 => ['title' => 'Δ', 'text' => 'Κείμενο Δ'],
        ]],
    ]);

    $items = content_items('home.why.items');

    expect($items)->toHaveCount(4)
        ->and($items[0]['title'])->toBe('Μόνο τίτλος')
        ->and($items[0]['text'])->toBe('');

    $this->get(route('home'))->assertOk()->assertSeeText('Μόνο τίτλος');
});

/* -------------------------------------------------------------- reset */

it('restores every original text for a page', function () {
    ContentBlock::create(['page_key' => 'home', 'field_key' => 'hero.title', 'locale' => 'el', 'value' => 'Α']);
    ContentBlock::create(['page_key' => 'home', 'field_key' => 'hero.lead', 'locale' => 'el', 'value' => 'Β']);
    ContentBlock::create(['page_key' => 'about', 'field_key' => 'hero.title', 'locale' => 'el', 'value' => 'Γ']);

    $this->actingAs(pagesUser(Role::Owner))
        ->delete(route('admin.pages.reset', 'home'))
        ->assertRedirect();

    expect(ContentBlock::where('page_key', 'home')->count())->toBe(0)
        // Other pages are untouched.
        ->and(ContentBlock::where('page_key', 'about')->count())->toBe(1);

    $this->get(route('home'))->assertOk()->assertSeeText('Στήριξη σε κάθε βήμα της μάθησης');
});

/* ------------------------------------------------------- sanitisation */

it('strips scripts from the story prose body', function () {
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'home'), [
        'fields' => ['story.body' => '<p>Η δική μας ιστορία</p><script>alert(1)</script>'],
    ]);

    expect(content('home.story.body'))->toContain('Η δική μας ιστορία')
        ->and(content('home.story.body'))->not->toContain('<script>');

    // And the public page renders it unescaped, so this must stay clean.
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Η δική μας ιστορία')
        ->assertDontSee('alert(1)', false);
});

it('ships a story rather than an empty space under the heading', function () {
    // The page shipped with nothing below "Ξεκινήσαμε με μια απλή πεποίθηση",
    // because the text lived in a database row nobody had filled in.
    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match('/<div class="story-prose.*?<\/div>/s', $html, $prose);

    expect(strip_tags($prose[0] ?? ''))->not->toBe('')
        ->and($prose[0])->toContain('<p>')
        ->and($html)->toContain(e(content('home.story.title')));
});

it('edits the About page SEO from the one place that owns it', function () {
    // SEO used to live on the pages table as well, which meant two fields for
    // the same tag that could disagree. It is the content registry now.
    $this->actingAs(pagesUser(Role::Owner))->put(route('admin.pages.update', 'about'), [
        'fields' => [
            'seo.title' => 'Ο τίτλος SEO μας για τη Google',
            'seo.description' => 'Η περιγραφή SEO μας για τη Google.',
        ],
    ])->assertRedirect();

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Ο τίτλος SEO μας για τη Google', false)
        ->assertSee('Η περιγραφή SEO μας για τη Google.', false);
});
