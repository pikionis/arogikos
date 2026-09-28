<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Cms\ContentBlock;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Οι υπηρεσίες ως συλλογή
|--------------------------------------------------------------------------
| Ήταν τρεις θέσεις σταθερές. Τώρα προστίθενται, αφαιρούνται και αλλάζουν
| σειρά, και η σελίδα και η Αρχική ακολουθούν — με τις επικεφαλίδες κοινές
| για όλες και τις ερωτήσεις πάντα στο τέλος.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

function boss(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => Role::Owner, 'is_active' => true])->save();

    return $user->refresh();
}

/**
 * @param  array<int, array<string, mixed>>  $services
 */
function storeServices(array $services): void
{
    ContentBlock::updateOrCreate(
        ['page_key' => 'services', 'field_key' => 'services', 'locale' => 'el'],
        ['value' => json_encode($services, JSON_UNESCAPED_UNICODE)]
    );
}

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

/* ------------------------------------------------------------ the page */

it('renders as many services as there are, in the order stored', function () {
    storeServices([aService('Πρώτη'), aService('Δεύτερη'), aService('Τρίτη'), aService('Τέταρτη')]);

    $html = $this->get(route('services'))->assertOk()->getContent();

    $positions = array_map(
        fn (string $name) => mb_strpos($html, '<h2>'.e($name).'</h2>'),
        ['Πρώτη', 'Δεύτερη', 'Τρίτη', 'Τέταρτη']
    );

    expect($positions)->not->toContain(false)
        // The zig-zag rhythm is positional, so a fourth band just carries on.
        ->and($positions)->toBe(array_values(collect($positions)->sort()->all()));
});

it('follows a service moved to the top', function () {
    storeServices([aService('Πρώτη'), aService('Δεύτερη')]);
    $html = $this->get(route('services'))->getContent();
    expect(mb_strpos($html, '<h2>Πρώτη</h2>'))->toBeLessThan(mb_strpos($html, '<h2>Δεύτερη</h2>'));

    storeServices([aService('Δεύτερη'), aService('Πρώτη')]);
    $html = $this->get(route('services'))->getContent();

    expect(mb_strpos($html, '<h2>Δεύτερη</h2>'))->toBeLessThan(mb_strpos($html, '<h2>Πρώτη</h2>'));
});

it('keeps the shared headings above every service', function () {
    storeServices([aService('Πρώτη'), aService('Δεύτερη'), aService('Τρίτη'), aService('Τέταρτη')]);

    $html = $this->get(route('services'))->getContent();

    // One set per service, written once in the panel.
    foreach (['Πότε να απευθυνθείτε', 'Τι περιλαμβάνει', 'Πώς δουλεύουμε'] as $heading) {
        expect(substr_count($html, e($heading)))->toBe(4);
    }
});

it('keeps the questions below all of them', function () {
    storeServices([aService('Πρώτη'), aService('Δεύτερη')]);

    $html = $this->get(route('services'))->getContent();

    expect(mb_strpos($html, e(content('services.faq_title'))))
        ->toBeGreaterThan(mb_strpos($html, '<h2>Δεύτερη</h2>'));
});

it('shows the illustration each service was given', function () {
    storeServices([aService('Πρώτη', ['icon' => 'summer'])]);

    $this->get(route('services'))->assertOk()->assertSee('cv-summer', false);
});

/* ------------------------------------------------------------ the home */

it('shows on the home page only what was chosen for it', function () {
    storeServices([
        aService('Μέσα', ['on_home' => true]),
        aService('Έξω', ['on_home' => false]),
    ]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    // Checked on the card itself: the footer column lists every service on
    // every page, chosen for the home band or not.
    expect($html)->toContain('<h3>Μέσα</h3>')
        ->and($html)->not->toContain('<h3>Έξω</h3>');

    // Both still belong to the services page.
    $this->get(route('services'))->assertOk()->assertSee('<h2>Έξω</h2>', false);
});

it('keeps the home row a row while three fit', function () {
    storeServices([aService('Μία'), aService('Δύο'), aService('Τρεις')]);

    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('grid-3')->and($html)->not->toContain('svc-carousel');
});

it('scrolls them once there are more than three', function () {
    storeServices([aService('Μία'), aService('Δύο'), aService('Τρεις'), aService('Τέσσερις')]);

    $html = $this->get(route('home'))->getContent();

    // Four would either shrink the cards or wrap into a ragged second line.
    expect($html)->toContain('svc-carousel')
        ->and($html)->toContain('Τέσσερις');
});

it('leaves the home band out when nothing was chosen', function () {
    storeServices([aService('Μόνο στη σελίδα', ['on_home' => false])]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('svc-card');
});

/* ----------------------------------------------------------- the panel */

it('offers the whole service on its own page', function () {
    $html = $this->actingAs(boss())
        ->get(route('admin.pages.edit', 'services'))->assertOk()->getContent();

    expect($html)->toContain('a-collection')
        ->and($html)->toContain('collectionField')
        // Drag handle and the add button.
        ->and($html)->toContain('a-drag')
        ->and($html)->toContain(e('Προσθήκη'));
});

it('adds a service through the form', function () {
    $existing = content_items('services.services');

    $payload = [];
    foreach ($existing as $i => $service) {
        $payload[$i] = [
            'icon' => $service['icon'], 'name' => $service['name'], 'lead' => $service['lead'],
            'signs' => implode("\n", $service['signs']), 'bullets' => implode("\n", $service['bullets']),
            'process' => implode("\n", $service['process']), 'note' => $service['note'],
            'card_body' => $service['card_body'], 'on_home' => '1',
        ];
    }
    $payload[] = [
        'icon' => 'play', 'name' => 'Μια Ολοκαίνουρια Υπηρεσία', 'lead' => 'Νέα υπηρεσία.',
        'signs' => "Ένα\nΔύο", 'bullets' => 'Κάτι', 'process' => 'Βήμα',
        'note' => '', 'card_body' => 'Κάρτα.', 'on_home' => '0',
    ];

    $this->actingAs(boss())->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => $payload],
    ])->assertRedirect();

    $saved = content_items('services.services');

    $last = count($existing);

    expect($saved)->toHaveCount($last + 1)
        ->and($saved[$last]['name'])->toBe('Μια Ολοκαίνουρια Υπηρεσία')
        ->and($saved[$last]['signs'])->toBe(['Ένα', 'Δύο'])
        ->and($saved[$last]['on_home'])->toBeFalse();

    $this->get(route('services'))->assertOk()->assertSee('<h2>Μια Ολοκαίνουρια Υπηρεσία</h2>', false);
    $this->get(route('home'))->assertOk()->assertDontSee('<h3>Μια Ολοκαίνουρια Υπηρεσία</h3>', false);
});

it('removes one by leaving it out of the submit', function () {
    $this->actingAs(boss())->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => [
            ['icon' => 'calm', 'name' => 'Η μόνη που μένει', 'lead' => '', 'signs' => '',
                'bullets' => '', 'process' => '', 'note' => '', 'card_body' => '', 'on_home' => '1'],
        ]],
    ])->assertRedirect();

    expect(content_items('services.services'))->toHaveCount(1);

    $this->get(route('services'))
        ->assertOk()
        ->assertSee('<h2>Η μόνη που μένει</h2>', false)
        ->assertDontSee('<h2>'.shippedServices()[0]['name'].'</h2>', false);
});

it('saves the order the rows arrived in', function () {
    $this->actingAs(boss())->put(route('admin.pages.update', 'services'), [
        'fields' => ['services' => [
            ['icon' => 'calm', 'name' => 'Τρίτη έγινε πρώτη', 'lead' => '', 'signs' => '',
                'bullets' => '', 'process' => '', 'note' => '', 'card_body' => '', 'on_home' => '1'],
            ['icon' => 'play', 'name' => 'Πρώτη έγινε δεύτερη', 'lead' => '', 'signs' => '',
                'bullets' => '', 'process' => '', 'note' => '', 'card_body' => '', 'on_home' => '1'],
        ]],
    ])->assertRedirect();

    $saved = content_items('services.services');

    expect($saved[0]['name'])->toBe('Τρίτη έγινε πρώτη')
        ->and($saved[1]['name'])->toBe('Πρώτη έγινε δεύτερη');
});

it('keeps the footer column in step with the list', function () {
    storeServices([aService('Μοναδική Υπηρεσία')]);

    // The footer shows on every page, so it must follow the same list.
    $this->get(route('contact'))->assertOk()->assertSeeText('Μοναδική Υπηρεσία');
});

it('tells Google about each one', function () {
    storeServices([aService('Μοναδική Υπηρεσία')]);

    $html = $this->get(route('home'))->getContent();

    expect($html)->toContain('"@type":"Service"')
        ->and($html)->toContain('Μοναδική Υπηρεσία');
});
