<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An article is written by however many people wrote it.
 *
 * It used to carry one `author_id`, so the second and third person on a piece
 * simply did not appear. The byline is a list now.
 *
 * The order is the order of the Συντάκτες screen — the one the centre already
 * sets by dragging — so one place decides who comes first rather than two that
 * can disagree.
 *
 * `author_id` goes rather than staying behind holding a stale copy of the
 * answer. SQLite will not drop a column named in a foreign key, so the table
 * is rebuilt: the only faithful way to do it, and the deploy takes a backup of
 * the database file first.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Read the bylines out before the column carrying them disappears.
        $bylines = DB::table('articles')
            ->whereNotNull('author_id')
            ->get(['id', 'author_id']);

        Schema::withoutForeignKeyConstraints(function () {
            Schema::create('articles_rebuilt', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug');
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->string('category')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->boolean('is_published')->default(false);
                $table->string('seo_title')->nullable();
                $table->string('seo_description')->nullable();
                $table->timestamps();
                $table->string('cover_preset')->nullable();
            });

            $columns = [
                'id', 'title', 'slug', 'excerpt', 'body', 'category', 'published_at',
                'is_published', 'seo_title', 'seo_description', 'created_at',
                'updated_at', 'cover_preset',
            ];

            $list = implode(', ', array_map(fn (string $c) => '"'.$c.'"', $columns));

            DB::statement("INSERT INTO articles_rebuilt ({$list}) SELECT {$list} FROM articles");

            Schema::drop('articles');
            Schema::rename('articles_rebuilt', 'articles');

            // Named after the table they were created on, so they are made
            // again now that it has its real name back.
            Schema::table('articles', function (Blueprint $table) {
                $table->unique('slug');
                $table->index('category');
                $table->index('published_at');
                $table->index('is_published');
            });
        });

        Schema::create('article_author', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            // Deleting a profile leaves its articles standing, exactly as
            // before — they lose that one name from the byline.
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'author_id']);
        });

        foreach ($bylines as $row) {
            DB::table('article_author')->insert([
                'article_id' => $row->id,
                'author_id' => $row->author_id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
        });

        // The column holds one, so only the first name survives going back.
        foreach (DB::table('article_author')->orderBy('author_id')->get() as $row) {
            DB::table('articles')
                ->where('id', $row->article_id)
                ->whereNull('author_id')
                ->update(['author_id' => $row->author_id]);
        }

        Schema::dropIfExists('article_author');
    }
};
