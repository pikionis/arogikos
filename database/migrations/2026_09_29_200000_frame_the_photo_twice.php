<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One framing was not enough.
 *
 * The site shows an author's photograph in two quite different shapes: a
 * small circle beside a byline, and a tall arch on the team page and next to
 * an article. A square chosen for the circle loses its top and bottom in the
 * arch, and one chosen for the arch loses its sides in the circle — so the
 * preview was honest about one of them and wrong about the other.
 *
 * Each shape gets its own rectangle now. The upload is still never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The single crop was square, which is what the circle wants.
        Schema::table('authors', function (Blueprint $table) {
            $table->renameColumn('crop_x', 'circle_x');
            $table->renameColumn('crop_y', 'circle_y');
            $table->renameColumn('crop_w', 'circle_w');
            $table->renameColumn('crop_h', 'circle_h');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->unsignedInteger('arch_x')->nullable()->after('circle_h');
            $table->unsignedInteger('arch_y')->nullable()->after('arch_x');
            $table->unsignedInteger('arch_w')->nullable()->after('arch_y');
            $table->unsignedInteger('arch_h')->nullable()->after('arch_w');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn(['arch_x', 'arch_y', 'arch_w', 'arch_h']);
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->renameColumn('circle_x', 'crop_x');
            $table->renameColumn('circle_y', 'crop_y');
            $table->renameColumn('circle_w', 'crop_w');
            $table->renameColumn('circle_h', 'crop_h');
        });
    }
};
