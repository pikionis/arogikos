<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cms\Album;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $albums = [
            [
                'title' => 'Δραστηριότητες',
                'slug' => 'drastiriotites',
                'description' => 'Στιγμές από τις ομαδικές και βιωματικές δράσεις μας.',
                'sort_order' => 1,
                'photos' => ['Ομαδική δραστηριότητα', 'Εργαστήρι ανάγνωσης', 'Ομάδα κοινωνικών δεξιοτήτων'],
            ],
            [
                'title' => 'Χώροι',
                'slug' => 'xoroi',
                'description' => 'Οι χώροι της Αρωγής, σχεδιασμένοι για παιδιά.',
                'sort_order' => 2,
                'photos' => ['Η αίθουσα συμβουλευτικής', 'Ο χώρος υποδοχής', 'Η βιβλιοθήκη μας'],
            ],
            [
                'title' => 'Εκδηλώσεις',
                'slug' => 'ekdiloseis',
                'description' => 'Γιορτές, ημερίδες και ανοιχτές ημέρες της κοινότητάς μας.',
                'sort_order' => 3,
                'photos' => ['Ανοιχτή ημέρα γνωριμίας', 'Χριστουγεννιάτικη γιορτή', 'Ημερίδα για εκπαιδευτικούς'],
            ],
        ];

        foreach ($albums as $data) {
            $photos = $data['photos'];
            unset($data['photos']);

            $album = Album::updateOrCreate(['slug' => $data['slug']], $data);

            // Refresh photos idempotently for repeated seeding.
            $album->photos()->delete();

            foreach ($photos as $index => $caption) {
                $album->photos()->create([
                    'caption' => $caption,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
