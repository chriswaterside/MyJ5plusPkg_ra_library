<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * British National Grid Transverse Mercator projection parameters.
 * These constants apply to both the OSGB36 (Airy 1830) and ETRS89
 * pseudo-grid (GRS80) projections -- only the ellipsoid changes between
 * the two, not the projection origin or scale factor.
 */
final class NationalGridProjection
{
    /** Scale factor on the central meridian. */
    public const F0 = 0.9996012717;

    /** True origin latitude, degrees. */
    public const LAT0_DEG = 49.0;

    /** True origin longitude, degrees. */
    public const LON0_DEG = -2.0;

    /** True origin northing, metres. */
    public const N0 = -100000.0;

    /** True origin easting, metres. */
    public const E0 = 400000.0;
}
