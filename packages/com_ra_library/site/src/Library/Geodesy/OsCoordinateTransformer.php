<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Top-level OSGB36 <-> WGS84/ETRS89 coordinate transformer, combining
 * Transverse Mercator projection with the OSTN15 grid shift.
 *
 * ETRS89 is treated as equivalent to WGS84 for all practical purposes
 * within Great Britain, per Ordnance Survey's own guidance.
 *
 * Verified end-to-end (both directions) against Ordnance Survey's
 * OSTN15_OSGM15_Lite worked examples -- matches OS's reference output
 * to sub-millimetre precision. See tests/Unit/Library/Geodesy for the
 * regression suite encoding these worked examples.
 */
final class OsCoordinateTransformer
{
    /**
     * Default location of the OSTN15/OSGM15 Lite grid data file, relative
     * to this class's own file location -- i.e. always
     * ".../Library/Geodesy/data/OSTN15_OSGM15_Lite_DataFile.txt", bundled
     * alongside the code that reads it. Callers using the default
     * constructor never need to know this file exists or where it lives.
     */
    private const DEFAULT_GRID_FILE_PATH = __DIR__ . '/data/OSTN15_OSGM15_Lite_DataFile.txt';

    private TransverseMercator $osgb36Projection;
    private TransverseMercator $etrs89Projection;
    private Ostn15LiteGrid $grid;

    /**
     * @param string|null $ostn15GridFilePath Override the default grid
     *   file location -- normally left null. Exists primarily so tests
     *   can point at fixture files without needing to relocate them into
     *   the real data/ folder.
     */
    public function __construct(?string $ostn15GridFilePath = null)
    {
        $this->osgb36Projection = new TransverseMercator(Ellipsoid::airy1830());
        $this->etrs89Projection = new TransverseMercator(Ellipsoid::grs80());

        $this->grid = new Ostn15LiteGrid();
        $this->grid->loadFromFile($ostn15GridFilePath ?? self::DEFAULT_GRID_FILE_PATH);
    }

    /**
     * Convert an OSGB36 National Grid easting/northing to WGS84 lat/lon.
     *
     * Uses the same iterative shift-resolution approach as Ordnance Survey's
     * own reference implementation: an initial guess at the OSGB36 point
     * itself, then 3 rounds of recomputing the shift at the refined
     * position. OS's worked examples converge by iteration 2, with the
     * 3rd confirming stability -- matched here exactly rather than using
     * an arbitrary iteration count.
     *
     * @return array{0: float, 1: float} [latitude, longitude]
     */
    public function osgb36ToWgs84(float $easting, float $northing): array
    {
        $approxEasting  = $easting;
        $approxNorthing = $northing;

        for ($i = 0; $i < 3; $i++) {
            [$eastShift, $northShift] = $this->grid->shiftAt($approxEasting, $approxNorthing);
            $approxEasting  = $easting - $eastShift;
            $approxNorthing = $northing - $northShift;
        }

        return $this->etrs89Projection->eastingNorthingToLatLon($approxEasting, $approxNorthing);
    }

    /**
     * Convert a WGS84 lat/lon to OSGB36 National Grid easting/northing.
     *
     * This direction is non-iterative: project to the ETRS89 pseudo-grid,
     * look up the shift at that position, and add it directly.
     *
     * @return array{0: float, 1: float} [easting, northing]
     */
    public function wgs84ToOsgb36(float $lat, float $lon): array
    {
        [$etrsEasting, $etrsNorthing] = $this->etrs89Projection->latLonToEastingNorthing($lat, $lon);

        [$eastShift, $northShift] = $this->grid->shiftAt($etrsEasting, $etrsNorthing);

        return [$etrsEasting + $eastShift, $etrsNorthing + $northShift];
    }
}
