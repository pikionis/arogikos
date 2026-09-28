<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an event be written now and announced later.
 *
 * Deliberately NOT starts_at. That is when the event happens, and an
 * announcement exists to be read before then — hiding it until the day would
 * mean nobody could come. This is the separate question of when the
 * announcement itself goes live, and it matches what articles already do.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->index()->after('is_published');
        });

        // Everything already public stays public: without a date the new scope
        // would hide every existing event the moment this deploys.
        DB::table('events')
            ->where('is_published', true)
            ->whereNull('published_at')
            ->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
