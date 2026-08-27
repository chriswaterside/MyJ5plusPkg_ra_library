<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Applies OsCoordinateTransformer across every point in a normalised
 * multipolygon structure (as produced by WkbGeometryReader), preserving
 * the polygon/ring nesting so the result can be passed straight to
 * PolygonSimplifier or WktGeometryWriter.
 *
 * This is the piece that connects GeoPackage reading (OSGB36 geometry)
 * to storage (WGS84 geometry), per the decision to reproject once at
 * import time rather than per-request.
 */
final class MultiPolygonReprojector
{
    public function __construct(private readonly OsCoordinateTransformer $transformer)
    {
    }

    /**
     * Reproject every point in a multipolygon from OSGB36 easting/northing
     * to WGS84 lat/lon.
     *
     * Note on point order: WKB/WKT store points as (x, y), which for
     * OSGB36 is (easting, northing). The output uses the same (x, y)
     * convention but now holding (longitude, latitude) -- matching the
     * GeoJSON/WKT convention of (x, y) = (lon, lat), NOT (lat, lon).
     * This is a common source of bugs; ensure downstream code (GeoJSON
     * serialisation in particular) expects this ordering.
     *
     * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $multiPolygon
     *   Structure: [polygon][ring][point] = [easting, northing]
     *
     * @return array<int, array<int, array<int, array{0: float, 1: float}>>>
     *   Structure: [polygon][ring][point] = [longitude, latitude]
     */
    public function osgb36ToWgs84(array $multiPolygon): array
    {
        return $this->mapPoints(
            $multiPolygon,
            function (array $point): array {
                [$easting, $northing] = $point;
                [$lat, $lon] = $this->transformer->osgb36ToWgs84($easting, $northing);

                return [$lon, $lat]; // x,y = lon,lat convention
            }
        );
    }

    /**
     * Reproject every point in a multipolygon from WGS84 lat/lon back to
     * OSGB36 easting/northing. Provided for completeness/symmetry; your
     * pipeline as designed shouldn't need this direction in the import
     * path itself.
     *
     * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $multiPolygon
     *   Structure: [polygon][ring][point] = [longitude, latitude]
     *
     * @return array<int, array<int, array<int, array{0: float, 1: float}>>>
     *   Structure: [polygon][ring][point] = [easting, northing]
     */
    public function wgs84ToOsgb36(array $multiPolygon): array
    {
        return $this->mapPoints(
            $multiPolygon,
            function (array $point): array {
                [$lon, $lat] = $point;

                return $this->transformer->wgs84ToOsgb36($lat, $lon);
            }
        );
    }

    /**
     * @param callable(array{0: float, 1: float}): array{0: float, 1: float} $pointMapper
     */
    private function mapPoints(array $multiPolygon, callable $pointMapper): array
    {
        $result = [];

        foreach ($multiPolygon as $polygon) {
            $mappedPolygon = [];

            foreach ($polygon as $ring) {
                $mappedRing = [];

                foreach ($ring as $point) {
                    $mappedRing[] = $pointMapper($point);
                }

                $mappedPolygon[] = $mappedRing;
            }

            $result[] = $mappedPolygon;
        }

        return $result;
    }
}
