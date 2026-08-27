<?php

namespace Ramblers\Component\RaLibrary\Tests\Unit\Library\Geodesy;

use PHPUnit\Framework\TestCase;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\WkbGeometryReader;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\WktGeometryWriter;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\DouglasPeucker;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\PolygonSimplifier;

/**
 * Regression suite for WKB parsing, WKT writing, and Douglas-Peucker
 * polygon simplification, using a hand-built WKB MULTIPOLYGON as fixture
 * data (a square with a deliberate near-collinear "wobble" point on one
 * edge, to exercise tolerance-based point retention/removal).
 */
final class WkbWktSimplificationTest extends TestCase
{
    private WkbGeometryReader $reader;
    private WktGeometryWriter $writer;
    private PolygonSimplifier $simplifier;

    protected function setUp(): void
    {
        $this->reader = new WkbGeometryReader();
        $this->writer = new WktGeometryWriter();
        $this->simplifier = new PolygonSimplifier(new DouglasPeucker());
    }

    /**
     * Builds a standard little-endian WKB MULTIPOLYGON containing a single
     * polygon: a square with an inserted near-collinear point on the
     * bottom edge.
     *
     * Square: (0,0) -> (10,0) -> (10,10) -> (0,10) -> (0,0)
     * With wobble point at (5, 0.01) inserted on the bottom edge.
     */
    private function buildTestWkb(): string
    {
        $ring = [
            [0.0, 0.0],
            [5.0, 0.01],
            [10.0, 0.0],
            [10.0, 10.0],
            [0.0, 10.0],
            [0.0, 0.0],
        ];

        $wkb = '';
        $wkb .= pack('C', 1);
        $wkb .= pack('V', 6); // MULTIPOLYGON
        $wkb .= pack('V', 1); // 1 polygon

        $wkb .= pack('C', 1);
        $wkb .= pack('V', 3); // POLYGON
        $wkb .= pack('V', 1); // 1 ring

        $wkb .= pack('V', count($ring));

        foreach ($ring as [$x, $y]) {
            $wkb .= pack('e', $x);
            $wkb .= pack('e', $y);
        }

        return $wkb;
    }

    public function testWkbParsingProducesCorrectPointCount(): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());

        $this->assertCount(1, $parsed, 'Expected 1 polygon');
        $this->assertCount(1, $parsed[0], 'Expected 1 ring (no holes)');
        $this->assertCount(6, $parsed[0][0], 'Expected 6 points including closing point');
    }

    public function testWkbParsingPreservesCoordinateValues(): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());
        $ring = $parsed[0][0];

        $this->assertEqualsWithDelta(0.0, $ring[0][0], 0.0001);
        $this->assertEqualsWithDelta(0.0, $ring[0][1], 0.0001);
        $this->assertEqualsWithDelta(5.0, $ring[1][0], 0.0001);
        $this->assertEqualsWithDelta(0.01, $ring[1][1], 0.0001);
    }

    public function testWktRoundTripWithoutSimplification(): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());
        $wkt = $this->writer->writeMultiPolygon($parsed);

        $this->assertSame('MULTIPOLYGON(((0 0,5 0.01,10 0,10 10,0 10,0 0)))', $wkt);
    }

    public function testLooseToleranceDropsWobblePoint(): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());
        $simplified = $this->simplifier->simplifyMultiPolygon($parsed, 0.1);

        $this->assertCount(5, $simplified[0][0], 'Wobble point should be dropped at loose tolerance');
    }

    public function testTightToleranceKeepsWobblePoint(): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());
        $simplified = $this->simplifier->simplifyMultiPolygon($parsed, 0.001);

        $this->assertCount(6, $simplified[0][0], 'Wobble point should be kept at tight tolerance');
    }

    /**
     * @dataProvider toleranceProvider
     */
    public function testSimplifiedRingRemainsClosed(float $tolerance): void
    {
        $parsed = $this->reader->readMultiPolygon($this->buildTestWkb());
        $simplified = $this->simplifier->simplifyMultiPolygon($parsed, $tolerance);

        $ring = $simplified[0][0];
        $first = $ring[0];
        $last = $ring[count($ring) - 1];

        $this->assertSame($first[0], $last[0], 'Ring first/last X mismatch -- ring not closed');
        $this->assertSame($first[1], $last[1], 'Ring first/last Y mismatch -- ring not closed');
    }

    public static function toleranceProvider(): array
    {
        return [
            'loose tolerance' => [0.1],
            'tight tolerance' => [0.001],
        ];
    }
}
