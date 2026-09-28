<?php

declare(strict_types=1);

namespace App\Support\Brand;

use GdImage;
use RuntimeException;

/**
 * A small drawing surface for the brand's generated images.
 *
 * Everything is drawn at a multiple of the final size and scaled down at the
 * end: GD will not antialias a thick arc or a large glyph on its own, and the
 * logo is mostly curves. Supersampling costs a few milliseconds in a build
 * step and is the difference between a crisp mark and a jagged one.
 */
final class Canvas
{
    private GdImage $im;

    /** @var array<string, int> */
    private array $allocated = [];

    /* How a scene's own coordinates land on this canvas; identity until
       viewBox() says otherwise, so everything drawn by hand is unaffected. */
    private float $vbScale = 1.0;

    private float $vbShiftY = 0.0;

    public function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly int $scale = 3,
    ) {
        $im = imagecreatetruecolor($width * $scale, $height * $scale);

        if (! $im instanceof GdImage) {
            throw new RuntimeException('GD could not allocate the canvas.');
        }

        imagealphablending($im, true);
        imagesavealpha($im, true);

        $this->im = $im;
    }

    /* --------------------------------------------------------------- paint */

    /**
     * Fits a drawing's own coordinate space onto this canvas.
     *
     * The cover scenes are 1600x1000; a social card is 1.91:1. The width
     * fills, and the height is trimmed evenly top and bottom — which is what
     * Facebook would crop to anyway, so better to choose the crop than to
     * let it happen.
     */
    public function viewBox(float $sourceWidth, float $sourceHeight, float $cropHeight): self
    {
        $this->vbScale = $this->width / $sourceWidth;
        $this->vbShiftY = -(($sourceHeight - $cropHeight) / 2);

        return $this;
    }

    public function fill(string $hex): self
    {
        imagefilledrectangle($this->im, 0, 0, imagesx($this->im), imagesy($this->im), $this->color($hex));

        return $this;
    }

    public function disc(float $cx, float $cy, float $r, string $hex): self
    {
        imagefilledellipse($this->im, $this->px($cx), $this->px($cy), $this->px($r * 2), $this->px($r * 2), $this->color($hex));

        return $this;
    }

    /** A circle outline of a given stroke width, as the logo's head is drawn. */
    public function ring(float $cx, float $cy, float $r, float $stroke, string $hex): self
    {
        return $this->arc($cx, $cy, $r, 0, 360, $stroke, $hex);
    }

    /**
     * An arc of a given stroke width, with round caps.
     *
     * Stamped from overlapping discs rather than drawn with imagearc() and a
     * thickness: GD widens an arc by drawing it repeatedly at an offset, which
     * on a curve leaves gaps that read as spikes — the logo's ring came out
     * looking like a sun. Stamping costs a few hundred discs and is exact.
     *
     * Angles follow GD's convention: 0° is east, increasing clockwise.
     */
    public function arc(float $cx, float $cy, float $r, float $from, float $to, float $stroke, string $hex): self
    {
        // One stamp every half pixel of arc length, measured at draw scale.
        $length = deg2rad(abs($to - $from)) * $r * $this->scale;
        $steps = max(12, (int) ceil($length * 2));

        for ($i = 0; $i <= $steps; $i++) {
            $angle = deg2rad($from + ($to - $from) * $i / $steps);

            $this->disc($cx + cos($angle) * $r, $cy + sin($angle) * $r, $stroke / 2, $hex);
        }

        return $this;
    }

    public function roundedBar(float $x, float $y, float $w, float $h, string $hex): self
    {
        $r = $h / 2;

        imagefilledrectangle($this->im, $this->px($x + $r), $this->px($y), $this->px($x + $w - $r), $this->px($y + $h), $this->color($hex));
        $this->disc($x + $r, $y + $r, $r, $hex);
        $this->disc($x + $w - $r, $y + $r, $r, $hex);

        return $this;
    }

    /**
     * One polygon, drawn once.
     *
     * Everything the SVG rasteriser draws comes through here — fills and
     * strokes alike — because GD blends a single polygon against what is
     * beneath it correctly, while a shape built from overlapping pieces
     * blends itself twice wherever they meet.
     *
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    public function fillPolygon(array $points, string $hex, float $opacity = 1.0): self
    {
        if (count($points) < 3) {
            return $this;
        }

        $flat = [];
        foreach ($points as [$x, $y]) {
            $flat[] = $this->px($x * $this->vbScale);
            $flat[] = $this->px(($y + $this->vbShiftY) * $this->vbScale);
        }

        imagefilledpolygon($this->im, $flat, $this->color($hex, $opacity));

        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h, string $hex): self
    {
        imagefilledrectangle($this->im, $this->px($x), $this->px($y), $this->px($x + $w), $this->px($y + $h), $this->color($hex));

        return $this;
    }

    /** Draws text from its left edge and baseline, and returns its width. */
    public function text(string $font, float $size, float $x, float $baseline, string $hex, string $body): float
    {
        imagettftext($this->im, $size * $this->scale, 0, $this->px($x), $this->px($baseline), $this->color($hex), $font, $body);

        return $this->measure($font, $size, $body);
    }

    public function measure(string $font, float $size, string $body): float
    {
        $box = imagettfbbox($size * $this->scale, 0, $font, $body);

        return ($box[2] - $box[0]) / $this->scale;
    }

    /* -------------------------------------------------------------- output */

    public function save(string $path): void
    {
        $out = imagescale($this->im, $this->width, $this->height, IMG_BICUBIC);

        if (! $out instanceof GdImage) {
            throw new RuntimeException('GD could not scale the canvas.');
        }

        imagealphablending($out, false);
        imagesavealpha($out, true);

        if (! imagepng($out, $path, 9)) {
            throw new RuntimeException("Could not write $path");
        }
    }

    /** The raw PNG bytes at a given square size — used to build the .ico. */
    public function pngAt(int $size): string
    {
        $out = imagescale($this->im, $size, $size, IMG_BICUBIC);

        if (! $out instanceof GdImage) {
            throw new RuntimeException('GD could not scale the canvas.');
        }

        imagealphablending($out, false);
        imagesavealpha($out, true);

        ob_start();
        imagepng($out, null, 9);

        return (string) ob_get_clean();
    }

    /* --------------------------------------------------------------- utils */

    private function px(float $v): int
    {
        return (int) round($v * $this->scale);
    }

    private function color(string $hex, float $opacity = 1.0): int
    {
        $opacity = max(0.0, min(1.0, $opacity));
        $key = $hex.'|'.round($opacity, 3);

        return $this->allocated[$key] ??= (function () use ($hex, $opacity): int {
            [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%2x%2x%2x');

            // GD counts alpha the other way round: 0 opaque, 127 invisible.
            $alpha = (int) round((1 - $opacity) * 127);

            return $alpha === 0
                ? imagecolorallocate($this->im, $r, $g, $b)
                : imagecolorallocatealpha($this->im, $r, $g, $b, $alpha);
        })();
    }
}
