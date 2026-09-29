<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Shared handling for the admin's single-file image fields.
 *
 * Every collection in this app is ->singleFile(), so adding replaces. The
 * matching "remove_<name>" checkbox is rendered by
 * resources/views/components/admin/image-field.blade.php.
 */
class MediaUpload
{
    /**
     * A collection that holds many files: add whatever was chosen, remove
     * whatever was ticked.
     *
     * Used for the photographs of an event, which arrive in batches days
     * after it happened rather than all at once.
     *
     * @param  string  $field  the file input's name, e.g. "photos[]"
     */
    public static function syncMany(HasMedia $model, Request $request, string $collection, string $field): void
    {
        // Removals first, so replacing the whole set in one save is possible.
        foreach ((array) $request->input("remove_{$collection}", []) as $id) {
            $media = $model->media()->whereKey((int) $id)->first();
            $media?->delete();
        }

        foreach ((array) $request->file($field, []) as $file) {
            if ($file === null) {
                continue;
            }

            $model->addMedia($file)
                ->sanitizingFileName(fn (string $name) => Str::slug(
                    pathinfo($name, PATHINFO_FILENAME), '-', 'el'
                ).'.'.pathinfo($name, PATHINFO_EXTENSION))
                ->toMediaCollection($collection);
        }
    }

    public static function sync(HasMedia $model, Request $request, string $collection): void
    {
        if ($request->boolean("remove_{$collection}")) {
            $model->clearMediaCollection($collection);
        }

        if (! $request->hasFile($collection)) {
            return;
        }

        $model->addMediaFromRequest($collection)
            // Greek filenames survive a Linux host badly; slug them, keeping
            // the extension medialibrary needs for the mime type.
            ->sanitizingFileName(fn (string $name) => Str::slug(
                pathinfo($name, PATHINFO_FILENAME), '-', 'el'
            ).'.'.pathinfo($name, PATHINFO_EXTENSION))
            ->toMediaCollection($collection);
    }
}
