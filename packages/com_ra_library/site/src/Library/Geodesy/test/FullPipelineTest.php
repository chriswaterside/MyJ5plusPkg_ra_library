<?php

namespace Ramblers\Component\RaLibrary\Tests\Unit\Library\Geodesy;

use PHPUnit\Framework\TestCase;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\GeoPackageTableReader;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\WkbGeometryReader;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\DouglasPeucker;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\PolygonSimplifier;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\OsCoordinateTransformer;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\MultiPolygonReprojector;

/**
 * End-to-end regression test for the assembled import pipeline:
 * GeoPackage -> WKB parsing -> OSTN15 reprojection -> RDP simplification.
 *
 * Uses a fixture parish near OS's point001 worked example location, with
 * a deliberate near-collinear "wobble" point to confirm simplification
 * still behaves correctly on geometry that has been through the full
 * read/reproject pipeline first.
 */
final class FullPipelineTest extends TestCase
{
    private string $gpkgPath;
    private OsCoordinateTransformer $transformer;

    protected function setUp(): void
    {
        $this->gpkgPath = sys_get_temp_dir() . '/ra_pathnetwork_pipeline_test_' . uniqid() . '.gpkg';
        $this->buildFixtureGeoPackage($this->gpkgPath);

        $gridPath = __DIR__ . '/../../../fixtures/OSTN15_OSGM15_Lite_DataFile.txt';
        $this->transformer = new OsCoordinateTransformer($gridPath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->gpkgPath)) {
            unlink($this->gpkgPath);
        }
    }

    public function testFullPipelineProducesPlausibleUkCoordinates(): void
    {
        $reprojector = new MultiPolygonReprojector($this->transformer);
        $wkbReader = new WkbGeometryReader();

        $tableReader = new GeoPackageTableReader($this->gpkgPath);
        $rows = iterator_to_array($tableReader->readTable('parishes', 'geom'));
        $tableReader->close();

        $this->assertCount(1, $rows);

        $osgb36Geometry = $wkbReader->readMultiPolygon($rows[0]['wkb']);
        $wgs84Geometry = $reprojector->osgb36ToWgs84($osgb36Geometry);

        foreach ($wgs84Geometry[0][0] as [$lon, $lat]) {
            $this->assertGreaterThanOrEqual(-8, $lon, 'Longitude outside plausible UK bounds');
            $this->assertLessThanOrEqual(2, $lon, 'Longitude outside plausible UK bounds');
            $this->assertGreaterThanOrEqual(49, $lat, 'Latitude outside plausible UK bounds');
            $this->assertLessThanOrEqual(61, $lat, 'Latitude outside plausible UK bounds');
        }
    }

    public function testSimplificationAfterReprojectionDropsWobblePoint(): void
    {
        $wkbReader = new WkbGeometryReader();
        $simplifier = new PolygonSimplifier(new DouglasPeucker());

        $tableReader = new GeoPackageTableReader($this->gpkgPath);
        $rows = iterator_to_array($tableReader->readTable('parishes', 'geom'));
        $tableReader->close();

        $osgb36Geometry = $wkbReader->readMultiPolygon($rows[0]['wkb']);

        // 50m tolerance in OSGB36 metres -- simplify BEFORE reprojection,
        // per the decision to keep tolerance units unambiguous.
        $simplified = $simplifier->simplifyMultiPolygon($osgb36Geometry, 50.0);

        $this->assertCount(6, $osgb36Geometry[0][0], 'Source ring should have 6 points including wobble');
        $this->assertCount(5, $simplified[0][0], 'Wobble point should be dropped at 50m tolerance');
    }

    private function buildFixtureGeoPackage(string $path): void
    {
        $db = new \SQLite3($path);

        $db->exec("CREATE TABLE gpkg_spatial_ref_sys (srs_name TEXT NOT NULL, srs_id INTEGER NOT NULL PRIMARY KEY, organization TEXT NOT NULL, organization_coordsys_id INTEGER NOT NULL, definition TEXT NOT NULL, description TEXT)");
        $db->exec("CREATE TABLE gpkg_contents (table_name TEXT NOT NULL PRIMARY KEY, data_type TEXT NOT NULL, identifier TEXT UNIQUE, description TEXT DEFAULT '', last_change DATETIME NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')), min_x DOUBLE, min_y DOUBLE, max_x DOUBLE, max_y DOUBLE, srs_id INTEGER)");
        $db->exec("INSERT INTO gpkg_contents (table_name, data_type, identifier, srs_id) VALUES ('parishes', 'features', 'parishes', 27700)");
        $db->exec("CREATE TABLE gpkg_geometry_columns (table_name TEXT NOT NULL, column_name TEXT NOT NULL, geometry_type_name TEXT NOT NULL, srs_id INTEGER NOT NULL, z TINYINT NOT NULL, m TINYINT NOT NULL, PRIMARY KEY (table_name, column_name))");
        $db->exec("INSERT INTO gpkg_geometry_columns (table_name, column_name, geometry_type_name, srs_id, z, m) VALUES ('parishes', 'geom', 'MULTIPOLYGON', 27700, 0, 0)");
        $db->exec("CREATE TABLE parishes (id INTEGER PRIMARY KEY, name TEXT NOT NULL, authority_id INTEGER, geom BLOB)");

        $insert = $db->prepare('INSERT INTO parishes (id, name, authority_id, geom) VALUES (:id, :name, :authorityId, :geom)');
        $insert->bindValue(':id', 1, SQLITE3_INTEGER);
        $insert->bindValue(':name', 'Test Parish Near Point001', SQLITE3_TEXT);
        $insert->bindValue(':authorityId', 42, SQLITE3_INTEGER);
        $insert->bindValue(':geom', $this->buildParishGeomBlob(), SQLITE3_BLOB);
        $insert->execute();

        $db->close();
    }

    /**
     * A roughly square parish boundary near OSGB point001 (224134, 252130),
     * with a deliberate near-collinear wobble point along one edge.
     */
    private function buildParishGeomBlob(): string
    {
        $ring = [
            [224000.0, 252000.0],
            [224250.0, 252005.0], // wobble point
            [224500.0, 252000.0],
            [224500.0, 252300.0],
            [224000.0, 252300.0],
            [224000.0, 252000.0],
        ];

        $wkb = pack('C', 1) . pack('V', 6) . pack('V', 1)
            . pack('C', 1) . pack('V', 3) . pack('V', 1)
            . pack('V', count($ring));

        foreach ($ring as [$x, $y]) {
            $wkb .= pack('e', $x) . pack('e', $y);
        }

        $header = "GP" . pack('C', 0) . pack('C', 0x01) . pack('V', 27700);

        return $header . $wkb;
    }
}
