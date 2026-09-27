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

    'local_seo' => [
        'label' => 'Τοπική αναζήτηση (SEO)',
        'hint' => 'Τα διαβάζει η Google για να δείχνει το κέντρο σε χάρτες και τοπικές αναζητήσεις. Δεν εμφανίζονται στη σελίδα.',
        'fields' => [
            ['key' => 'street_address', 'label' => 'Οδός και αριθμός', 'type' => 'text',
                'hint' => 'Μόνο η οδός, π.χ. «Ασκληπιού 12».'],
            ['key' => 'locality', 'label' => 'Πόλη', 'type' => 'text'],
            ['key' => 'postal_code', 'label' => 'Ταχυδρομικός κώδικας', 'type' => 'text'],
            ['key' => 'region', 'label' => 'Περιοχή / Νομός', 'type' => 'text'],
            ['key' => 'geo_lat', 'label' => 'Γεωγραφικό πλάτος', 'type' => 'text',
                'hint' => 'Από το Google Maps: δεξί κλικ στο σημείο → το πρώτο νούμερο.'],
            ['key' => 'geo_lng', 'label' => 'Γεωγραφικό μήκος', 'type' => 'text',
                'hint' => 'Το δεύτερο νούμερο.'],
            ['key' => 'opening_hours_schema', 'label' => 'Ωράριο σε μορφή Google', 'type' => 'text',
                'hint' => 'Αγγλικά, π.χ. «Mo-Fr 13:00-21:00». Είναι η μηχανική εκδοχή του ωραρίου — το ωράριο που βλέπει ο κόσμος ορίζεται παραπάνω.'],
        ],
    ],

    'integrations' => [
        'label' => 'Χάρτης & στατιστικά',
        'fields' => [
            ['key' => 'maps_embed_url', 'label' => 'Google Maps embed URL', 'type' => 'url',
                'hint' => 'Η διεύθυνση από το «Ενσωμάτωση χάρτη» του Google Maps.'],
            ['key' => 'ga_id', 'label' => 'Google Analytics ID', 'type' => 'text',
                'hint' => 'Μορφή G-XXXXXXXXXX, από Analytics → Διαχείριση → Ροές δεδομένων. Αν μείνει κενό, δεν φορτώνεται κανένα script παρακολούθησης.'],
            ['key' => 'google_ads_id', 'label' => 'Google Ads ID', 'type' => 'text',
                'hint' => 'Μορφή AW-XXXXXXXXX, από Google Ads → Εργαλεία → Μετατροπές. Χρειάζεται μόνο αν τρέχετε διαφημίσεις.'],
            ['key' => 'google_ads_conversion_label', 'label' => 'Ετικέτα μετατροπής (Ads)', 'type' => 'text',
                'hint' => 'Το κομμάτι μετά την κάθετο, π.χ. AbC-D_efGh. Όταν συμπληρωθεί, κάθε υποβολή της φόρμας επικοινωνίας μετριέται ως μετατροπή.'],
        ],
    ],

];
