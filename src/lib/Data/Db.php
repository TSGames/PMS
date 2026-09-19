<?php

namespace Pms\Data;

use SQLite3Result;
use SQLite3Stmt;

/**
 * Datenbankzugriff mit vorbereiteten Anweisungen.
 *
 * Der Altbestand setzt SQL aus Zeichenketten zusammen und übergibt Werte aus
 * $_POST teilweise ungeprüft. Alle neu geschriebenen Bereiche benutzen
 * ausschließlich diese Klasse; Werte werden gebunden statt eingesetzt.
 *
 * Tabellennamen werden immer mit dem konfigurierten Präfix versehen.
 */
final class Db
{
    /** Vollständiger Tabellenname inklusive Präfix. */
    public static function table(string $table): string
    {
        return ($GLOBALS['pms_db_prefix'] ?? '') . $table;
    }

    /**
     * Liefert alle Zeilen einer Abfrage als Objekte.
     *
     * @return list<object>
     */
    public static function select(string $sql, array $params = []): array
    {
        $result = self::run($sql, $params);
        if (!$result instanceof SQLite3Result) {
            return [];
        }
        $rows = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $rows[] = (object)$row;
        }
        $result->finalize();
        return $rows;
    }

    /** Erste Zeile einer Abfrage oder null. */
    public static function first(string $sql, array $params = []): ?object
    {
        $result = self::run($sql, $params);
        if (!$result instanceof SQLite3Result) {
            return null;
        }
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $result->finalize();
        return $row === false ? null : (object)$row;
    }

    /** Erster Wert der ersten Zeile. */
    public static function value(string $sql, array $params = []): mixed
    {
        $result = self::run($sql, $params);
        if (!$result instanceof SQLite3Result) {
            return null;
        }
        $row = $result->fetchArray(SQLITE3_NUM);
        $result->finalize();
        return $row === false ? null : $row[0];
    }

    /** Führt eine verändernde Anweisung aus. */
    public static function execute(string $sql, array $params = []): bool
    {
        return self::run($sql, $params) !== false;
    }

    /**
     * Fügt einen Datensatz ein und liefert dessen ID.
     *
     * @param array<string, scalar|null> $data Spalte => Wert
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        $sql = 'INSERT INTO ' . self::table($table)
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        if (!self::execute($sql, $data)) {
            return 0;
        }
        return (int)self::value('SELECT last_insert_rowid()');
    }

    /**
     * Aktualisiert einen Datensatz anhand seiner ID.
     *
     * @param array<string, scalar|null> $data Spalte => Wert
     */
    public static function update(string $table, int $id, array $data): bool
    {
        if ($data === []) {
            return true;
        }
        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }
        $data['pms_id'] = $id;

        $sql = 'UPDATE ' . self::table($table) . ' SET ' . implode(', ', $assignments) . ' WHERE id = :pms_id';
        return self::execute($sql, $data);
    }

    /** Löscht einen Datensatz anhand seiner ID. */
    public static function delete(string $table, int $id): bool
    {
        return self::execute('DELETE FROM ' . self::table($table) . ' WHERE id = :id', ['id' => $id]);
    }

    /** Zählt Datensätze, optional eingeschränkt. */
    public static function count(string $table, string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::table($table);
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        return (int)self::value($sql, $params);
    }

    /**
     * Bereitet eine Anweisung vor, bindet die Werte und führt sie aus.
     */
    private static function run(string $sql, array $params): SQLite3Result|false
    {
        $statement = self::prepare($sql);
        if (!$statement instanceof SQLite3Stmt) {
            error_log('SQL konnte nicht vorbereitet werden: ' . $sql);
            return false;
        }

        foreach ($params as $name => $value) {
            $statement->bindValue(is_int($name) ? $name + 1 : ':' . $name, $value, self::typeOf($value));
        }

        $result = $statement->execute();
        if ($result === false) {
            error_log('SQL fehlgeschlagen: ' . $sql . ' (' . self::connection()->error() . ')');
        }
        return $result;
    }

    private static function prepare(string $sql): SQLite3Stmt|false
    {
        return self::connection()->prepare($sql);
    }

    private static function connection(): \pms_db_class
    {
        return $GLOBALS['pms_db_connection'];
    }

    private static function typeOf(mixed $value): int
    {
        return match (true) {
            $value === null => SQLITE3_NULL,
            is_int($value), is_bool($value) => SQLITE3_INTEGER,
            is_float($value) => SQLITE3_FLOAT,
            default => SQLITE3_TEXT,
        };
    }
}
