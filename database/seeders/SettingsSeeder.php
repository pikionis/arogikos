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

            'phone' => '22420 29 100',
            'phone_href' => '+302242029100',
            'email' => 'arogicenter@gmail.com',
            'contact_form_recipient' => 'arogicenter@gmail.com',
            'address' => 'Ιπποκράτους 24, Κως 85300',
            'hours' => 'Δευτέρα – Παρασκευή · 09:00–21:00',

            'instagram' => 'https://instagram.com/arogicenter',
            'facebook' => 'https://facebook.com/arogicenter',

            'maps_embed_url' => 'https://www.google.com/maps?q=Kos,Greece&z=14&output=embed',

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
