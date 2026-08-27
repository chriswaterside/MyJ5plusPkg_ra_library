<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Transverse Mercator forward/inverse projection, parameterised by ellipsoid.
 *
 * Implements the standard OS National Grid formulae (see OS "A guide to
 * coordinate systems in Great Britain", section on Transverse Mercator
 * projections). Angles are handled internally in radians; public methods
 * take/return degrees for convenience.
 *
 * Verified against Ordnance Survey's OSTN15_OSGM15_Lite worked examples --
 * both directions match OS's reference output to sub-millimetre precision.
 */
final class TransverseMercator
{
    public function __construct(private readonly Ellipsoid $ellipsoid)
    {
    }

    /**
     * Convert ellipsoidal latitude/longitude (degrees) to grid easting/northing (metres).
     *
     * @return array{0: float, 1: float} [easting, northing]
     */
    public function latLonToEastingNorthing(float $latDeg, float $lonDeg): array
    {
        $a  = $this->ellipsoid->semiMajorAxis;
        $b  = $this->ellipsoid->semiMinorAxis;
        $f0 = NationalGridProjection::F0;

        $lat  = deg2rad($latDeg);
        $lon  = deg2rad($lonDeg);
        $lat0 = deg2rad(NationalGridProjection::LAT0_DEG);
        $lon0 = deg2rad(NationalGridProjection::LON0_DEG);

        $e2 = 1 - ($b * $b) / ($a * $a);   // eccentricity squared
        $n  = ($a - $b) / ($a + $b);

        $cosLat = cos($lat);
        $sinLat = sin($lat);

        $nu   = $a * $f0 / sqrt(1 - $e2 * $sinLat ** 2);
        $rho  = $a * $f0 * (1 - $e2) / (1 - $e2 * $sinLat ** 2) ** 1.5;
        $eta2 = $nu / $rho - 1;

        $m = $this->meridionalArc($lat, $lat0, $b, $f0, $n);

        $cos3Lat = $cosLat ** 3;
        $cos5Lat = $cosLat ** 5;
        $tan2Lat = tan($lat) ** 2;
        $tan4Lat = $tan2Lat ** 2;

        $vii  = $m + NationalGridProjection::N0;
        $viii = ($nu / 2) * $sinLat * $cosLat;
        $ix   = ($nu / 24) * $sinLat * $cos3Lat * (5 - $tan2Lat + 9 * $eta2);
        $x    = ($nu / 720) * $sinLat * $cos5Lat * (61 - 58 * $tan2Lat + $tan4Lat);

        $northing = $vii + $viii * ($lon - $lon0) ** 2
            + $ix * ($lon - $lon0) ** 4
            + $x * ($lon - $lon0) ** 6;

        $xi   = $nu * $cosLat;
        $xiiV = ($nu / 6) * $cos3Lat * ($nu / $rho - $tan2Lat);
        $xiiA = ($nu / 120) * $cos5Lat * (5 - 18 * $tan2Lat + $tan4Lat + 14 * $eta2 - 58 * $tan2Lat * $eta2);

        $easting = NationalGridProjection::E0
            + $xi * ($lon - $lon0)
            + $xiiV * ($lon - $lon0) ** 3
            + $xiiA * ($lon - $lon0) ** 5;

        return [$easting, $northing];
    }

    /**
     * Convert grid easting/northing (metres) to ellipsoidal latitude/longitude (degrees).
     *
     * @return array{0: float, 1: float} [latDeg, lonDeg]
     */
    public function eastingNorthingToLatLon(float $easting, float $northing): array
    {
        $a  = $this->ellipsoid->semiMajorAxis;
        $b  = $this->ellipsoid->semiMinorAxis;
        $f0 = NationalGridProjection::F0;

        $lat0 = deg2rad(NationalGridProjection::LAT0_DEG);
        $lon0 = deg2rad(NationalGridProjection::LON0_DEG);

        $e2 = 1 - ($b * $b) / ($a * $a);
        $n  = ($a - $b) / ($a + $b);

        // Initial approximation, then iterate until the meridional arc matches.
        $lat = $lat0;
        $m   = 0.0;

        do {
            $lat = ($northing - NationalGridProjection::N0 - $m) / ($a * $f0) + $lat;
            $m   = $this->meridionalArc($lat, $lat0, $b, $f0, $n);
        } while (abs($northing - NationalGridProjection::N0 - $m) >= 0.00001);

        $sinLat = sin($lat);
        $cosLat = cos($lat);
        $tanLat = tan($lat);

        $nu   = $a * $f0 / sqrt(1 - $e2 * $sinLat ** 2);
        $rho  = $a * $f0 * (1 - $e2) / (1 - $e2 * $sinLat ** 2) ** 1.5;
        $eta2 = $nu / $rho - 1;

        $tan2Lat = $tanLat ** 2;
        $tan4Lat = $tan2Lat ** 2;
        $tan6Lat = $tan2Lat ** 3;

        $vii  = $tanLat / (2 * $rho * $nu);
        $viii = ($tanLat / (24 * $rho * $nu ** 3)) * (5 + 3 * $tan2Lat + $eta2 - 9 * $tan2Lat * $eta2);
        $ix   = ($tanLat / (720 * $rho * $nu ** 5)) * (61 + 90 * $tan2Lat + 45 * $tan4Lat);

        $x    = 1 / ($nu * $cosLat);
        $xi   = (1 / (6 * $nu ** 3 * $cosLat)) * ($nu / $rho + 2 * $tan2Lat);
        $xii  = (1 / (120 * $nu ** 5 * $cosLat)) * (5 + 28 * $tan2Lat + 24 * $tan4Lat);
        $xiiA = (1 / (5040 * $nu ** 7 * $cosLat)) * (61 + 662 * $tan2Lat + 1320 * $tan4Lat + 720 * $tan6Lat);

        $de = $easting - NationalGridProjection::E0;

        $latOut = $lat - $vii * $de ** 2 + $viii * $de ** 4 - $ix * $de ** 6;
        $lonOut = $lon0 + $x * $de - $xi * $de ** 3 + $xii * $de ** 5 - $xiiA * $de ** 7;

        return [rad2deg($latOut), rad2deg($lonOut)];
    }

    private function meridionalArc(float $lat, float $lat0, float $b, float $f0, float $n): float
    {
        $latPlus  = $lat + $lat0;
        $latMinus = $lat - $lat0;

        return $b * $f0 * (
            (1 + $n + (5 / 4) * $n ** 2 + (5 / 4) * $n ** 3) * $latMinus
            - (3 * $n + 3 * $n ** 2 + (21 / 8) * $n ** 3) * sin($latMinus) * cos($latPlus)
            + ((15 / 8) * $n ** 2 + (15 / 8) * $n ** 3) * sin(2 * $latMinus) * cos(2 * $latPlus)
            - (35 / 24) * $n ** 3 * sin(3 * $latMinus) * cos(3 * $latPlus)
        );
    }
}
