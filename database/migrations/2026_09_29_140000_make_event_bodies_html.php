<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An event gets a page, so its description becomes prose.
 *
 * The panel has offered a rich-text editor for this field all along, but the
 * public card printed it with {{ }} — which would have shown the tags to
 * visitors the first time somebody used bold. The page renders it as HTML
 * now, so whatever plain text is already in there has to become HTML too,
 * or it loses its line breaks on the way.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('events')->whereNotNull('body')->get(['id', 'body']) as $row) {
            $body = trim((string) $row->body);

            // Already marked up by the editor: leave it exactly as it is.
            if ($body === '' || $body !== strip_tags($body)) {
                continue;
            }

            $paragraphs = preg_split('/\R{2,}/u', $body) ?: [$body];

            $html = implode('', array_map(
                fn (string $p) => '<p>'.nl2br(e(trim($p)), false).'</p>',
                array_filter(array_map('trim', $paragraphs), fn (string $p) => $p !== '')
            ));

            DB::table('events')->where('id', $row->id)->update(['body' => $html]);
        }
    }

    public function down(): void
    {
        // Turning HTML back into plain text would lose more than it restored.
    }
};
