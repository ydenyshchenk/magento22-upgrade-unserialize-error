<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new Exception($errstr);
}
set_error_handler("exception_error_handler");

class CheckSerializedData
{
    /** @var SimpleXMLElement */
    protected $xml;

    /** @var PDO */
    protected $pdo;

    protected $table;

    protected $columns;

    protected $where = [];

    protected $idKey = null;

    public $counter = 0;

    /**
     * Syncer constructor.
     *
     * @param array $dbc
     * @return CheckSerializedData
     * @throws PDOException
     */
    function __construct(array $dbc)
    {
        try {
            $this->pdo = new PDO(
                "mysql:host={$dbc['host']};charset=utf8",
                $dbc['username'],
                $dbc['password']
            );
        } catch (PDOException  $e ) {
            echo "Error: " . $e . "\n";
        }

        $this->parseArgs();

        $columns = $this->pdo->query("SHOW COLUMNS FROM {$this->table}")->fetchAll();
        foreach ($columns as $column) {
            if ($column['Key'] == 'PRI') {
                $this->idKey = $column['Field'];
                break;
            }
        }

        if ($this->idKey === null) {
            exit('Mentioned table has no PRIMARY index');
        }

        return $this;
    }

    protected function parseArgs()
    {
        global $argv;
        unset($argv[0]);

        foreach ($argv as $arg) {
            $argKV = explode('=', $arg, 2);
            if (count($argKV) == 2) {
                list($key, $value) = $argKV;

                if (preg_match('/table/i', $key)) {
                    $this->table = $value;
                } elseif (preg_match('/column/i', $key)) {
                    $this->columns = explode(',', $value);
                } elseif (preg_match('/where/i', $key)) {
                    $this->where[] = $value;
                }
            }
        }

        if ($this->table === null) {
            exit("Please specify table to check\n");
        }

        if ($this->columns === null) {
            exit("Please specify column to check\n");
        }
    }

    public function walkTable()
    {
        $offset = 0;
        $limit = 100;
        $lastId = 0;

        $start = microtime(true);

        $where = $this->where;
        $where['pri'] = "{$this->idKey} > $lastId";
        $whereStr = implode(' and ', $where);
        $query = "select * from {$this->table} where $whereStr limit $limit";
        $result = $this->pdo->query($query);
        $rows = $result->fetchAll();

        $brokenIds = [];

        while(count($rows) > 0) {

            foreach ($rows as $row) {
                foreach ($this->columns as $column) {
                    $value = $row[$column];
                    if (preg_match('/^[\{\[]/', $value)) {
                        continue;
                    } elseif (preg_match('/[\:\{\}]/', $value)) {
                        try {
                            unserialize($value);
                        } catch (Exception $e) {
                            $brokenIds[] = $row[$this->idKey];
                            echo 'E';
                        }
                    }
                }
                $this->counter++;
            }

            echo '.';
            $lastId = $row[$this->idKey];
            $where['pri'] = "{$this->idKey} > $lastId";
            $whereStr = implode(' and ', $where);
            $query = "select * from {$this->table} where $whereStr limit $limit";
            $result = $this->pdo->query($query . " offset $offset");
            $rows = $result->fetchAll();
        }

        $setColumnsNullArr = [];
        foreach ($this->columns as $column) {
            $setColumnsNullArr[] = "`$column` = null";
        }
        $setColumnsNull = implode(',', $setColumnsNullArr);
        $updateIds = implode(',', $brokenIds);
        $sql = "update {$this->table} set $setColumnsNull where `option_id` in ($updateIds);";

        $columns = implode('', $this->columns);

        $end = microtime(true);
        $time = $end - $start;

        echo "\n\nReviewed {$this->counter} items in {$time} seconds \n";

        if ($updateIds) {
            echo "Please execute the next SQL command to set NULL for columns {$columns}"
                . " in all broken values in table {$this->table}: \n\n";

            echo "$sql \n\n";
        } else {
            echo "All values of columns {$columns} of table {$this->table} may be unserialized successfully \n";
        }
    }
}

$dbConfig = [
    'host' => 'localhost',
    'username' => 'm',
    'password' => ''
];

$monkey = new CheckSerializedData($dbConfig);
$monkey->walkTable();
