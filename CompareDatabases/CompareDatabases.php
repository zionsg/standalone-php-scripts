<?php
/**
 * Compare columns and rows between 2 databases
 *
 * Usage:
 *   set_time_limit(0);
 *   $app = new CompareDatabases('old', 'new', 'root', '', 'localhost');
 *   $app(); // prints out HTML
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/CompareDatabases
 * @since  2017-02-19T15:00+08:00
 */
class CompareDatabases
{
    const RESULT = 'result';
    const DELETED = 'deleted';
    const INSERTED = 'inserted';
    const UPDATED = 'updated';
    const COLOR_DELETED = '#c00000';
    const COLOR_INSERTED = '#00c000';
    const COLOR_UPDATED = '#0000c0';

    /**
     * @var string
     */
    protected $db1;

    /**
     * @var string
     */
    protected $db2;

    /**
     * @var PDO
     */
    protected $conn;

    public function __construct($db1, $db2, $dbUser, $dbPassword, $dbHost)
    {
        $this->db1 = $db1;
        $this->db2 = $db2;
        $this->conn = new PDO(
            "mysql:host={$dbHost};charset=UTF8",
            $dbUser,
            $dbPassword,
            [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function __invoke()
    {
        $db1 = $this->db1;
        $db2 = $this->db2;
        $databases = [$db1, $db2];

        // Get columns and rows for databases
        $info = [];
        foreach ($databases as $database) {
            $info[$database] = $this->getDatabase($database);
        }

        echo '<pre>';
        printf('Comparing databases <b>"%s"</b> and <b>"%s"</b>' . "\n\n\n", $db1, $db2);

        // Check if tables are the same
        $diffKeys = $this->getDiffKeys($info[$db1], $info[$db2]);
        if ($diffKeys[self::RESULT]) {
            printf("Different tables between both databases: %s\n\n", json_encode($diffKeys));
        }

        // Go thru each table in db1 and compare with corresponding table in db2
        foreach ($info[$db1] as $table1 => $table1Info) {
            $table1Name = "{$db1}.{$table1}";
            $table2Name = "{$db2}.{$table1}";
            printf("<b>%s</b>\n", $table1);

            // Get info for corresponding table in db2
            $table2Info = $info[$db2][$table1] ?? null;
            if (null === $table2Info) {
                printf("%s not found\n", $table2Name);
                continue;
            }

            // Check if columns are the same in both tables
            $diffCols = $this->getDiff($table1Info['columns'], $table2Info['columns']);
            if ($diffCols[self::RESULT]) {
                printf("Different columns: %s\n", json_encode($diffCols));
            }

            // Rows for both tables - retrieve only as needed to reduce memory usage
            $table1Rows = $this->getRows($db1, $table1);
            $table2Rows = $this->getRows($db2, $table1);
            $diffIds = $this->getDiffKeys($table1Rows, $table2Rows);
            $hasChanges = $diffIds[self::RESULT];

            // Show removed rows
            if ($diffIds[self::DELETED]) {
                $this->printColor(self::COLOR_DELETED, sprintf(
                    "Deleted ids: %s\nDeleted rows: %s\n",
                    json_encode($diffIds[self::DELETED]),
                    json_encode($this->getRowsForIds($diffIds[self::DELETED], $table1Rows))
                ));
            }

            // Show inserted rows
            if ($diffIds[self::INSERTED]) {
                $this->printColor(self::COLOR_INSERTED, sprintf(
                    "Inserted ids: %s\nInserted rows: %s\n",
                    json_encode($diffIds[self::INSERTED]),
                    json_encode($this->getRowsForIds($diffIds[self::INSERTED], $table2Rows))
                ));
            }

            // Go thru common rows and check for updates
            $commonIds = array_intersect_key($table1Rows, $table2Rows);
            $updatedIds = [];
            $updatedRows = [];
            foreach ($commonIds as $id => $row) {
                $row1 = $table1Rows[$id];
                $row2 = $table2Rows[$id];
                if (json_encode($row1) != json_encode($row2)) {
                    $updatedIds[] = $id;

                    // Find out what was updated
                    $updatedCols = [];
                    foreach ($row1 as $column => $value1) {
                        $value2 = $row2[$column];
                        if ($value2 != $value1) {
                            $updatedCols[$column] = [$db1 => $value1, $db2 => $value2];
                        }
                    }
                    $updatedRows[$id] = $updatedCols;
                }
            }
            if ($updatedIds) {
                $this->printColor(self::COLOR_UPDATED, sprintf(
                    "Updated ids: %s\nUpdated rows (changes btw dbs): %s\n",
                    json_encode($updatedIds),
                    json_encode($updatedRows)
                ));
            }

            if (! $diffIds[self::RESULT] && ! $updatedIds) {
                printf("No changes\n");
            }
            echo "\n\n";
        }

        echo '</pre>';
    }

    protected function printColor($colorCode, $text)
    {
        printf('<div style="color:%s;">%s</div>', $colorCode, $text);
    }

    protected function getRowsForIds($ids, $rows)
    {
        return array_intersect_key($rows, array_flip($ids));
    }

    protected function getDiffKeys($array1, $array2)
    {
        $deleted = array_values(array_diff(array_keys($array1), array_keys($array2)));
        $inserted = array_values(array_diff(array_keys($array2), array_keys($array1)));

        return [
            self::RESULT => ($deleted || $inserted),
            self::DELETED => $deleted,
            self::INSERTED => $inserted,
        ];
    }

    protected function getDiff($array1, $array2)
    {
        $deleted = array_values(array_diff($array1, $array2));
        $inserted = array_values(array_diff($array2, $array1));

        return [
            self::RESULT => ($deleted || $inserted),
            self::DELETED => $deleted,
            self::INSERTED => $inserted,
        ];
    }

    protected function getDatabase($database)
    {
        $result = $this->getTables($database);

        return $result;
    }

    protected function getTables($database)
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM information_schema.tables
             WHERE table_schema = :database
             ORDER BY table_name ASC"
        );
        $stmt->execute([':database' => $database]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $table = $row['TABLE_NAME'];
            $result[$table] = [
                'columns' => $this->getColumns($database, $table),
            ];
        }

        return $result;
    }

    protected function getRows($database, $table)
    {
        $stmt = $this->conn->prepare(sprintf(
            'SELECT * from %s.%s ORDER BY id ASC',
            $database,
            $table
        ));
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $id = $row['id'];
            $result[$id] = $row;
        }

        return $result;
    }

    protected function getColumns($database, $table)
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM information_schema.columns
             WHERE table_schema = :database AND table_name = :table
             ORDER BY table_name ASC, ordinal_position ASC"
        );
        $stmt->execute([':database' => $database, ':table' => $table]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $column = $row['COLUMN_NAME'];
            $result[] = $column;
        }

        return $result;
    }
}
