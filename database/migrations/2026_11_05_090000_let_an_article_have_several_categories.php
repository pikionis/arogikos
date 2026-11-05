<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An article belongs to as many categories as it is about.
 *
 * One column meant one answer, and a piece on exam stress for teenagers is
 * about adolescence and about study at the same time. A reader looking under
 * either of them should find it.
 *
 * The pivot stores the category's `key`, not its id, because that is what the
 * rest of this system already addresses categories by — the routes, the
 * picker, the JSON endpoint — and because an archived category has to keep
 * supplying its label and colour to the articles that carry it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('category_key', 60);
            // The order they were chosen in: the first one colours the card
            // and supplies the drawing an article without a cover falls back
            // to, so which one leads is a decision, not an accident.
            $table->unsignedInteger('position')->default(0);
            $table->primary(['article_id', 'category_key']);
            $table->index('category_key');
        });

        foreach (DB::table('articles')->whereNotNull('category')->get(['id', 'category']) as $row) {
            if (trim((string) $row->category) === '') {
                continue;
            }

            DB::table('article_category')->insert([
                'article_id' => $row->id,
                'category_key' => $row->category,
                'position' => 1,
            ]);
        }

        // SQLite will not drop an indexed column, so the index goes first.
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['category']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('category')->nullable()->index();
        });

        // The column holds one, so only the first survives going back.
        foreach (DB::table('article_category')->orderBy('position')->get() as $row) {
            DB::table('articles')
                ->where('id', $row->article_id)
                ->whereNull('category')
                ->update(['category' => $row->category_key]);
        }

        Schema::dropIfExists('article_category');
    }
};
