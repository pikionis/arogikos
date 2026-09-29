<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Author;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Storing which square of the uploaded photograph becomes the photograph.
 *
 * The browser sends a rectangle in the picture's own pixels. It is checked
 * against the real file here rather than trusted: a crop that reaches past
 * the edge would fail inside the image conversion, which happens during the
 * request and would take the whole save down with it.
 */
class PhotoCrop
{
    public static function sync(Author $author, Request $request): void
    {
        $media = $author->getFirstMedia('photo');

        if (! $media) {
            self::clear($author);

            return;
        }

        // A new file in the same save means the numbers on the form describe
        // the previous picture. Start that one whole.
        if ($request->hasFile('photo')) {
            self::clear($author);
            self::regenerate($media);

            return;
        }

        $crop = self::fit($media, [
            'x' => (int) $request->input('crop_x'),
            'y' => (int) $request->input('crop_y'),
            'w' => (int) $request->input('crop_w'),
            'h' => (int) $request->input('crop_h'),
        ]);

        if ($crop === null) {
            self::clear($author);
            self::regenerate($media);

            return;
        }

        // Nothing moved, so nothing needs rebuilding — regenerating the
        // conversions is the slow part of this whole screen.
        if ((int) $author->crop_x === $crop['x'] && (int) $author->crop_y === $crop['y']
            && (int) $author->crop_w === $crop['w'] && (int) $author->crop_h === $crop['h']) {
            return;
        }

        $author->forceFill([
            'crop_x' => $crop['x'],
            'crop_y' => $crop['y'],
            'crop_w' => $crop['w'],
            'crop_h' => $crop['h'],
        ])->save();

        self::regenerate($media);
    }

    /**
     * The submitted square, pulled back inside the picture.
     *
     * @param  array{x: int, y: int, w: int, h: int}  $crop
     * @return array{x: int, y: int, w: int, h: int}|null null when there is nothing to crop
     */
    private static function fit(Media $media, array $crop): ?array
    {
        if ($crop['w'] < 1 || $crop['h'] < 1) {
            return null;
        }

        [$width, $height] = self::dimensions($media);

        if ($width < 1 || $height < 1) {
            return null;
        }

        $w = min($crop['w'], $width);
        $h = min($crop['h'], $height);
        $x = max(0, min($crop['x'], $width - $w));
        $y = max(0, min($crop['y'], $height - $h));

        // The whole picture is not a crop; leaving it unset keeps the
        // conversions one step shorter.
        if ($x === 0 && $y === 0 && $w === $width && $h === $height) {
            return null;
        }

        return ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
    }

    /** @return array{0: int, 1: int} */
    private static function dimensions(Media $media): array
    {
        $size = @getimagesize($media->getPath());

        return [(int) ($size[0] ?? 0), (int) ($size[1] ?? 0)];
    }

    private static function clear(Author $author): void
    {
        if (! $author->hasCrop()) {
            return;
        }

        $author->forceFill([
            'crop_x' => null, 'crop_y' => null, 'crop_w' => null, 'crop_h' => null,
        ])->save();
    }

    private static function regenerate(Media $media): void
    {
        // The model is what carries the crop, and the conversions are
        // registered from it — so it has to be the freshly saved one.
        $media->load('model');

        app(FileManipulator::class)->createDerivedFiles($media);
    }
}
