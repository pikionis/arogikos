<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blog categories, moved out of config/arogi.php so staff can manage them.
     *
     * `key` is what lives in articles.category — a plain indexed string, so no
     * article data changes and nothing needs backfilling.
     *
     * `archived_at` rather than a hard delete: removing a category that
     * articles already use must not change those articles. An archived row
     * disappears from the picker and the public filter chips, but still
     * supplies the label, colour and motif for the articles that carry it.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label');
            // Constrained to the palette and the SVG motifs the design ships;
            // see App\Models\Cms\Category::COLORS / MOTIFS.
            $table->string('color', 20)->default('green');
            $table->string('motif', 20)->default('book');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
