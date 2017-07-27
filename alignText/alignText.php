<?php
/**
 * Align text in columns
 *
 * @link   https://github.com/zionsg/standalone-php-scripts/tree/master/alignText
 * @param  string $text
 * @param  string $columnDelimiter
 * @return string
 */
function alignText($text, $columnDelimiter = '|')
{
    $rows = explode("\n", $text);
    $maxWidthForColumns = [];

    // Calculate max width per column
    foreach ($rows as $row) {
        $cols = array_map('trim', explode($columnDelimiter, $row));
        $colCnt = count($cols);

        for ($i = 0; $i < $colCnt; $i++) {
            $col = $cols[$i];
            $maxWidthForColumns[$i] = max(strlen($col), $maxWidthForColumns[$i] ?? 0);
        }
    }

    // Generate aligned text
    $result = '';
    foreach ($rows as $row) {
        $cols = explode($columnDelimiter, $row);
        array_walk($cols, function (&$val, $key) use ($maxWidthForColumns) {
            $val = str_pad(trim($val), $maxWidthForColumns[$key] ?? 0, ' ', STR_PAD_RIGHT);
        });

        $result .= implode(" {$columnDelimiter} ", $cols) . "\n";
    }

    return $result;
}
