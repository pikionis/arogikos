<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Author;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Storing which part of the uploaded photograph each frame keeps.
 *
 * There are two, because the site draws two quite different shapes: a small
 * circle beside a byline and a tall arch on the team page. One rectangle
 * cannot be right for both.
 *
 * The browser sends each one in the picture's own pixels. They are checked
 * against the real file here rather than trusted: a rectangle reaching past
 * the edge would fail inside the image conversion, which runs during the
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

        [$width, $height] = self::dimensions($media);

        if ($width < 1 || $height < 1) {
            return;
        }

        $changed = false;
        $values = [];

        foreach (array_keys(Author::FRAMES) as $frame) {
            $crop = self::fit($width, $height, [
                'x' => (int) $request->input($frame.'_x'),
                'y' => (int) $request->input($frame.'_y'),
                'w' => (int) $request->input($frame.'_w'),
                'h' => (int) $request->input($frame.'_h'),
            ]);

            foreach (['x', 'y', 'w', 'h'] as $part) {
                $column = $frame.'_'.$part;
                $was = $author->{$column} === null ? null : (int) $author->{$column};
                $now = $crop === null ? null : $crop[$part];

                $values[$column] = $now;
                $changed = $changed || $was !== $now;
            }
        }

        // Regenerating the conversions is the slow part of this whole screen,
        // so it only happens when a frame actually moved.
        if (! $changed) {
            return;
        }

        $author->forceFill($values)->save();

        self::regenerate($media);
    }

    /**
     * The submitted rectangle, pulled back inside the picture.
     *
     * @param  array{x: int, y: int, w: int, h: int}  $crop
     * @return array{x: int, y: int, w: int, h: int}|null null when there is nothing to crop
     */
    private static function fit(int $width, int $height, array $crop): ?array
    {
        if ($crop['w'] < 1 || $crop['h'] < 1) {
            return null;
        }

        $w = min($crop['w'], $width);
        $h = min($crop['h'], $height);
        $x = max(0, min($crop['x'], $width - $w));
        $y = max(0, min($crop['y'], $height - $h));

        // The whole picture is not a crop; leaving it unset keeps the
        // conversion one step shorter.
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
        $values = [];

        foreach (array_keys(Author::FRAMES) as $frame) {
            foreach (['x', 'y', 'w', 'h'] as $part) {
                $values[$frame.'_'.$part] = null;
            }
        }

        if (array_filter($values, fn ($v, $k) => $author->{$k} !== null, ARRAY_FILTER_USE_BOTH) === []) {
            return;
        }

        $author->forceFill($values)->save();
    }

    private static function regenerate(Media $media): void
    {
        // The model is what carries the framing, and the conversions are
        // registered from it — so it has to be the freshly saved one.
        $media->load('model');

        app(FileManipulator::class)->createDerivedFiles($media);
    }
}
