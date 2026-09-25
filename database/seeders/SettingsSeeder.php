<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cms\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'org_name' => 'Αρωγή',
            'footer_tagline' => 'Κέντρο εξειδικευμένης μαθησιακής υποστήριξης, συμβουλευτικής και επαγγελματικού προσανατολισμού στην Κω.',

            'phone' => '22420 21429',
            'phone_href' => '+302242021429',
            'email' => 'arogicenter@gmail.com',
            'contact_form_recipient' => 'arogicenter@gmail.com',
            'address' => 'Ασκληπιού 12, Κως 85300',
            'hours' => 'Δευτέρα – Παρασκευή · 13:00–21:00',

            'instagram' => 'https://instagram.com/arogicenter',
            'facebook' => 'https://facebook.com/arogicenter',

            // The centre itself, not a generic view of Kos: ?cid= addresses the
            // actual Google Maps listing, so the pin carries the name, photos
            // and reviews rather than a bare marker.
            // Structured-data twins of the address and hours above: Google
            // wants the parts separately and the hours in its own notation.
            'street_address' => 'Ασκληπιού 12',
            'locality' => 'Κως',
            'postal_code' => '85300',
            'region' => 'Δωδεκάνησα',
            'geo_lat' => '36.8884583',
            'geo_lng' => '27.2767477',
            'opening_hours_schema' => 'Mo-Fr 13:00-21:00',

            'maps_url' => 'https://maps.google.com/?cid=4202208299327602585',
            'maps_embed_url' => 'https://www.google.com/maps?q=36.8884583,27.2767477&z=17&output=embed',

            // Empty by default — the analytics snippet renders only when set.
            'ga_id' => '',
        ];

        foreach ($settings as $key => $value) {
            // firstOrCreate, NOT updateOrCreate: these are starting values, and
            // every one of them is editable from the panel. Overwriting would
            // silently reset the client's own phone number, address and — worst
            // of all — the address their contact form delivers to.
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
