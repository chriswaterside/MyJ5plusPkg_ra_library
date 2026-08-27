<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Decodes standard 2D WKB (Well-Known Binary) geometry into plain PHP
 * array structures suitable for simplification.
 *
 * Supports POLYGON and MULTIPOLYGON only (2D, no Z/M) -- sufficient for
 * OS Boundary-Line authority/parish data. Other geometry types will
 * throw.
 *
 * Output structure, always normalised to multipolygon shape regardless
 * of whether the input was POLYGON or MULTIPOLYGON:
 *   array<int, array<int, array<int, array{0: float, 1: float}>>>
 *   [polygon][ring][point] = [x, y]
 */
final class WkbGeometryReader
{
    private const WKB_TYPE_POLYGON = 3;
    private const WKB_TYPE_MULTIPOLYGON = 6;

    /** @var string */
    private string $data;

    /** @var int */
    private int $offset;

    public function readMultiPolygon(string $wkb): array
    {
        $this->data = $wkb;
        $this->offset = 0;

        return $this->readGeometry();
    }

    private function readGeometry(): array
    {
        $littleEndian = $this->readByte() === 1;
        $type = $this->readUInt32($littleEndian);

        return match ($type) {
            self::WKB_TYPE_POLYGON => [$this->readPolygonBody($littleEndian)],
            self::WKB_TYPE_MULTIPOLYGON => $this->readMultiPolygonBody($littleEndian),
            default => throw new \InvalidArgumentException("Unsupported WKB geometry type: {$type}"),
        };
    }

    /**
     * @return array<int, array<int, array{0: float, 1: float}>> Rings of a single polygon.
     */
    private function readPolygonBody(bool $littleEndian): array
    {
        $ringCount = $this->readUInt32($littleEndian);
        $rings = [];

        for ($i = 0; $i < $ringCount; $i++) {
            $rings[] = $this->readRing($littleEndian);
        }

        return $rings;
    }

    /**
     * @return array<int, array<int, array<int, array{0: float, 1: float}>>>
     */
    private function readMultiPolygonBody(bool $littleEndian): array
    {
        $polygonCount = $this->readUInt32($littleEndian);
        $polygons = [];

        for ($i = 0; $i < $polygonCount; $i++) {
            // Each polygon in a multipolygon has its own byte-order + type header.
            $polyLittleEndian = $this->readByte() === 1;
            $polyType = $this->readUInt32($polyLittleEndian);

            if ($polyType !== self::WKB_TYPE_POLYGON) {
                throw new \InvalidArgumentException("Expected POLYGON inside MULTIPOLYGON, got type {$polyType}");
            }

            $polygons[] = $this->readPolygonBody($polyLittleEndian);
        }

        return $polygons;
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function readRing(bool $littleEndian): array
    {
        $pointCount = $this->readUInt32($littleEndian);
        $points = [];

        for ($i = 0; $i < $pointCount; $i++) {
            $x = $this->readDouble($littleEndian);
            $y = $this->readDouble($littleEndian);
            $points[] = [$x, $y];
        }

        return $points;
    }

    private function readByte(): int
    {
        $value = ord($this->data[$this->offset]);
        $this->offset += 1;

        return $value;
    }

    private function readUInt32(bool $littleEndian): int
    {
        $bytes = substr($this->data, $this->offset, 4);
        $this->offset += 4;

        return $littleEndian
            ? unpack('V', $bytes)[1]
            : unpack('N', $bytes)[1];
    }

    private function readDouble(bool $littleEndian): float
    {
        $bytes = substr($this->data, $this->offset, 8);
        $this->offset += 8;

        // PHP's unpack 'e' = little-endian double, 'E' = big-endian double
        // (requires PHP 7.0+, available -- confirmed PHP 8.3 in this environment).
        return $littleEndian
            ? unpack('e', $bytes)[1]
            : unpack('E', $bytes)[1];
    }
}
