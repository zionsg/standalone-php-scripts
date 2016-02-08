<?php
/**
 * Split large SQL database dump file into parts
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/split_sql_file
 * @since  2016-02-07T23:00+08:00
 */

$filename = 'D:\large_2GB_database.sql'; // change filename

set_time_limit(0);
splitSqlFile($filename);

/**
 * Split SQL file into parts
 *
 * Lines are written to part files as they are read instead of accumulating in a variable before writing
 * in case there are too many lines and memory is exhausted.
 *
 * Part files will be stored in 'parts' subfolder in the same folder as this script with filenames output to browser.
 *
 * @param  string $filename Path to SQL file
 * @return void
 */
function splitSqlFile($filename)
{
    // Variables
    $handle = fopen($filename, 'rb');
    $baseFilename = basename($filename);
    $partFolder = __DIR__ . '/parts';
    $padLen = 5;
    $partCnt = 0;
    $getPartFilename = function ($partCnt) use ($baseFilename, $padLen) {
        return 'part' . str_pad($partCnt, $padLen, '0', STR_PAD_LEFT) . '_' . $baseFilename;
    };

    // Checks
    if (false === $handle) {
        throw new Exception("{$filename} could not be opened");
    }
    if (!file_exists($partFolder)) {
        mkdir($partFolder);
    }
    if (!is_writable($partFolder)) {
        throw new Exception("Could not create or write to {$partFolder}");
    }

    // Each part will consist of conditional-execution tokens, table schema or dump
    while (!feof($handle)) {
        $partFilename = $getPartFilename(++$partCnt);
        $partHandle = fopen($partFolder . '/' . $partFilename, 'wb');
        $breakOnBlankLine = false;
        while (!feof($handle)) {
            $line = fgets($handle);
            fwrite($partHandle, $line);

            // Take only the first 10 chars and not the whole line else exhaust memory
            $segment = trim(substr($line, 0, 10));
            $isBlankLine = ('' === $segment);
            $isComment = ('--' == substr($segment, 0, 2));
            if ($breakOnBlankLine && $isBlankLine) {
                break;
            }

            $breakOnBlankLine = (!$isBlankLine && !$isComment);
        }
        fclose($partHandle);
        echo $partFilename . '<br>';
    }

    fclose($handle);
    echo "<br><b>SQL file {$filename} split into {$partCnt} parts.</b><br><br>";
}
