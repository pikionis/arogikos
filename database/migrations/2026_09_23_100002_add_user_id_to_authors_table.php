<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links an author display profile to a panel account.
     *
     * This is what lets the article form offer "Μέλη του panel" as authors.
     * The link is optional in both directions: the six seeded authors have no
     * account, and an account need not be an author.
     */
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
