<?php

namespace Ramblers\Component\RaLibrary\Tests\Unit\Library\Geodesy;

use PHPUnit\Framework\TestCase;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\GeoPackageTableReader;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\WkbGeometryReader;

/**
 * Regression suite for GeoPackageTableReader, using a minimal but
 * spec-compliant .gpkg file built in setUp() (required gpkg_contents /
 * gpkg_geometry_columns / gpkg_spatial_ref_sys admin tables, plus a
 * small "parishes" feature table with two rows of real GeoPackage-header
 * -wrapped WKB geometry).
 */
final class GeoPackageTableReaderTest extends TestCase
{
    private string $gpkgPath;

    protected function setUp(): void
    {
        $this->gpkgPath = sys_get_temp_dir() . '/ra_pathnetwork_test_' . uniqid() . '.gpkg';
        $this->buildFixtureGeoPackage($this->gpkgPath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->gpkgPath)) {
            unlink($this->gpkgPath);
        }
    }

    public function testListFeatureTablesFindsParishesTable(): void
    {
        $reader = new GeoPackageTableReader($this->gpkgPath);
        $tables = $reader->listFeatureTables();
        $reader->close();

        $this->assertCount(1, $tables);
        $this->assertSame('parishes', $tables[0]['tableName']);
        $this->assertSame('geom', $tables[0]['geometryColumn']);
    }

    public function testListAttributeColumnsExcludesGeometryColumn(): void
    {
        $reader = new GeoPackageTableReader($this->gpkgPath);
        $columns = $reader->listAttributeColumns('parishes', 'geom');
        $reader->close();

        $this->assertSame(['id', 'name', 'authority_id'], $columns);
    }

    public function testCountRowsMatchesFixtureData(): void
    {
        $reader = new GeoPackageTableReader($this->gpkgPath);
        $count = $reader->countRows('parishes');
        $reader->close();

        $this->assertSame(2, $count);
    }

    public function testReadTableYieldsCorrectSridAndGeometry(): void
    {
        $reader = new GeoPackageTableReader($this->gpkgPath);
        $wkbReader = new WkbGeometryReader();

        $rows = iterator_to_array($reader->readTable('parishes', 'geom'));
        $reader->close();

        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->assertSame(27700, $row['srid'], 'Expected OSGB36 (EPSG:27700) SRID from GeoPackage header');

            $parsed = $wkbReader->readMultiPolygon($row['wkb']);
            $this->assertCount(5, $parsed[0][0], 'Expected 5-point closed square ring');
        }

        $this->assertSame('Test Parish One', $rows[0]['attributes']['name']);
        $this->assertSame(101, $rows[0]['attributes']['authority_id']);
    }

    private function buildFixtureGeoPackage(string $path): void
    {
        $db = new \SQLite3($path);

        $db->exec("
            CREATE TABLE gpkg_spatial_ref_sys (
                srs_name TEXT NOT NULL,
                srs_id INTEGER NOT NULL PRIMARY KEY,
                organization TEXT NOT NULL,
                organization_coordsys_id INTEGER NOT NULL,
                definition TEXT NOT NULL,
                description TEXT
            )
        ");

        $db->exec("
            INSERT INTO gpkg_spatial_ref_sys (srs_name, srs_id, organization, organization_coordsys_id, definition, description)
            VALUES ('OSGB 1936 / British National Grid', 27700, 'EPSG', 27700, 'PROJCS[...]', 'British National Grid')
        ");

        $db->exec("
            CREATE TABLE gpkg_contents (
                table_name TEXT NOT NULL PRIMARY KEY,
                data_type TEXT NOT NULL,
                identifier TEXT UNIQUE,
                description TEXT DEFAULT '',
                last_change DATETIME NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
                min_x DOUBLE, min_y DOUBLE, max_x DOUBLE, max_y DOUBLE,
                srs_id INTEGER
            )
        ");

        $db->exec("
            INSERT INTO gpkg_contents (table_name, data_type, identifier, srs_id)
            VALUES ('parishes', 'features', 'parishes', 27700)
        ");

        $db->exec("
            CREATE TABLE gpkg_geometry_columns (
                table_name TEXT NOT NULL,
                column_name TEXT NOT NULL,
                geometry_type_name TEXT NOT NULL,
                srs_id INTEGER NOT NULL,
                z TINYINT NOT NULL,
                m TINYINT NOT NULL,
                PRIMARY KEY (table_name, column_name)
            )
        ");

        $db->exec("
            INSERT INTO gpkg_geometry_columns (table_name, column_name, geometry_type_name, srs_id, z, m)
            VALUES ('parishes', 'geom', 'MULTIPOLYGON', 27700, 0, 0)
        ");

        $db->exec("
            CREATE TABLE parishes (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                authority_id INTEGER,
                geom BLOB
            )
        ");

        $insert = $db->prepare('INSERT INTO parishes (id, name, authority_id, geom) VALUES (:id, :name, :authorityId, :geom)');

        $insert->bindValue(':id', 1, SQLITE3_INTEGER);
        $insert->bindValue(':name', 'Test Parish One', SQLITE3_TEXT);
        $insert->bindValue(':authorityId', 101, SQLITE3_INTEGER);
        $insert->bindValue(':geom', $this->buildGeoPackageGeomBlob(400000, 300000), SQLITE3_BLOB);
        $insert->execute();

        $insert->reset();
        $insert->bindValue(':id', 2, SQLITE3_INTEGER);
        $insert->bindValue(':name', 'Test Parish Two', SQLITE3_TEXT);
        $insert->bindValue(':authorityId', 101, SQLITE3_INTEGER);
        $insert->bindValue(':geom', $this->buildGeoPackageGeomBlob(400500, 300500), SQLITE3_BLOB);
        $insert->execute();

        $db->close();
    }

    private function buildGeoPackageGeomBlob(float $offsetE, float $offsetN): string
    {
        $ring = [
            [$offsetE + 0.0, $offsetN + 0.0],
            [$offsetE + 100.0, $offsetN + 0.0],
            [$offsetE + 100.0, $offsetN + 100.0],
            [$offsetE + 0.0, $offsetN + 100.0],
            [$offsetE + 0.0, $offsetN + 0.0],
        ];

        $wkb = '';
        $wkb .= pack('C', 1);
        $wkb .= pack('V', 6);
        $wkb .= pack('V', 1);
        $wkb .= pack('C', 1);
        $wkb .= pack('V', 3);
        $wkb .= pack('V', 1);
        $wkb .= pack('V', count($ring));

        foreach ($ring as [$x, $y]) {
            $wkb .= pack('e', $x);
            $wkb .= pack('e', $y);
        }

        $header = '';
        $header .= "GP";
        $header .= pack('C', 0);
        $header .= pack('C', 0x01);
        $header .= pack('V', 27700);

        return $header . $wkb;
    }
}
