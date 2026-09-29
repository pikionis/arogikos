<?php

declare(strict_types=1);

use App\Models\Cms\Author;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\Conversions\FileManipulator;

/**
 * Make the two new sizes for the photographs already uploaded.
 *
 * The conversions were renamed, and a URL for one that was never generated
 * points at a file that is not there — every author photo on the site would
 * be a broken image until somebody happened to save that profile again.
 *
 * Nothing is cropped here: no one has framed anything yet, so each shape gets
 * the whole picture, exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        $manipulator = app(FileManipulator::class);

        Author::query()->with('media')->cursor()->each(function (Author $author) use ($manipulator) {
            $media = $author->getFirstMedia('photo');

            if (! $media) {
                return;
            }

            try {
                $manipulator->createDerivedFiles($media);
            } catch (Throwable $e) {
                // One unreadable upload must not stop a deploy; that profile
                // rebuilds the next time it is saved.
                report($e);
            }
        });
    }

    public function down(): void
    {
        // Rebuilt images; there is nothing to undo.
    }
};
