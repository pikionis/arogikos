<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an author pick one of the ready-made cover scenes.
 *
 * A plain string rather than an enum or a foreign key: the scenes live in
 * config/cms-covers.php and in Blade, not in the database, so there is nothing
 * to point at. Retiring a scene must not take an article's choice with it —
 * same rule the categories follow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('cover_preset', 40)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('cover_preset');
        });
    }
};
