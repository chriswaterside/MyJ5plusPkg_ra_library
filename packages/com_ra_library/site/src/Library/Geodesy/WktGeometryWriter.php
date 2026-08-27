<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Writes plain PHP array geometry structures out as WKT (Well-Known Text),
 * suitable for MariaDB's ST_GeomFromText().
 *
 * Expects the same normalised multipolygon structure produced by
 * WkbGeometryReader / consumed by PolygonSimplifier:
 *   array<int, array<int, array<int, array{0: float, 1: float}>>>
 *   [polygon][ring][point] = [x, y]
 */
final class WktGeometryWriter
{
    /**
     * Format precision for coordinate output. WGS84 degree values need
     * enough decimal places to preserve sub-metre precision -- 8 decimal
     * places is roughly 1.1mm at the equator, comfortably more than needed.
     */
    private const COORDINATE_PRECISION = 8;

    public function writeMultiPolygon(array $multiPolygon): string
    {
        $polygonStrings = [];

        foreach ($multiPolygon as $polygon) {
            $polygonStrings[] = $this->writePolygonBody($polygon);
        }

        return 'MULTIPOLYGON(' . implode(',', $polygonStrings) . ')';
    }

    /**
     * Writes a single polygon as just its ring-body text, e.g. "((...),(...))",
     * without the "POLYGON" keyword -- used both standalone (writePolygon)
     * and nested inside writeMultiPolygon.
     */
    private function writePolygonBody(array $polygon): string
    {
        $ringStrings = [];

        foreach ($polygon as $ring) {
            $ringStrings[] = '(' . $this->writeRing($ring) . ')';
        }

        return '(' . implode(',', $ringStrings) . ')';
    }

    /**
     * Write a single polygon (not wrapped in a multipolygon) as WKT.
     * Provided for completeness -- your pipeline is expected to always
     * normalise to MULTIPOLYGON per the earlier -nlt PROMOTE_TO_MULTI
     * decision, but this is here in case a bare POLYGON is ever needed.
     */
    public function writePolygon(array $polygon): string
    {
        return 'POLYGON' . $this->writePolygonBody($polygon);
    }

    private function writeRing(array $ring): string
    {
        $pointStrings = [];

        foreach ($ring as [$x, $y]) {
            $pointStrings[] = $this->formatCoordinate($x) . ' ' . $this->formatCoordinate($y);
        }

        return implode(',', $pointStrings);
    }

    private function formatCoordinate(float $value): string
    {
        return rtrim(rtrim(sprintf('%.' . self::COORDINATE_PRECISION . 'f', $value), '0'), '.');
    }
}
