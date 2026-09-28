<?php

declare(strict_types=1);

namespace App\Support\Brand;

/**
 * Turns a line into the shape that line covers.
 *
 * A stroke has to become a polygon so it can be drawn in one go. Stamping it
 * from overlapping discs is simpler and looks identical while a shape is
 * opaque, but the moment it is not, every overlap blends twice and the line
 * comes out mottled — and the cover scenes lean on soft, half-transparent
 * arcs throughout.
 *
 * The outline runs up one side of the line and back down the other, with a
 * half-circle turned at each end so the caps are round. Every stroke in the
 * scenes is either round-capped or short enough that the difference is
 * invisible at 1200 pixels wide.
 */
final class Outline
{
    /**
     * @param  array<int, array{0: float, 1: float}>  $points
     * @return array<int, array{0: float, 1: float}>
     */
    public static function around(array $points, float $width): array
    {
        $points = self::dedupe($points);
        $half = $width / 2;

        if (count($points) < 2) {
            return $points === [] ? [] : self::cap($points[0], 0.0, $half);
        }

        $left = [];
        $right = [];

        foreach ($points as $i => [$x, $y]) {
            // The direction of the line here: the segment ahead, the one
            // behind, or the average of the two at a join.
            [$dx, $dy] = self::direction($points, $i);

            $left[] = [$x - $dy * $half, $y + $dx * $half];
            $right[] = [$x + $dy * $half, $y - $dx * $half];
        }

        $first = $points[0];
        $last = $points[count($points) - 1];
        [$sdx, $sdy] = self::direction($points, 0);
        [$edx, $edy] = self::direction($points, count($points) - 1);

        return array_merge(
            self::cap($first, atan2(-$sdy, -$sdx), $half),
            $left,
            self::cap($last, atan2($edy, $edx), $half),
            array_reverse($right),
        );
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $points
     * @return array{0: float, 1: float}
     */
    private static function direction(array $points, int $i): array
    {
        $last = count($points) - 1;

        $before = $i > 0 ? self::unit($points[$i - 1], $points[$i]) : null;
        $after = $i < $last ? self::unit($points[$i], $points[$i + 1]) : null;

        if ($before === null) {
            return $after ?? [1.0, 0.0];
        }

        if ($after === null) {
            return $before;
        }

        $dx = $before[0] + $after[0];
        $dy = $before[1] + $after[1];
        $len = sqrt($dx * $dx + $dy * $dy);

        // A hairpin turn cancels out; keep going the way we came.
        return $len < 1e-9 ? $before : [$dx / $len, $dy / $len];
    }

    /**
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     * @return array{0: float, 1: float}|null
     */
    private static function unit(array $a, array $b): ?array
    {
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        $len = sqrt($dx * $dx + $dy * $dy);

        return $len < 1e-9 ? null : [$dx / $len, $dy / $len];
    }

    /**
     * Half a circle around an end, turned to face away from the line.
     *
     * @param  array{0: float, 1: float}  $at
     * @return array<int, array{0: float, 1: float}>
     */
    private static function cap(array $at, float $facing, float $radius): array
    {
        $points = [];

        for ($i = 0; $i <= 12; $i++) {
            $a = $facing - M_PI / 2 + M_PI * $i / 12;
            $points[] = [$at[0] + cos($a) * $radius, $at[1] + sin($a) * $radius];
        }

        return $points;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $points
     * @return array<int, array{0: float, 1: float}>
     */
    private static function dedupe(array $points): array
    {
        $out = [];

        foreach ($points as $point) {
            $previous = end($out);

            if ($previous === false || abs($previous[0] - $point[0]) > 1e-9 || abs($previous[1] - $point[1]) > 1e-9) {
                $out[] = $point;
            }
        }

        return array_values($out);
    }
}
