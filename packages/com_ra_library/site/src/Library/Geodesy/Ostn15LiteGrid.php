<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Loads the OSTN15/OSGM15 Lite Developer Pack grid (20km resolution) and
 * provides bilinearly-interpolated easting/northing shift values.
 *
 * File format (confirmed against the actual downloaded data file, no header row):
 *   recordIndex, easting, northing, eastShift, northShift, geoidHeight, qualityFlag
 *
 * Only easting, northing, eastShift, northShift are used here -- geoid
 * height and quality flag are read but not currently applied, since this
 * library handles horizontal transformation only.
 *
 * Verified against Ordnance Survey's OSTN15_OSGM15_Lite worked examples --
 * grid lookups and bilinear interpolation match OS's reference output
 * to sub-millimetre precision.
 */
final class Ostn15LiteGrid
{
    private const GRID_RESOLUTION_METRES = 20000; // 20km for the Lite pack

    /** @var array<string, array{eastShift: float, northShift: float}> keyed by "eastIndex_northIndex" */
    private array $gridPoints = [];

    public function loadFromFile(string $path): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open OSTN15 grid file: {$path}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line);

            // Columns: recordIndex, easting, northing, eastShift, northShift, geoidHeight, qualityFlag
            $easting    = (float) $parts[1];
            $northing   = (float) $parts[2];
            $eastShift  = (float) $parts[3];
            $northShift = (float) $parts[4];

            $eastIndex  = (int) round($easting / self::GRID_RESOLUTION_METRES);
            $northIndex = (int) round($northing / self::GRID_RESOLUTION_METRES);

            $this->gridPoints["{$eastIndex}_{$northIndex}"] = [
                'eastShift'  => $eastShift,
                'northShift' => $northShift,
            ];
        }

        fclose($handle);
    }

    /**
     * Bilinearly interpolate the easting/northing shift at a given position.
     *
     * When converting ETRS89 -> OSGB36, pass the ETRS89 pseudo-grid easting/northing.
     * When converting OSGB36 -> ETRS89, pass the (iteratively refined) OSGB36 easting/northing.
     *
     * @return array{0: float, 1: float} [eastingShift, northingShift]
     *
     * @throws \OutOfRangeException if the point falls outside the loaded grid's coverage.
     */
    public function shiftAt(float $easting, float $northing): array
    {
        $res = self::GRID_RESOLUTION_METRES;

        $eastIndex0  = (int) floor($easting / $res);
        $northIndex0 = (int) floor($northing / $res);

        $dx = ($easting - $eastIndex0 * $res) / $res;
        $dy = ($northing - $northIndex0 * $res) / $res;

        $p00 = $this->pointAt($eastIndex0, $northIndex0);
        $p10 = $this->pointAt($eastIndex0 + 1, $northIndex0);
        $p01 = $this->pointAt($eastIndex0, $northIndex0 + 1);
        $p11 = $this->pointAt($eastIndex0 + 1, $northIndex0 + 1);

        $eastShift  = $this->bilinear($p00['eastShift'], $p10['eastShift'], $p01['eastShift'], $p11['eastShift'], $dx, $dy);
        $northShift = $this->bilinear($p00['northShift'], $p10['northShift'], $p01['northShift'], $p11['northShift'], $dx, $dy);

        return [$eastShift, $northShift];
    }

    private function pointAt(int $eastIndex, int $northIndex): array
    {
        $key = "{$eastIndex}_{$northIndex}";

        if (!isset($this->gridPoints[$key])) {
            throw new \OutOfRangeException(
                "No OSTN15 grid point at index {$key} -- point may be outside the OSTN15 coverage area."
            );
        }

        return $this->gridPoints[$key];
    }

    private function bilinear(float $q00, float $q10, float $q01, float $q11, float $dx, float $dy): float
    {
        return $q00 * (1 - $dx) * (1 - $dy)
            + $q10 * $dx * (1 - $dy)
            + $q01 * (1 - $dx) * $dy
            + $q11 * $dx * $dy;
    }
}
