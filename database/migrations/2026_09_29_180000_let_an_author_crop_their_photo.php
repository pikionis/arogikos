<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which part of the photograph becomes the photograph.
 *
 * People send what they have: a group shot, a holiday picture, themselves at
 * the edge of the frame. The site kept the middle of it, which is often
 * somebody else's shoulder.
 *
 * The chosen square is stored rather than applied to the file, so the upload
 * stays whole and the crop can be moved again later without asking for the
 * photo a second time. Null in all four means the picture is used as it came.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            // Pixels in the uploaded image, not percentages: the browser and
            // the server have to agree on exactly the same square.
            $table->unsignedInteger('crop_x')->nullable()->after('bio');
            $table->unsignedInteger('crop_y')->nullable()->after('crop_x');
            $table->unsignedInteger('crop_w')->nullable()->after('crop_y');
            $table->unsignedInteger('crop_h')->nullable()->after('crop_w');
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn(['crop_x', 'crop_y', 'crop_w', 'crop_h']);
        });
    }
};
