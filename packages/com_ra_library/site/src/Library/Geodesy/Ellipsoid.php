<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Ellipsoid parameters used by the OSGB36 <-> ETRS89 transformation pipeline.
 */
final class Ellipsoid
{
    public function __construct(
        public readonly float $semiMajorAxis,   // a, metres
        public readonly float $semiMinorAxis,   // b, metres
    ) {
    }

    /**
     * Airy 1830 -- used for OSGB36 National Grid (easting/northing <-> OSGB36 lat/lon).
     */
    public static function airy1830(): self
    {
        return new self(6377563.396, 6356256.909);
    }

    /**
     * GRS80 -- used for the ETRS89 pseudo-grid that OSTN15 shifts to/from.
     * ETRS89 is treated as equivalent to WGS84 for all practical purposes
     * within Great Britain and Ireland, per OS's own guidance.
     */
    public static function grs80(): self
    {
        return new self(6378137.000, 6356752.3141);
    }
}
