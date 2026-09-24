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
