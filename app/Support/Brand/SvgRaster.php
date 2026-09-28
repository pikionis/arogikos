<?php

declare(strict_types=1);

namespace App\Support\Brand;

use RuntimeException;
use SimpleXMLElement;

/**
 * Draws a cover scene onto a Canvas, so it can be saved as a PNG.
 *
 * Facebook, WhatsApp and LinkedIn will not read an SVG, so the scenes the
 * site shows have to exist as raster images too or every article shares the
 * same generic card. Rather than redraw twenty pictures by hand, this reads
 * the files the site already uses: one source, and a PNG that cannot drift
 * from what a visitor sees.
 *
 * Deliberately narrow. It covers what those files contain — rect, circle,
 * ellipse, path, groups, fill, stroke and opacity — and throws on anything
 * else, because a silently skipped element is a wrong picture that nobody
 * would notice until it was already on somebody's timeline.
 *
 * Every shape becomes one polygon, fill and stroke alike. GD blends a
 * polygon drawn once against the colours beneath it correctly; a stroke
 * stamped from overlapping discs would darken itself wherever the discs met,
 * which is visible the moment a shape is semi-transparent.
 *
 * Build-time only.
 */
final class SvgRaster
{
    /** @var array<string, string> */
    private array $palette;

    /** @param array<string, string> $palette CSS custom property name → hex */
    public function __construct(array $palette)
    {
        $this->palette = $palette;
    }

    public function render(string $svg, Canvas $canvas): void
    {
        // The files are Blade partials; the comment at the top is not XML.
        $svg = preg_replace('/\{\{--.*?--\}\}/s', '', $svg) ?? $svg;

        $xml = @simplexml_load_string(trim($svg));

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Could not parse the scene.');
        }

        $this->walk($xml, $canvas, 1.0);
    }

    private function walk(SimpleXMLElement $node, Canvas $canvas, float $opacity): void
    {
        foreach ($node->children() as $child) {
            $name = $child->getName();
            $own = $opacity * (float) ($this->attr($child, 'opacity') ?? 1.0);

            if ($name === 'g') {
                $this->walk($child, $canvas, $own);

                continue;
            }

            $this->draw($name, $child, $canvas, $own);
        }
    }

    private function draw(string $name, SimpleXMLElement $el, Canvas $canvas, float $opacity): void
    {
        $subpaths = match ($name) {
            'rect' => [$this->rect($el)],
            'circle' => [$this->ellipse(
                (float) $this->attr($el, 'cx'),
                (float) $this->attr($el, 'cy'),
                (float) $this->attr($el, 'r'),
                (float) $this->attr($el, 'r'),
            )],
            'ellipse' => [$this->ellipse(
                (float) $this->attr($el, 'cx'),
                (float) $this->attr($el, 'cy'),
                (float) $this->attr($el, 'rx'),
                (float) $this->attr($el, 'ry'),
            )],
            'path' => SvgPath::flatten((string) $this->attr($el, 'd')),
            default => throw new RuntimeException("Unsupported element: <$name>"),
        };

        $fill = $this->colour($this->attr($el, 'fill'));
        $stroke = $this->colour($this->attr($el, 'stroke'));
        $width = (float) ($this->attr($el, 'stroke-width') ?? 1);

        // A path with a stroke and no fill attribute is a line in these
        // files. SVG would default it to black, which is a colour this brand
        // does not use anywhere.
        if ($fill !== null && ! ($name === 'path' && $this->attr($el, 'fill') === null && $stroke !== null)) {
            foreach ($subpaths as $points) {
                $canvas->fillPolygon($points, $fill, $opacity);
            }
        }

        if ($stroke !== null && $width > 0) {
            foreach ($subpaths as $points) {
                $canvas->fillPolygon(Outline::around($points, $width), $stroke, $opacity);
            }
        }
    }

    /** @return array<int, array{0: float, 1: float}> */
    private function rect(SimpleXMLElement $el): array
    {
        $x = (float) $this->attr($el, 'x');
        $y = (float) $this->attr($el, 'y');
        $w = (float) $this->attr($el, 'width');
        $h = (float) $this->attr($el, 'height');
        $r = min((float) ($this->attr($el, 'rx') ?? 0), $w / 2, $h / 2);

        if ($r <= 0) {
            return [[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h]];
        }

        $points = [];
        $corners = [
            [$x + $w - $r, $y + $r, -M_PI / 2],
            [$x + $w - $r, $y + $h - $r, 0.0],
            [$x + $r, $y + $h - $r, M_PI / 2],
            [$x + $r, $y + $r, M_PI],
        ];

        foreach ($corners as [$cx, $cy, $from]) {
            for ($i = 0; $i <= 16; $i++) {
                $a = $from + (M_PI / 2) * $i / 16;
                $points[] = [$cx + cos($a) * $r, $cy + sin($a) * $r];
            }
        }

        return $points;
    }

    /** @return array<int, array{0: float, 1: float}> */
    private function ellipse(float $cx, float $cy, float $rx, float $ry): array
    {
        $steps = max(48, (int) ceil(max($rx, $ry)));
        $points = [];

        for ($i = 0; $i < $steps; $i++) {
            $a = 2 * M_PI * $i / $steps;
            $points[] = [$cx + cos($a) * $rx, $cy + sin($a) * $ry];
        }

        return $points;
    }

    /** Resolves `var(--green)` and `#RRGGBB`; `none` and absent both mean no paint. */
    private function colour(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === 'none') {
            return null;
        }

        if (preg_match('/var\(\s*(--[a-z-]+)\s*\)/', $value, $m)) {
            return $this->palette[$m[1]]
                ?? throw new RuntimeException("Unknown colour token: {$m[1]}");
        }

        return $value;
    }

    private function attr(SimpleXMLElement $el, string $name): ?string
    {
        $value = $el->attributes()->{$name};

        return $value === null ? null : (string) $value;
    }
}
