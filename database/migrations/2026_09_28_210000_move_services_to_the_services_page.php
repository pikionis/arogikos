<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Carries an edited copy of the three services to their new home.
 *
 * They used to live under `shared`, because the home page, the services page
 * and the footer all read them. They now live on the services page, where
 * everything about a service is managed together — but a centre that had
 * already rewritten them must not lose that work, so the row moves with its
 * key rather than being dropped back to the shipped copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('content_blocks')
            ->where('page_key', 'shared')
            ->where('field_key', 'services')
            ->get();

        foreach ($rows as $row) {
            $items = json_decode((string) $row->value, true);

            if (! is_array($items)) {
                continue;
            }

            // The two fields the collection adds. Existing services keep
            // appearing on the home page, which is where they already were.
            $icons = ['dyslexia', 'calm', 'branching'];

            foreach ($items as $i => $item) {
                $items[$i]['icon'] = $item['icon'] ?? ($icons[$i] ?? 'network');
                $items[$i]['on_home'] = $item['on_home'] ?? true;
            }

            DB::table('content_blocks')->updateOrInsert(
                ['page_key' => 'services', 'field_key' => 'services', 'locale' => $row->locale],
                [
                    'value' => json_encode($items, JSON_UNESCAPED_UNICODE),
                    'created_at' => $row->created_at,
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('content_blocks')
            ->where('page_key', 'shared')
            ->where('field_key', 'services')
            ->delete();
    }

    public function down(): void
    {
        DB::table('content_blocks')
            ->where('page_key', 'services')
            ->where('field_key', 'services')
            ->update(['page_key' => 'shared']);
    }
};
