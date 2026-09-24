<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable copy for the fixed set of public pages.
     *
     * Every field is declared in config/cms-content.php together with the
     * Greek text that currently lives in the Blade templates. A row here is an
     * OVERRIDE: with an empty table the site renders exactly as before, and
     * "restore the default" is simply deleting the row.
     *
     * `locale` is here from day one even though the site is Greek-only. Adding
     * it to the unique index later would force a full SQLite table rebuild;
     * adding it now costs nothing.
     */
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 60)->index();
            $table->string('field_key', 120);
            $table->string('locale', 5)->default('el');
            // Scalar for text/textarea/html, JSON for lines/repeater.
            $table->longText('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['page_key', 'field_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
