<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Parses the GeoPackage Binary geometry header (OGC GeoPackage spec,
 * clause 2.1.3) and returns the raw WKB payload beneath it.
 *
 * GeoPackage stores each geometry BLOB as:
 *   - 2 bytes: magic number, ASCII "GP" (0x47 0x50)
 *   - 1 byte:  version
 *   - 1 byte:  flags (encodes byte order, envelope size, empty flag)
 *   - 4 bytes: SRID (byte order per the flags byte)
 *   - N bytes: envelope (0, 32, 48, or 64 bytes depending on flags -- often omitted/zero)
 *   - remainder: standard WKB geometry
 */
final class GeoPackageBinaryReader
{
    /**
     * @return array{srid: int, wkb: string} The SRID and the raw WKB payload.
     */
    public function stripHeader(string $blob): array
    {
        if (strlen($blob) < 8) {
            throw new \InvalidArgumentException('Blob too short to be a GeoPackage geometry.');
        }

        $magic = substr($blob, 0, 2);

        if ($magic !== "GP") {
            throw new \InvalidArgumentException('Not a GeoPackage geometry BLOB (bad magic number).');
        }

        $flags = ord($blob[3]);

        // Bit 0 of flags: 0 = big-endian, 1 = little-endian (applies to SRID and envelope).
        $littleEndian = ($flags & 0x01) === 0x01;

        $sridBytes = substr($blob, 4, 4);
        $srid = $littleEndian
            ? unpack('V', $sridBytes)[1]
            : unpack('N', $sridBytes)[1];

        // Bits 1-3 of flags encode envelope contents/size.
        $envelopeIndicator = ($flags >> 1) & 0x07;

        $envelopeLength = match ($envelopeIndicator) {
            0 => 0,   // no envelope
            1 => 32,  // XY
            2, 3 => 48, // XYZ or XYM
            4 => 64,  // XYZM
            default => throw new \InvalidArgumentException("Unrecognised GeoPackage envelope indicator: {$envelopeIndicator}"),
        };

        $wkbOffset = 8 + $envelopeLength;

        return [
            'srid' => $srid,
            'wkb'  => substr($blob, $wkbOffset),
        ];
    }
}
