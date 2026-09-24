<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cms\Category;
use Illuminate\Database\Seeder;

/**
 * Moves the four categories that used to live in config/arogi.php into the
 * table, keeping their keys so every existing article stays pointed at the
 * right one.
 *
 * Safe to re-run on every deploy: it only ever CREATES what is missing, so
 * categories the client has since renamed, recoloured or withdrawn are left
 * exactly as they are.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;

        foreach ((array) config('arogi.categories', []) as $key => $meta) {
            // firstOrCreate, NOT updateOrCreate: the deploy script runs this on
            // every release, and updateOrCreate would reset a label, colour or
            // motif the client had changed in the admin.
            Category::firstOrCreate(
                ['key' => $key],
                [
                    'label' => $meta['label'],
                    'color' => $meta['color'],
                    'motif' => $meta['motif'],
                    'sort_order' => ++$order * 10,
                ],
            );
        }
    }
}
