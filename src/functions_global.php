<?php
// global functions
// also included in the install routine

class pms_db_class
{
    private ?SQLite3 $connection = null;
    private $db_name;
    private $valid = false;
    

    public function connect(string $db): bool
    {
        try {
            $this->connection = new SQLite3("/var/db/" . ($db == null ? "default" : $db) . ".sqlite");
            if ($this->connection) {
                $this->valid = true;
            }
        } catch (PDOException $e) {
        }
        return false;
    }
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
        }
    }
    public function valid() {
        return $this->valid;
    }
    public function fetch(SQLite3Result $result): array {
        return $result->fetchArray(SQLITE3_NUM);
    }
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
    public function fetchObject(SQLite3Result $result): object|bool {
        $data = $result->fetchArray(SQLITE3_ASSOC);
        if($data === false) {
            return false;
        }
        return (object)$data;
    }
    public function query(string $sql): SQLite3Result|bool
    {
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
    public function exec(string $sql): SQLite3Result|bool
    {
        if (!$this->connection) return false;
        try {
            return $this->connection->exec($sql);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function error(): string
    {
        return $this->connection->lastErrorMsg();
    }

    public function list_tables(): ?mysqli_result
    {
        return $this->query("SHOW TABLES FROM " . $this->escape($this->db_name));
    }

    public function tablename($link, int $i): ?string
    {
        return mysqli_tablename($link, $i);
    }

    public function escape(?string $str): string
    {
        return $this->connection->escapeString(stripslashes($str));
    }
    public function lastInsertId() {
        return $this->fetch($this->query("SELECT last_insert_rowid();"))[0];
    }
}

$pms_db_connection = new pms_db_class();
$pms_db_connection->connect($db_databasename);

function mysqli_fetch_object($link) {
    global $pms_db_connection ;
    return $pms_db_connection->fetchObject($link);
}
function pms_query(string $query): ?SQLite3Result
{
    global $pms_db_connection;
    return $pms_db_connection->query($query);
}

function from_db(string $typ, null|int|string $id, string $what, int $caching = 1, string $field = "id", string $where_add = ""): ?string
{
    global $pms_db_prefix;
    global $pms_db_connection;

    $do = "SELECT " . $what . " FROM " . $pms_db_prefix . $typ . " WHERE " . $field . " = '" . $pms_db_connection->escape($id) . "'" . $where_add . " LIMIT 1;";
    if ($result = $pms_db_connection->query($do)) {
        $link = $result->fetchArray(SQLITE3_NUM);
        return $link[0];
    }

    return "";
}

function update_engine(bool $do = false, int $last_version = 0, string $pms_db_prefix = "", string $file = "update.sql", $connection = NULL): array
{
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
