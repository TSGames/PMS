<?php

/**
 * Class pms_db_class
 * Manages database operations using SQLite3.
 */
class pms_db_class {
    private ?SQLite3 $connection = null; // Database connection instance
    private $db_name; // Name of the database
    private $valid = false; // Flag indicating if the connection is valid

    /**
     * Connects to a specified SQLite3 database.
     *
     * @param string $db The name of the database to connect to. Defaults to 'default'.
     * @return bool True on successful connection, False otherwise.
     */
    public function connect(string $db): bool {
        try {
            $this->connection = new SQLite3("/var/db/" . ($db == null ? "default" : $db) . ".sqlite");
            if ($this->connection) {
                $this->valid = true;
            }
        } catch (PDOException $e) {
        }
        return false;
    }

    /**
     * Initializes the database schema and optionally populates it with initial data.
     *
     * @param bool $withData If True, initializes the database with sample data. Defaults to False.
     * @return bool True if initialization is successful, False otherwise.
     */
    public function init($withData = false) {
        if ($this->connection) {
            $result = $this->connection->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='cat'");
            if (!$result) {
                $result = $this->connection->exec(file_get_contents(__DIR__ . "/.db_layout.sql"));
                $this->valid = !!$result;
            }
            if($withData) {
                $result = $this->connection->querySingle("SELECT * FROM config WHERE id='1'");
                if(!$result) {
                    $password = substr(bin2hex(random_bytes(20)), 0,  20);
                    error_log("Initial admin password: " . $password);
                    file_put_contents("/var/db/.init_password", $password);
                    $this->connection->exec(str_replace('$PASSWORD', $this->escape(md5($password)), file_get_contents(__DIR__ . "/.db_data.sql")));
                }
            }
            return $this->valid;
        } else {
            error_log('db not connected');
        }
    }

    /**
     * Checks if the database connection is valid.
     *
     * @return bool True if the connection is valid, False otherwise.
     */
    public function valid() {
        return $this->valid;
    }

    /**
     * Fetches a single row from a result set as an array.
     *
     * @param SQLite3Result $result The result set to fetch from.
     * @return array An associative array representing the fetched row, or an empty array if no more rows are available.
     */
    public function fetch(SQLite3Result $result): array {
        return $result->fetchArray(SQLITE3_NUM);
    }

    /**
     * Fetches all rows from a result set as objects.
     *
     * @param SQLite3Result $result The result set to fetch from.
     * @return array An array of objects representing the fetched rows, or an empty array if no more rows are available.
     */
    public function fetchAllObject(SQLite3Result $result): array {
        $collector = [];
        while(true) {
            $data = $result->fetchArray(SQLITE3_ASSOC);
            if($data === false) {
                return $collector;
            }
            $collector[] = (object)$data;
        }
    }

    /**
     * Fetches a single row from a result set as an object.
     *
     * @param SQLite3Result $result The result set to fetch from.
     * @return object|bool An object representing the fetched row, or False if no more rows are available.
     */
    public function fetchObject(SQLite3Result $result): object|bool {
        $data = $result->fetchArray(SQLITE3_ASSOC);
        if($data === false) {
            return false;
        }
        return (object)$data;
    }

    /**
     * Executes a SQL query and returns the result.
     *
     * @param string $sql The SQL query to execute.
     * @return SQLite3Result|bool The result of the query, or False on failure.
     */
    public function query(string $sql): SQLite3Result|bool {
        if (!$this->connection) return false;
        try {
            $result = $this->connection->query($sql);
            if($result === false) {
                error_log($this->error());
                return false;
            }
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Executes a SQL statement and returns the result.
     *
     * @param string $sql The SQL statement to execute.
     * @return SQLite3Result|bool The result of the execution, or False on failure.
     */
    public function exec(string $sql): SQLite3Result|bool {
        if (!$this->connection) return false;
        try {
            return $this->connection->exec($sql);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns the last error message from the database connection.
     *
     * @return string The last error message, or an empty string if no error occurred.
     */
    public function error(): string {
        return $this->connection->lastErrorMsg();
    }

    /**
     * Lists all tables in the database that are not system tables.
     *
     * @return SQLite3Result A result set containing the names of the tables.
     */
    public function list_tables(): SQLite3Result {
        return $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
    }

    /**
     * Returns the name of a table at a specified index in the result set.
     *
     * @param mixed $link The result set to fetch from.
     * @param int $i The index of the table to retrieve.
     * @return ?string The name of the table, or Null if no more tables are available.
     */
    public function tablename($link, int $i): ?string {
        return mysqli_tablename($link, $i);
    }

    /**
     * Escapes a string for use in an SQL query.
     *
     * @param ?string $str The string to escape.
     * @return string The escaped string.
     */
    public function escape(?string $str): string {
        return $this->connection->escapeString(stripslashes($str));
    }

    /**
     * Returns the last insert row ID from the database connection.
     *
     * @return mixed The last insert row ID, or Null if no rows were inserted.
     */
    public function lastInsertId() {
        return $this->fetch($this->query("SELECT last_insert_rowid();"))[0];
    }
}

$pms_db_connection = new pms_db_class();
$pms_db_connection->connect($db_databasename);

/**
 * Fetches an object from a result set.
 *
 * @param mixed $link The result set to fetch from.
 * @return object|bool An object representing the fetched row, or False if no more rows are available.
 */
function mysqli_fetch_object($link) {
    global $pms_db_connection ;
    return $pms_db_connection->fetchObject($link);
}

/**
 * Executes a SQL query and returns the result.
 *
 * @param string $query The SQL query to execute.
 * @return ?SQLite3Result The result of the query, or Null on failure.
 */
function pms_query(string $query): ?SQLite3Result {
    global $pms_db_connection;
    return $pms_db_connection->query($query);
}

/**
 * Fetches a value from the database based on specified parameters.
 *
 * @param string $typ The type of data to fetch.
 * @param null|int|string $id The ID of the data to fetch.
 * @param string $what The column(s) to retrieve.
 * @param int $caching Cache flag (not used).
 * @param string $field The field name to match against.
 * @param string $where_add Additional WHERE clause conditions.
 * @return ?string The fetched value, or an empty string if no data is found.
 */
function from_db(string $typ, null|int|string $id, string $what, int $caching = 1, string $field = "id", string $where_add = ""): ?string {
    global $pms_db_prefix;
    global $pms_db_connection;

    $do = "SELECT " . $what . " FROM " . $pms_db_prefix . $typ . " WHERE " . $field . " = '" . $pms_db_connection->escape($id) . "'" . $where_add . " LIMIT 1;";
    if ($result = $pms_db_connection->query($do)) {
        $link = $result->fetchArray(SQLITE3_NUM);
        return $link[0];
    }

    return "";
}

/**
 * Updates the database schema to a specified version.
 *
 * @param bool $do If True, performs the update. Defaults to False.
 * @param int $last_version The last known version of the database schema.
 * @param string $pms_db_prefix Database prefix (not used).
 * @param string $file Path to the SQL file containing updates.
 * @param mixed $connection Database connection instance (not used).
 * @return array An array with two elements: the number of queries executed and the total number of queries attempted.
 */
function update_engine(bool $do = false, int $last_version = 0, string $pms_db_prefix = "", string $file = "update.sql", $connection = NULL): array {
    global $pms_db_connection;
    if ($connection) $pms_db_connection = $connection;
    $update = str_replace("#table", $pms_db_prefix, @file_get_contents($file));
    $update = explode("#Update Start", $update);
    $a_count = 0;
    $b_count = 0;

    if ($do) {
        $file = fopen("update_sql.log", "w+");
        fwrite($file, "Starting Database Update... (Last Version $last_version)");
    }

    foreach ($update as $query) {
        $temp = explode("\n", $query);
        for ($i = 0; $i < count($temp); $i++) {
            if (substr($temp[$i], 0, 9) == "#Version ") {
                if (substr($temp[$i], 9) > $last_version) {
                    if ($do) fwrite($file, "\n\nRunning Update for Version " . substr($temp[$i], 9) . "...");
                    unset($temp[$i]);
                    $query = explode("#Update End", implode("\n", $temp));
                    if ($do) {
                        $query = explode(";", $query[0]);
                        for ($i = 0; $i < count($query); $i++) {
                            if (!$query[$i] || ctype_space($query[$i])) continue;
                            $result = $pms_db_connection->query($query[$i]);
                            if (!$result) fwrite($file, "\nError while executing command '" . trim($query[$i]) . "':\n" . $pms_db_connection->error());
                            $a_count += $result;
                            $b_count++;
                        }
                    } else {
                        $a_count++;
                    }
                }
            }
        }
    }

    if ($do) {
        fclose($file);
        return array($a_count, $b_count);
}

    return array($a_count, 0);
}

function mysqli_field_name($result, int $field_offset): ?string
{
    $properties = mysqli_fetch_field_direct($result, $field_offset);
    return is_object($properties) ? $properties->name : null;
}

?>
