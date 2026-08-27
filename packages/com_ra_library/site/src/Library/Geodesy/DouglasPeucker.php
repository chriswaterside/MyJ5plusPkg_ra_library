<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Pure-PHP implementation of the Ramer-Douglas-Peucker line simplification
 * algorithm. Operates on flat point arrays; callers are responsible for
 * decomposing polygons into their constituent rings before calling this,
 * and for re-assembling the result afterward (see PolygonSimplifier).
 */
final class DouglasPeucker
{
    /**
     * Simplify a single ring/line of points.
     *
     * @param array<int, array{0: float, 1: float}> $points Ordered [x, y] pairs.
     * @param float $tolerance Maximum perpendicular distance, in the same
     *                          units as the input coordinates (metres if
     *                          working in OSGB36, degrees if WGS84).
     *
     * @return array<int, array{0: float, 1: float}>
     */
    public function simplify(array $points, float $tolerance): array
    {
        $count = count($points);

        if ($count < 3) {
            return $points; // Nothing to simplify below 3 points.
        }

        $keep = array_fill(0, $count, false);
        $keep[0] = true;
        $keep[$count - 1] = true;

        $this->simplifySection($points, 0, $count - 1, $tolerance, $keep);

        $result = [];

        foreach ($keep as $index => $shouldKeep) {
            if ($shouldKeep) {
                $result[] = $points[$index];
            }
        }

        return $result;
    }

    /**
     * Recursively mark which points in [$start, $end] survive simplification.
     *
     * @param array<int, bool> $keep Passed by reference, mutated in place.
     */
    private function simplifySection(array $points, int $start, int $end, float $tolerance, array &$keep): void
    {
        if ($end <= $start + 1) {
            return; // No interior points to consider.
        }

        $maxDistance = 0.0;
        $maxIndex = $start;

        for ($i = $start + 1; $i < $end; $i++) {
            $distance = $this->perpendicularDistance($points[$i], $points[$start], $points[$end]);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $i;
            }
        }

        if ($maxDistance > $tolerance) {
            $keep[$maxIndex] = true;
            $this->simplifySection($points, $start, $maxIndex, $tolerance, $keep);
            $this->simplifySection($points, $maxIndex, $end, $tolerance, $keep);
        }
        // else: every point between start and end is within tolerance of the
        // straight line -- they're all discarded, start and end alone remain.
    }

    private function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        [$x, $y] = $point;
        [$x1, $y1] = $lineStart;
        [$x2, $y2] = $lineEnd;

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;

        if ($dx === 0.0 && $dy === 0.0) {
            // Degenerate line segment (start == end) -- fall back to plain distance.
            return sqrt(($x - $x1) ** 2 + ($y - $y1) ** 2);
        }

        // Perpendicular distance from point to the infinite line through
        // lineStart/lineEnd, via the standard point-to-line formula.
        $numerator = abs($dy * $x - $dx * $y + $x2 * $y1 - $y2 * $x1);
        $denominator = sqrt($dx ** 2 + $dy ** 2);

        return $numerator / $denominator;
    }
}
