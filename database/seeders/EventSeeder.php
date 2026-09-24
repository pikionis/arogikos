<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cms\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // Upcoming
            [
                'slug' => 'ergastiri-oria-me-agapi',
                'title' => 'Εργαστήρι για γονείς: «Όρια με αγάπη»',
                'starts_at' => '2026-06-14 18:00',
                'location' => 'Αίθουσα Αρωγής, Κως',
                'body' => 'Πρακτικές στρατηγικές για ξεκάθαρα όρια χωρίς συγκρούσεις, με τον ψυχολόγο Γιώργο Παπαδάκη. Διάρκεια 18:00–20:00.',
            ],
            [
                'slug' => 'anoixti-imera-gnorimias',
                'title' => 'Ανοιχτή ημέρα γνωριμίας',
                'starts_at' => '2026-06-28 10:00',
                'location' => 'Αρωγή, Κως',
                'body' => 'Ελάτε να γνωρίσετε την ομάδα, να δείτε τους χώρους και να ρωτήσετε ό,τι θέλετε — με καφέ και χαμόγελα. 10:00–14:00.',
            ],
            [
                'slug' => 'omada-meletis-kalokairiou',
                'title' => 'Ομάδα μελέτης καλοκαιριού — έναρξη',
                'starts_at' => '2026-07-05 09:30',
                'location' => 'Αρωγή, Κως',
                'body' => 'Δημιουργική επανάληψη και οργάνωση μελέτης για το επόμενο σχολικό έτος, σε μικρές ομάδες.',
            ],
            [
                'slug' => 'viomatiko-efivon-poios-eimai',
                'title' => 'Βιωματικό εργαστήρι εφήβων: «Ποιος είμαι;»',
                'starts_at' => '2026-07-19 17:00',
                'location' => 'Αίθουσα Αρωγής',
                'body' => 'Δραστηριότητες αυτογνωσίας στο πλαίσιο του επαγγελματικού προσανατολισμού. 17:00–19:00.',
            ],

            // Past
            [
                'slug' => 'imerida-dyslexia-stin-praxi',
                'title' => 'Ημερίδα: «Δυσλεξία στην πράξη»',
                'starts_at' => '2026-03-22 10:00',
                'location' => 'Πνευματικό Κέντρο Κω',
                'body' => 'Ανοιχτή ημερίδα για εκπαιδευτικούς και γονείς, με πάνω από 80 συμμετοχές.',
            ],
            [
                'slug' => 'ergastiri-diaxeirisi-agxous',
                'title' => 'Εργαστήρι: «Διαχείριση άγχους εξετάσεων»',
                'starts_at' => '2026-02-10 17:00',
                'location' => 'Αίθουσα Αρωγής',
                'body' => 'Βιωματικό εργαστήρι για μαθητές Λυκείου εν όψει των εξετάσεων.',
            ],
            [
                'slug' => 'xristougenniatiki-giorti',
                'title' => 'Χριστουγεννιάτικη γιορτή Αρωγής',
                'starts_at' => '2025-12-14 17:30',
                'location' => 'Αρωγή, Κως',
                'body' => 'Μια ζεστή απογευματινή γιορτή για τα παιδιά και τις οικογένειές μας.',
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['slug' => $event['slug']],
                [
                    'title' => $event['title'],
                    'starts_at' => Carbon::parse($event['starts_at']),
                    'location' => $event['location'],
                    'body' => $event['body'],
                    'is_published' => true,
                ]
            );
        }
    }
}
