<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Parses a British National Grid reference such as "SK364514" (or
 * "SK 364 514", any mix of case/spacing, 2-10 digits as long as the
 * easting/northing halves are equal length) into an OSGB36 easting/northing
 * in metres - the input OsCoordinateTransformer::osgb36ToWgs84() expects.
 *
 * Ported from Chris Veness's geodesy library (OsGridRef.parse(),
 * https://github.com/chrisveness/geodesy, MIT licence), whose own
 * documented worked example this is verified against: 'TG 51409 13177'
 * parses to easting 651409, northing 313177.
 *
 * Deliberately restricted to grid references starting with H, N, S or T -
 * the only four 500km grid squares that actually cover Great Britain
 * (Ireland and the Channel Islands use their own separate grid systems),
 * matching the same restriction in Veness's reference implementation.
 *
 * @since  1.0.0
 */
final class OsGridReference
{
    /**
     * @param   string  $gridRef  e.g. "SK364514" or "SK 364 514".
     *
     * @return  array{0: float, 1: float}|null  [easting, northing] in metres, or null if not parseable.
     *
     * @since   1.0.0
     */
    public static function parse(string $gridRef): ?array
    {
        $gridRef = trim($gridRef);

        if (!preg_match('/^([HNSThnst])([A-Za-z])(.*)$/', $gridRef, $matches)) {
            return null;
        }

        [, $letter1, $letter2, $rest] = $matches;

        $parts = preg_split('/\s+/', trim($rest), -1, \PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1 && ctype_digit($parts[0])) {
            // No internal whitespace - one run of digits, split evenly
            // (e.g. "364514" -> "364" / "514").
            $digits = $parts[0];

            if ($digits === '' || strlen($digits) % 2 !== 0) {
                return null;
            }

            $half = (int) (strlen($digits) / 2);
            $eastingDigits = substr($digits, 0, $half);
            $northingDigits = substr($digits, $half);
        } elseif (
            count($parts) === 2
            && ctype_digit($parts[0])
            && ctype_digit($parts[1])
            && strlen($parts[0]) === strlen($parts[1])
        ) {
            $eastingDigits = $parts[0];
            $northingDigits = $parts[1];
        } else {
            return null;
        }

        // Numeric values of the letter references, mapping A->0, B->1, etc,
        // then shuffled down after 'I' since that letter is skipped in the
        // grid lettering scheme.
        $l1 = ord(strtoupper($letter1)) - ord('A');
        $l2 = ord(strtoupper($letter2)) - ord('A');

        if ($l1 > 7) {
            $l1--;
        }

        if ($l2 > 7) {
            $l2--;
        }

        // Grid letters into 100km-square indexes from the false origin
        // (grid square SV, off the southwest coast of England).
        $e100km = ((($l1 - 2) % 5 + 5) % 5) * 5 + $l2 % 5;
        $n100km = (19 - intdiv($l1, 5) * 5) - intdiv($l2, 5);

        // Standardise to 5-digit (metre precision) easting/northing digits.
        $eastingDigits = str_pad($eastingDigits, 5, '0');
        $northingDigits = str_pad($northingDigits, 5, '0');

        $easting = $e100km * 100000 + (int) $eastingDigits;
        $northing = $n100km * 100000 + (int) $northingDigits;

        if ($easting < 0 || $easting > 700000 || $northing < 0 || $northing > 1300000) {
            return null;
        }

        return [(float) $easting, (float) $northing];
    }
}
