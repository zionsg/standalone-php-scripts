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
    $rows = [];
    $maxWidthForColumns = [];

    // Calculate max width per column
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        $cols = array_map('trim', explode($columnDelimiter, $line));
        $colCnt = count($cols);
        $rows[] = $cols;

        foreach ($cols as $i => $col) {
            $maxWidthForColumns[$i] = max(strlen($col), $maxWidthForColumns[$i] ?? 0);
        }
    }

    // Generate aligned text
    $result = [];
    foreach ($rows as $row) {
        array_walk($row, function (&$val, $key) use ($maxWidthForColumns) {
            $val = ('' === $val ? '' : str_pad($val, $maxWidthForColumns[$key] ?? 0, ' ', STR_PAD_RIGHT));
        });

        $result[] = implode(" {$columnDelimiter} ", $row);
    }

    return implode("\n", $result);
}
