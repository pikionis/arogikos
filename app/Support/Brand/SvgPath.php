<?php

declare(strict_types=1);

namespace App\Support\Brand;

/**
 * Turns the path data used by the cover scenes into plain lists of points.
 *
 * Not a general SVG implementation — it handles exactly what those twenty
 * files contain, which is a small set because they were generated rather
 * than drawn by hand: M, L, C, Q, T, A and Z, absolute and relative. Anything
 * else would be a silent wrong shape, so it throws instead.
 *
 * Build-time only.
 */
final class SvgPath
{
    /** How finely a curve is broken into straight pieces. */
    private const CURVE_STEPS = 48;

    /**
     * @return array<int, array<int, array{0: float, 1: float}>> one list of points per subpath
     */
    public static function flatten(string $d): array
    {
        $tokens = preg_split('/(?=[A-Za-z])|[\s,]+/', trim($d), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $subpaths = [];
        $points = [];
        $x = $y = 0.0;
        $startX = $startY = 0.0;
        // Where the previous quadratic's control point was, for T.
        $lastControl = null;
        $command = '';
        $queue = [];

        $flush = function () use (&$subpaths, &$points) {
            if (count($points) > 1) {
                $subpaths[] = $points;
            }
            $points = [];
        };

        foreach ($tokens as $token) {
            if (preg_match('/^[A-Za-z]$/', $token)) {
                $command = $token;
                $queue = [];

                if ($command === 'Z' || $command === 'z') {
                    if ($points !== []) {
                        $points[] = [$startX, $startY];
                        $flush();
                    }
                    $x = $startX;
                    $y = $startY;
                }

                continue;
            }

            $queue[] = (float) $token;
            $relative = $command === strtolower($command);
            $upper = strtoupper($command);

            $take = match ($upper) {
                'M', 'L', 'T' => 2,
                'Q' => 4,
                'C' => 6,
                'A' => 7,
                default => throw new \RuntimeException("Unsupported path command: $command"),
            };

            if (count($queue) < $take) {
                continue;
            }

            $a = $queue;
            $queue = [];

            switch ($upper) {
                case 'M':
                    $flush();
                    $x = $relative ? $x + $a[0] : $a[0];
                    $y = $relative ? $y + $a[1] : $a[1];
                    $startX = $x;
                    $startY = $y;
                    $points = [[$x, $y]];
                    $lastControl = null;
                    // A second pair after M continues as a line, per the spec.
                    $command = $relative ? 'l' : 'L';
                    break;

                case 'L':
                    $x = $relative ? $x + $a[0] : $a[0];
                    $y = $relative ? $y + $a[1] : $a[1];
                    $points[] = [$x, $y];
                    $lastControl = null;
                    break;

                case 'Q':
                    $cx = $relative ? $x + $a[0] : $a[0];
                    $cy = $relative ? $y + $a[1] : $a[1];
                    $ex = $relative ? $x + $a[2] : $a[2];
                    $ey = $relative ? $y + $a[3] : $a[3];
                    self::quadratic($points, $x, $y, $cx, $cy, $ex, $ey);
                    $lastControl = [$cx, $cy];
                    $x = $ex;
                    $y = $ey;
                    break;

                case 'T':
                    // Mirror the previous control point through the current one.
                    $cx = $lastControl ? 2 * $x - $lastControl[0] : $x;
                    $cy = $lastControl ? 2 * $y - $lastControl[1] : $y;
                    $ex = $relative ? $x + $a[0] : $a[0];
                    $ey = $relative ? $y + $a[1] : $a[1];
                    self::quadratic($points, $x, $y, $cx, $cy, $ex, $ey);
                    $lastControl = [$cx, $cy];
                    $x = $ex;
                    $y = $ey;
                    break;

                case 'C':
                    $c1x = $relative ? $x + $a[0] : $a[0];
                    $c1y = $relative ? $y + $a[1] : $a[1];
                    $c2x = $relative ? $x + $a[2] : $a[2];
                    $c2y = $relative ? $y + $a[3] : $a[3];
                    $ex = $relative ? $x + $a[4] : $a[4];
                    $ey = $relative ? $y + $a[5] : $a[5];
                    self::cubic($points, $x, $y, $c1x, $c1y, $c2x, $c2y, $ex, $ey);
                    $lastControl = null;
                    $x = $ex;
                    $y = $ey;
                    break;

                case 'A':
                    $ex = $relative ? $x + $a[5] : $a[5];
                    $ey = $relative ? $y + $a[6] : $a[6];
                    self::arc($points, $x, $y, $a[0], $a[1], $a[2], (bool) $a[3], (bool) $a[4], $ex, $ey);
                    $lastControl = null;
                    $x = $ex;
                    $y = $ey;
                    break;
            }
        }

        $flush();

        return $subpaths;
    }

    /** @param array<int, array{0: float, 1: float}> $points */
    private static function quadratic(array &$points, float $x0, float $y0, float $cx, float $cy, float $x1, float $y1): void
    {
        for ($i = 1; $i <= self::CURVE_STEPS; $i++) {
            $t = $i / self::CURVE_STEPS;
            $u = 1 - $t;

            $points[] = [
                $u * $u * $x0 + 2 * $u * $t * $cx + $t * $t * $x1,
                $u * $u * $y0 + 2 * $u * $t * $cy + $t * $t * $y1,
            ];
        }
    }

    /** @param array<int, array{0: float, 1: float}> $points */
    private static function cubic(array &$points, float $x0, float $y0, float $c1x, float $c1y, float $c2x, float $c2y, float $x1, float $y1): void
    {
        for ($i = 1; $i <= self::CURVE_STEPS; $i++) {
            $t = $i / self::CURVE_STEPS;
            $u = 1 - $t;

            $points[] = [
                $u ** 3 * $x0 + 3 * $u ** 2 * $t * $c1x + 3 * $u * $t ** 2 * $c2x + $t ** 3 * $x1,
                $u ** 3 * $y0 + 3 * $u ** 2 * $t * $c1y + 3 * $u * $t ** 2 * $c2y + $t ** 3 * $y1,
            ];
        }
    }

    /**
     * Endpoint arc → centre parameterisation, as the SVG specification sets it
     * out in appendix F.6. The covers use A for every rounded band and every
     * dome, so this is the one piece that has to be exactly right.
     *
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    private static function arc(array &$points, float $x0, float $y0, float $rx, float $ry, float $rotation, bool $largeArc, bool $sweep, float $x1, float $y1): void
    {
        $rx = abs($rx);
        $ry = abs($ry);

        if ($rx === 0.0 || $ry === 0.0 || ($x0 === $x1 && $y0 === $y1)) {
            $points[] = [$x1, $y1];

            return;
        }

        $phi = deg2rad($rotation);
        $cosPhi = cos($phi);
        $sinPhi = sin($phi);

        $dx = ($x0 - $x1) / 2;
        $dy = ($y0 - $y1) / 2;
        $x1p = $cosPhi * $dx + $sinPhi * $dy;
        $y1p = -$sinPhi * $dx + $cosPhi * $dy;

        // Grow radii that are too small to span the two endpoints.
        $lambda = ($x1p ** 2) / ($rx ** 2) + ($y1p ** 2) / ($ry ** 2);
        if ($lambda > 1) {
            $rx *= sqrt($lambda);
            $ry *= sqrt($lambda);
        }

        $num = $rx ** 2 * $ry ** 2 - $rx ** 2 * $y1p ** 2 - $ry ** 2 * $x1p ** 2;
        $den = $rx ** 2 * $y1p ** 2 + $ry ** 2 * $x1p ** 2;
        $factor = $den == 0.0 ? 0.0 : sqrt(max(0.0, $num / $den));

        if ($largeArc === $sweep) {
            $factor = -$factor;
        }

        $cxp = $factor * $rx * $y1p / $ry;
        $cyp = -$factor * $ry * $x1p / $rx;

        $cx = $cosPhi * $cxp - $sinPhi * $cyp + ($x0 + $x1) / 2;
        $cy = $sinPhi * $cxp + $cosPhi * $cyp + ($y0 + $y1) / 2;

        $angle = fn (float $ux, float $uy, float $vx, float $vy): float => (
            ($ux * $vy - $uy * $vx < 0 ? -1 : 1)
            * acos(max(-1.0, min(1.0, ($ux * $vx + $uy * $vy) / (sqrt($ux ** 2 + $uy ** 2) * sqrt($vx ** 2 + $vy ** 2)))))
        );

        $start = $angle(1, 0, ($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry);
        $sweepAngle = $angle(($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry, (-$x1p - $cxp) / $rx, (-$y1p - $cyp) / $ry);

        if (! $sweep && $sweepAngle > 0) {
            $sweepAngle -= 2 * M_PI;
        } elseif ($sweep && $sweepAngle < 0) {
            $sweepAngle += 2 * M_PI;
        }

        $steps = max(8, (int) ceil(abs($sweepAngle) / (M_PI / 90)));

        for ($i = 1; $i <= $steps; $i++) {
            $theta = $start + $sweepAngle * $i / $steps;
            $px = $rx * cos($theta);
            $py = $ry * sin($theta);

            $points[] = [
                $cosPhi * $px - $sinPhi * $py + $cx,
                $sinPhi * $px + $cosPhi * $py + $cy,
            ];
        }
    }
}
