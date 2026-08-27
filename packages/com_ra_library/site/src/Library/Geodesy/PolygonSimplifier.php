<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Simplifies a normalised multipolygon structure (as produced by
 * WkbGeometryReader) ring by ring, preserving the polygon/ring nesting
 * so the result can be passed straight to WktGeometryWriter.
 *
 * Note: simplifying each ring independently means a ring shared between
 * two adjacent parishes/authorities can diverge slightly after
 * simplification -- this is an inherent Douglas-Peucker limitation, not
 * specific to this implementation. See decision log re: simplified
 * geometry being used for rendering only, never for containment queries.
 */
final class PolygonSimplifier
{
    public function __construct(private readonly DouglasPeucker $dp)
    {
    }

    /**
     * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $multiPolygon
     *   Structure: [polygon][ring][point] = [x, y]
     * @param float $tolerance Same units as the input coordinates.
     *
     * @return array<int, array<int, array<int, array{0: float, 1: float}>>>
     */
    public function simplifyMultiPolygon(array $multiPolygon, float $tolerance): array
    {
        $result = [];

        foreach ($multiPolygon as $polygon) {
            $simplifiedPolygon = [];

            foreach ($polygon as $ring) {
                $simplifiedRing = $this->dp->simplify($ring, $tolerance);
                $simplifiedPolygon[] = $this->ensureRingClosed($simplifiedRing);
            }

            $result[] = $simplifiedPolygon;
        }

        return $result;
    }

    /**
     * Defensive check: a polygon ring's first and last point must be
     * identical. DouglasPeucker::simplify() always keeps index 0 and the
     * last index, so this should already hold -- but confirms it rather
     * than assuming, since a malformed source ring (not properly closed)
     * would otherwise silently produce an invalid simplified ring too.
     *
     * @param array<int, array{0: float, 1: float}> $ring
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function ensureRingClosed(array $ring): array
    {
        if (count($ring) < 3) {
            return $ring; // Degenerate ring -- not enough points to close meaningfully.
        }

        $first = $ring[0];
        $last = $ring[count($ring) - 1];

        if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
            $ring[] = $first;
        }

        return $ring;
    }
}
