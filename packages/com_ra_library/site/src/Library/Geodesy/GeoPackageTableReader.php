<?php

namespace Ramblers\Component\Ra_library\Site\Library\Geodesy;

/**
 * Reads feature rows out of a GeoPackage (.gpkg) file via PHP's SQLite3
 * extension -- no GDAL/ogr2ogr required, since GeoPackage is itself a
 * SQLite database.
 *
 * Usage:
 *   $reader = new GeoPackageTableReader('/path/to/boundary_line.gpkg');
 *   foreach ($reader->readTable('parishes', 'geom') as $row) {
 *       // $row['attributes'] = ['id' => ..., 'name' => ..., ...]
 *       // $row['srid']       = int
 *       // $row['wkb']        = raw WKB string, ready for WkbGeometryReader
 *   }
 *
 * Every SQLite call here checks its return value explicitly and throws a
 * RuntimeException carrying SQLite3::lastErrorMsg() on failure -- added
 * after a real "Call to a member function fetchArray() on false" fatal
 * was hit in practice, which gave no indication of the actual underlying
 * SQLite error. query()/prepare()/execute() can all return false on
 * failure; the original version of this class didn't check for that.
 */
final class GeoPackageTableReader
{
    private \SQLite3 $db;
    private GeoPackageBinaryReader $headerReader;

    public function __construct(string $geoPackagePath)
    {
        if (!is_readable($geoPackagePath)) {
            throw new \RuntimeException("GeoPackage file not readable: {$geoPackagePath}");
        }

        $this->db = new \SQLite3($geoPackagePath, SQLITE3_OPEN_READONLY);
        $this->headerReader = new GeoPackageBinaryReader();
    }

    /**
     * List feature tables registered in gpkg_contents, along with their
     * geometry column name (from gpkg_geometry_columns) -- useful for
     * discovering table/column names before calling readTable(), rather
     * than guessing.
     *
     * @return array<int, array{tableName: string, geometryColumn: string, dataType: string}>
     */
    public function listFeatureTables(): array
    {
        $sql = "
            SELECT c.table_name, g.column_name, c.data_type
            FROM gpkg_contents c
            JOIN gpkg_geometry_columns g ON g.table_name = c.table_name
            WHERE c.data_type = 'features'
        ";

        $result = $this->querySafe($sql);
        $tables = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tables[] = [
                'tableName'      => $row['table_name'],
                'geometryColumn' => $row['column_name'],
                'dataType'       => $row['data_type'],
            ];
        }

        return $tables;
    }

    /**
     * Get the list of non-geometry column names for a table -- useful for
     * discovering what attribute fields are available before reading.
     *
     * @return array<int, string>
     */
    public function listAttributeColumns(string $tableName, string $geometryColumn): array
    {
        $result = $this->querySafe(
            "PRAGMA table_info(" . $this->quoteIdentifier($tableName) . ")",
            "listAttributeColumns('{$tableName}')"
        );
        $columns = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] !== $geometryColumn) {
                $columns[] = $row['name'];
            }
        }

        // PRAGMA table_info() returns an EMPTY result set (not a query
        // failure) if the table doesn't exist -- worth surfacing that
        // distinctly, since it's a very plausible real-world cause (a
        // typo'd table name, or a table genuinely absent in a particular
        // Boundary-Line release) and would otherwise silently produce an
        // empty column list rather than an obvious error.
        if ($columns === [] && !$this->tableExists($tableName)) {
            throw new \RuntimeException("Table '{$tableName}' does not exist in this GeoPackage.");
        }

        return $columns;
    }

    /**
     * Read all rows from a feature table, yielding each row's attributes
     * plus its geometry already unwrapped from the GeoPackage binary
     * header (i.e. ready to hand straight to WkbGeometryReader).
     *
     * Uses a generator so large tables (10,000+ parishes) don't need to
     * be held fully in memory at once -- pairs naturally with a
     * batched/resumable import.
     *
     * @return \Generator<array{attributes: array<string, mixed>, srid: int, wkb: string}>
     */
    public function readTable(string $tableName, string $geometryColumn, ?int $limit = null, int $offset = 0): \Generator
    {
        $attributeColumns = $this->listAttributeColumns($tableName, $geometryColumn);
        $columnList = implode(', ', array_map([$this, 'quoteIdentifier'], $attributeColumns));

        $sql = sprintf(
            'SELECT %s, %s FROM %s LIMIT :limit OFFSET :offset',
            $columnList,
            $this->quoteIdentifier($geometryColumn),
            $this->quoteIdentifier($tableName)
        );

        $stmt = $this->prepareSafe($sql, "readTable('{$tableName}')");
        $stmt->bindValue(':limit', $limit ?? -1, SQLITE3_INTEGER); // -1 = no limit, in SQLite
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $result = $stmt->execute();

        if ($result === false) {
            throw new \RuntimeException(
                "readTable('{$tableName}') execute() failed: " . $this->db->lastErrorMsg()
            );
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $geomBlob = $row[$geometryColumn];
            unset($row[$geometryColumn]);

            if ($geomBlob === null) {
                // Some rows may legitimately have null geometry -- skip,
                // but this is worth logging at the caller level.
                continue;
            }

            $stripped = $this->headerReader->stripHeader($geomBlob);

            yield [
                'attributes' => $row,
                'srid'       => $stripped['srid'],
                'wkb'        => $stripped['wkb'],
            ];
        }
    }

    /**
     * Count total rows in a table -- useful for planning batch chunk
     * boundaries.
     */
    public function countRows(string $tableName): int
    {
        $result = $this->db->querySingle(
            'SELECT COUNT(*) FROM ' . $this->quoteIdentifier($tableName)
        );

        if ($result === null && $this->db->lastErrorCode() !== 0) {
            throw new \RuntimeException(
                "countRows('{$tableName}') failed: " . $this->db->lastErrorMsg()
            );
        }

        return (int) $result;
    }

    /**
     * List distinct values (with row counts) for a given column on a
     * table -- useful for inspecting an unfamiliar layer's categorical
     * fields (e.g. an admin-unit-type column) before deciding how to
     * filter it for import.
     *
     * Validates the column actually exists on the table first, since
     * this is expected to be called from an admin-facing inspection
     * tool where the column name may be user-supplied.
     *
     * @return array<int, array{value: mixed, count: int}>
     */
    public function listDistinctValues(string $tableName, string $columnName, int $limit = 50): array
    {
        $validColumns = $this->listAllColumns($tableName);

        if (!in_array($columnName, $validColumns, true)) {
            throw new \InvalidArgumentException(
                "Column '{$columnName}' does not exist on table '{$tableName}'."
            );
        }

        $sql = sprintf(
            'SELECT %s AS value, COUNT(*) AS count FROM %s GROUP BY %s ORDER BY count DESC LIMIT :limit',
            $this->quoteIdentifier($columnName),
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName)
        );

        $stmt = $this->prepareSafe($sql, "listDistinctValues('{$tableName}', '{$columnName}')");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result === false) {
            throw new \RuntimeException(
                "listDistinctValues('{$tableName}', '{$columnName}') execute() failed: " . $this->db->lastErrorMsg()
            );
        }

        $values = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $values[] = ['value' => $row['value'], 'count' => (int) $row['count']];
        }

        return $values;
    }

    /**
     * List ALL column names on a table, including the geometry column --
     * used internally for validating column names against user input.
     * Unlike listAttributeColumns(), this doesn't need to know which
     * column is the geometry column, so it's usable for validation even
     * when the caller hasn't looked that up yet.
     *
     * @return array<int, string>
     */
    public function listAllColumns(string $tableName): array
    {
        $result = $this->querySafe(
            "PRAGMA table_info(" . $this->quoteIdentifier($tableName) . ")",
            "listAllColumns('{$tableName}')"
        );
        $columns = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }

        return $columns;
    }

    /**
     * Sample raw attribute rows from a table (excluding the geometry
     * column, which isn't useful to display as-is) -- for eyeballing real
     * data when column names/values alone don't make a layer's structure
     * clear.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sampleRows(string $tableName, string $geometryColumn, int $limit = 10): array
    {
        $columns = $this->listAttributeColumns($tableName, $geometryColumn);
        $columnList = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));

        $sql = sprintf(
            'SELECT %s FROM %s LIMIT :limit',
            $columnList,
            $this->quoteIdentifier($tableName)
        );

        $stmt = $this->prepareSafe($sql, "sampleRows('{$tableName}')");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result === false) {
            throw new \RuntimeException(
                "sampleRows('{$tableName}') execute() failed: " . $this->db->lastErrorMsg()
            );
        }

        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Check whether a table genuinely exists in the database -- queries
     * sqlite_master directly, independent of PRAGMA table_info()'s
     * behaviour of returning an empty (not failed) result for a missing
     * table.
     */
    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM sqlite_master WHERE type = :type AND name = :name');

        if ($stmt === false) {
            throw new \RuntimeException('tableExists() prepare failed: ' . $this->db->lastErrorMsg());
        }

        $stmt->bindValue(':type', 'table', SQLITE3_TEXT);
        $stmt->bindValue(':name', $tableName, SQLITE3_TEXT);

        $result = $stmt->execute();

        if ($result === false) {
            throw new \RuntimeException('tableExists() execute failed: ' . $this->db->lastErrorMsg());
        }

        return $result->fetchArray(SQLITE3_ASSOC) !== false;
    }

    /**
     * Run a query, throwing with SQLite's actual error message if it fails,
     * rather than letting the caller crash on a false result.
     */
    private function querySafe(string $sql, string $context = ''): \SQLite3Result
    {
        $result = $this->db->query($sql);

        if ($result === false) {
            $suffix = $context !== '' ? " ({$context})" : '';

            throw new \RuntimeException(
                "SQLite query failed{$suffix}: " . $this->db->lastErrorMsg() . " -- SQL: {$sql}"
            );
        }

        return $result;
    }

    /**
     * Prepare a statement, throwing with SQLite's actual error message if
     * it fails, rather than letting the caller crash on a false result.
     */
    private function prepareSafe(string $sql, string $context = ''): \SQLite3Stmt
    {
        $stmt = $this->db->prepare($sql);

        if ($stmt === false) {
            $suffix = $context !== '' ? " ({$context})" : '';

            throw new \RuntimeException(
                "SQLite prepare failed{$suffix}: " . $this->db->lastErrorMsg() . " -- SQL: {$sql}"
            );
        }

        return $stmt;
    }

    private function quoteIdentifier(string $identifier): string
    {
        // SQLite identifier quoting -- double quotes, with embedded
        // double quotes escaped by doubling. Defensive since table/column
        // names here originate from the GeoPackage's own metadata, not
        // user input, but cheap to do correctly regardless.
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function close(): void
    {
        $this->db->close();
    }
}
