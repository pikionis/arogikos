<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who comes first in the team.
 *
 * The About page used to list whoever was created first, which is an accident
 * of when someone accepted their invitation, not a decision anybody made. The
 * panel now orders them by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('is_active');
        });

        // Seed the order from what the site shows today, not from the name:
        // the grid is ordered by id right now, and a deploy that silently
        // reshuffled the team would be a change nobody asked for.
        foreach (DB::table('authors')->orderBy('id')->pluck('id') as $i => $id) {
            DB::table('authors')->where('id', $id)->update(['position' => $i + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
