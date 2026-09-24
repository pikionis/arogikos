<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Settings registry
|--------------------------------------------------------------------------
| Describes the editable site settings so the admin form can be generated
| instead of hand-written. The keys match database/seeders/SettingsSeeder.php
| and whatever the Blade templates read through setting().
|
| Types: text | textarea | email | tel | url
*/

return [

    'organisation' => [
        'label' => 'Ο οργανισμός',
        'fields' => [
            ['key' => 'org_name', 'label' => 'Όνομα', 'type' => 'text'],
            ['key' => 'footer_tagline', 'label' => 'Σύντομη περιγραφή (footer)', 'type' => 'textarea'],
        ],
    ],

    'contact' => [
        'label' => 'Στοιχεία επικοινωνίας',
        'hint' => 'Εμφανίζονται στο footer και στη σελίδα Επικοινωνία.',
        'fields' => [
            ['key' => 'phone', 'label' => 'Τηλέφωνο (όπως εμφανίζεται)', 'type' => 'text'],
            ['key' => 'phone_href', 'label' => 'Τηλέφωνο για κλήση', 'type' => 'tel',
                'hint' => 'Διεθνής μορφή, π.χ. +302242029100. Χρησιμοποιείται στα κουμπιά κλήσης.'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'email'],
            ['key' => 'address', 'label' => 'Διεύθυνση', 'type' => 'text'],
            ['key' => 'maps_url', 'label' => 'Σύνδεσμος χάρτη', 'type' => 'url',
                'hint' => 'Πού οδηγεί η διεύθυνση όταν την πατήσει κάποιος. Άνοιγμα στο Google Maps → Κοινή χρήση → Αντιγραφή συνδέσμου. Αν μείνει κενό, η διεύθυνση δεν γίνεται σύνδεσμος.'],
            ['key' => 'hours', 'label' => 'Ωράριο', 'type' => 'text'],
        ],
    ],

    'form' => [
        'label' => 'Φόρμα επικοινωνίας',
        'fields' => [
            ['key' => 'contact_form_recipient', 'label' => 'Παραλήπτης μηνυμάτων', 'type' => 'email',
                'hint' => 'Πού στέλνονται τα μηνύματα της φόρμας.'],
        ],
    ],

    'social' => [
        'label' => 'Κοινωνικά δίκτυα',
        'hint' => 'Αφήστε κενό όποιο δεν χρησιμοποιείτε — ο σύνδεσμος δεν θα εμφανιστεί.',
        'fields' => [
            ['key' => 'instagram', 'label' => 'Instagram', 'type' => 'url'],
            ['key' => 'facebook', 'label' => 'Facebook', 'type' => 'url'],
        ],
    ],

    'integrations' => [
        'label' => 'Χάρτης & στατιστικά',
        'fields' => [
            ['key' => 'maps_embed_url', 'label' => 'Google Maps embed URL', 'type' => 'url',
                'hint' => 'Η διεύθυνση από το «Ενσωμάτωση χάρτη» του Google Maps.'],
            ['key' => 'ga_id', 'label' => 'Google Analytics ID', 'type' => 'text',
                'hint' => 'π.χ. G-XXXXXXX. Αν μείνει κενό, δεν φορτώνεται κανένα script παρακολούθησης.'],
        ],
    ],

];
