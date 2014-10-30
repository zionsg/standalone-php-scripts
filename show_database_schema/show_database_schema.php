<?php
/**
 * Simple script to show database schema
 *
 * Just change the database credentials and adjust $cells or layout if need be.
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/show_database_schema
 * @since  2014-10-18T13:00+08:00
 */

// Database credentials
$user     = 'root';
$password = '';
$host     = 'localhost';
$database = '';

// Labels and properties to display
// @see getValue() on processing of properties
$cells = array(
    'S/N' => '',
    'Column' => 'COLUMN_NAME',
    'Type' => function ($column) {
        if ('text' == substr($column->DATA_TYPE, -4)) {
            return "{$column->DATA_TYPE}({$column->CHARACTER_MAXIMUM_LENGTH})";
        }
        return ($column->COLUMN_TYPE ?: $column->DATA_TYPE);
    },
    'Key' => function ($column) {
        return str_replace(array('PRI', 'UNI', 'MUL'), array('Primary', 'Unique', 'Index'), $column->COLUMN_KEY);
    },
    'Default' => function($column) {
        return ('YES' == $column->IS_NULLABLE && null === $column->COLUMN_DEFAULT ? 'null' : $column->COLUMN_DEFAULT);
    },
    'Extra' => 'EXTRA',
    'Comments' => 'COLUMN_COMMENT',
);

// Get database info
if (($tables = getTableColumns($host, $database, $user, $password)) === false) {
    echo 'There are no tables in the database.';
    exit;
}
?>

<style>
  table { border-collapse: collapse; }
  th { background-color: #ccc; }
  th, td {
    border: 1px solid #333;
    padding: 2px 4px;
  }
</style>

<div align="center"><b>Database: <?php echo $database; ?></b><br><i>(<?php echo count($tables); ?> tables)</i></div>

<?php foreach ($tables as $table => $schema): ?>
  <?php
  $info = $schema['info'];
  $columns = $schema['columns'];
  ?>
  <div>
    <b>Table: <?php echo $table; ?></b><br>
    <?php echo ($info->TABLE_COMMENT ? $info->TABLE_COMMENT . '<br>' : ''); ?>
    <i>(<?php echo count($columns); ?> columns)</i>
  </div>
  <?php
  if (!$columns) {
      continue;
  }
  $cnt = 0;
  ?>

  <table>
    <tr>
      <?php foreach ($cells as $label => $property): ?>
        <th><?php echo $label; ?></th>
      <?php endforeach; ?>
    </tr>
    <?php foreach ($columns as $columnName => $column): ?>
      <tr>
        <?php foreach ($cells as $label => $property): ?>
          <td><?php echo ('S/N' == $label ? ++$cnt : getValue($column, $property)); ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
  </table>
  <br><br>
<?php endforeach; ?>


<?php
/**
 * Get value from column corresponding to property
 *
 * If property is string, return column property.
 * If property is callable, pass $column and get value from it.
 * If property is array, get the first non-empty value.
 *
 * @param  object $column
 * @param  string|callable|array $property
 * @return string
 */
function getValue($column, $property)
{
    $value = '';

    if (is_string($property)) {
        $value = (isset($column->$property) ? $column->$property : '');
    } elseif (is_callable($property)) {
        $value = $property($column);
    } elseif (is_array($property)) {
        foreach ($property as $subproperty) {
            $value = getValue($column, $subproperty);
            if ($value) {
                break;
            }
        }
    }

    return $value;
}

/**
 * Return array of tables containing table info and array of column info
 *
 * @param  string $host
 * @param  string $database
 * @param  string $user
 * @param  string $password
 * @return array  array(
 *                    <table1> => array(
 *                        'info' => <object with table info>,
 *                        'columns' => array(<column1> => <object with info as properties>, ...),
 *                    ),
 *                    ...
 *                )
 */
function getTableColumns($host, $database, $user, $password)
{
    $tables = array();

    // Get db connection
    try {
        $db = new PDO(
            "mysql:host={$host};dbname={$database}",
            $user,
            $password,
            array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            )
        );
    } catch (PDOException $e) {
        echo $e->getMessage();
        return false;
    }

    // Query db for table info
    $stmt = $db->prepare(
        "SELECT *
         FROM information_schema.tables
         WHERE table_schema = :database
         ORDER BY table_name ASC"
    );
    $stmt->execute(array(':database' => $database));
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $tables[$row->TABLE_NAME]['info'] = $row;
    }

    // Query db for column info
    $stmt = $db->prepare(
        "SELECT *
         FROM information_schema.columns
         WHERE table_schema = :database
         ORDER BY table_name ASC, ordinal_position ASC"
    );
    $stmt->execute(array(':database' => $database));
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $tables[$row->TABLE_NAME]['columns'][$row->COLUMN_NAME] = $row;
    }

    // Close db connection
    $db = null;

    // Return tables
    return $tables;
}
?>
