<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Picks the picture social platforms show when somebody posts a link.
 *
 * There are three sources, in order of how specific they are:
 *
 *   1. whatever the page names for itself — an article uses its cover;
 *   2. the image uploaded in Κείμενα σελίδων → Κοινά στοιχεία;
 *   3. the generated brand card that ships with the repo.
 *
 * The third exists because a link with no og:image is posted as a bare box of
 * text. Every page therefore has a picture from the first deploy, and the
 * client can replace it with a photo of the centre whenever they have one,
 * without anybody touching code.
 */
final class ShareImage
{
    /** Drawn by `php artisan brand:images`. */
    public const DEFAULT_PATH = 'images/share-default.png';

    public const DEFAULT_WIDTH = 1200;

    public const DEFAULT_HEIGHT = 630;

    /**
     * @param  string|null  $explicit  the page's own choice, if it has one
     * @return array{url: string, width: int|null, height: int|null}
     */
    public static function resolve(?string $explicit = null): array
    {
        if (filled($explicit = trim((string) $explicit))) {
            return self::unsized($explicit);
        }

        if (filled($uploaded = content_image('shared.share_image', 'web'))) {
            return self::unsized($uploaded);
        }

        // The only one whose dimensions are known without opening a file.
        return [
            'url' => asset(self::DEFAULT_PATH),
            'width' => self::DEFAULT_WIDTH,
            'height' => self::DEFAULT_HEIGHT,
        ];
    }

    /**
     * @return array{url: string, width: null, height: null}
     */
    private static function unsized(string $url): array
    {
        return ['url' => $url, 'width' => null, 'height' => null];
    }
}
