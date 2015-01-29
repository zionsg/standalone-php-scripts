<?php
/**
 * Simple script to rename files for uploading to web server
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/rename_files
 * @since  2015-01-29T08:00+08:00
 */

// Change folder and prefix to run
renameFiles('', renameImageWithNumbering(''));

/**
 * Rename files in folder
 *
 * Subfolders are not processed.
 * Filename is changed to lowercase, retaining only the following characters: alphanumeric, dash, underscore, period.
 *
 * @param  string   $folder         Absolute path
 * @param  callable $renameCallback Optional additional callback applied after in-built renaming.
 *                                  Takes in (folder, oldFilename, newFilename) and returns new filename.
 * @return void
 */
function renameFiles($folder, $renameCallback = null)
{
    if (!$folder || !file_exists($folder)) {
        echo "Folder {$folder} does not exist";
        return;
    }

    echo "<b>Renaming files in {$folder}</b><br><br>";

    $totalCnt = 0;
    $successCnt = 0;
    foreach (scandir($folder) as $filename) {
        if (is_dir("{$folder}/{$filename}")) {
            continue;
        }

        $newFilename = preg_replace('/[^a-z0-9_\-\.]/', '', strtolower($filename));
        if (is_callable($renameCallback)) {
            $newFilename = $renameCallback($folder, $filename, $newFilename);
        }
        $result = rename("{$folder}/{$filename}", "{$folder}/{$newFilename}");

        $totalCnt++;
        $successCnt += ($result ? 1 : 0);
        printf(
            '%d) %s%s => %s<br>',
            $totalCnt,
            ($result ? '' : 'ERROR renaming '),
            $filename,
            $newFilename
        );
    }

    echo "<br><b>Completed - {$successCnt}/{$totalCnt} files renamed</b>";
}

/**
 * Returns callback to rename image files with numbering
 *
 * @example "2015jan-1.jpg"  with "test" as prefix becomes "test001.jpg"
 * @example "2015jan-1a.jpg" with "test" as prefix becomes "test001a.jpg"
 * @param   string   $prefix Optional prefix to prepend to filename after renaming
 * @return  callable
 */
function renameImageWithNumbering($prefix = '')
{
    return function ($folder, $filename, $newFilename) use ($prefix) {
        if (preg_match('/[^0-9]*([0-9]+)([^0-9\.]*)(\.[a-zA-Z]+)$/', $newFilename, $matches)) {
            $num = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
            $suffix = $matches[2];
            $ext = $matches[3];
            $newFilename = $num . $suffix . $ext;
        }

        return $prefix . $newFilename;
    };
}
