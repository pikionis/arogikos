<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\SettingsSeeder;

/*
|--------------------------------------------------------------------------
| Πηγαίνετε κατευθείαν στην υπηρεσία σας
|--------------------------------------------------------------------------
| Με δεκατέσσερις υπηρεσίες η σελίδα έγινε μακριά. Κάτω από την εισαγωγή
| γράφονται ονομαστικά όλες, και το πάτημα σε μία κατεβάζει τη σελίδα στη
| δική της ενότητα. Οι σύνδεσμοι και οι ενότητες γράφονται σε δύο διαφορετικά
| σημεία του template — εδώ ελέγχεται ότι δείχνουν πάντα στα ίδια σημεία.
*/

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(CategorySeeder::class);
});

it('names every service under the introduction', function () {
    $html = $this->get(route('services'))->assertOk()->getContent();

    [$hero] = explode('</section>', $html, 2);

    foreach (service_list() as $service) {
        expect($hero)->toContain('href="#'.$service['anchor'].'">'.e($service['name']).'</a>');
    }
});

it('gives every link a section to land on', function () {
    $html = $this->get(route('services'))->getContent();

    $missing = [];
    foreach (service_list() as $service) {
        if (! str_contains($html, 'id="'.$service['anchor'].'"')) {
            $missing[] = $service['name'];
        }
    }

    expect($missing)->toBe([]);
});

it('sends each link to its own service and no other', function () {
    storeServices([aService('Λογοθεραπεία'), aService('Εργοθεραπεία')]);

    $html = $this->get(route('services'))->getContent();
    $anchors = array_column(service_list(), 'anchor');

    // The id has to sit above the heading it belongs to, and below the
    // previous service's — otherwise the jump lands on the wrong band.
    $first = mb_strpos($html, 'id="'.$anchors[0].'"');
    $second = mb_strpos($html, 'id="'.$anchors[1].'"');

    expect($first)->toBeLessThan(mb_strpos($html, '<h2>Λογοθεραπεία</h2>'))
        ->and(mb_strpos($html, '<h2>Λογοθεραπεία</h2>'))->toBeLessThan($second)
        ->and($second)->toBeLessThan(mb_strpos($html, '<h2>Εργοθεραπεία</h2>'));
});

it('follows a service that was renamed in the panel', function () {
    storeServices([aService('Παλιό Όνομα'), aService('Δεύτερη')]);
    expect(service_list()[0]['anchor'])->toBe('palio-onoma');

    storeServices([aService('Καινούριο Όνομα'), aService('Δεύτερη')]);

    $html = $this->get(route('services'))->getContent();

    expect($html)->toContain('href="#kainourio-onoma">Καινούριο Όνομα</a>')
        ->and($html)->toContain('id="kainourio-onoma"')
        ->and($html)->not->toContain('palio-onoma');
});

it('follows a service that was dragged somewhere else', function () {
    storeServices([aService('Πρώτη'), aService('Δεύτερη')]);
    storeServices([aService('Δεύτερη'), aService('Πρώτη')]);

    $html = $this->get(route('services'))->getContent();

    // Links in the new order, and each still on its own band.
    expect(mb_strpos($html, 'href="#deuteri"'))->toBeLessThan(mb_strpos($html, 'href="#prwti"'))
        ->and(mb_strpos($html, 'id="deuteri"'))->toBeLessThan(mb_strpos($html, 'id="prwti"'));
});

it('keeps two services with the same name apart', function () {
    storeServices([aService('Ίδιο Όνομα'), aService('Ίδιο Όνομα')]);

    $anchors = array_column(service_list(), 'anchor');

    expect($anchors[0])->not->toBe($anchors[1])
        ->and($this->get(route('services'))->getContent())
        ->toContain('id="'.$anchors[1].'"');
});

it('keeps the rest in step around an unnamed service', function () {
    storeServices([aService('Πρώτη'), aService(''), aService('Τρίτη')]);

    $html = $this->get(route('services'))->assertOk()->getContent();
    $anchors = array_column(service_list(), 'anchor');
    $last = $anchors[count($anchors) - 1];

    preg_match('/<nav class="jump-list.*?<\/nav>/s', $html, $nav);

    // A row with no name has nothing to write a link with — but the band
    // after it must still be reachable, and still be its own band.
    expect(substr_count($nav[0], '<a href="#'))->toBe(2)
        ->and($html)->toContain('id="'.$last.'"')
        ->and($nav[0])->toContain('href="#'.$last.'"');
});

it('leaves the list out when there is only one service', function () {
    storeServices([aService('Η μόνη')]);

    // A menu of one is noise above the only thing on the page.
    expect($this->get(route('services'))->getContent())->not->toContain('jump-list');
});
