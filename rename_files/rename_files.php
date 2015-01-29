<?php
/**
 * Simple script to rename files
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/rename_files
 * @since  2015-01-29T08:00+08:00
 */

/**
 * Rename files in folder
 *
 * Subfolders are not processed.
 *
 * @param  string   $folder         Absolute path
 * @param  callable $renameCallback Optional additional callback applied after in-built renaming.
 *                                  Takes in (folder, oldFilename, newFilename) and returns new filename.
 * @return void
 */
function renameFiles($folder, $renameCallback = null)
{
    echo "<b>Renaming files in {$folder}</b><br><br>";

    foreach (scandir($folder) as $filename) {
        if (is_dir("{$folder}/{$filename}")) {
            continue;
        }

        $newFilename = preg_replace('/[^a-z0-9_\-\.]/', '', strtolower($filename));
        if (is_callable($renameCallback)) {
            $newFilename = $renameCallback($folder, $filename, $newFilename);
        }
        $result = rename("{$folder}/{$filename}", "{$folder}/{$newFilename}");

        printf(
            '%s: %s => %s<br>',
            ($result ? true : false),
            $filename,
            $newFilename
        );
    }

    echo '<br><b>Done!</b>';
}

/**
 * Returns callback to rename image files with numbering
 *
 * @example "2015jan-1.jpg" becomes "001.jpg"
 * @param   string   $prefix Prefix to add to filename after renaming
 * @return  callable
 */
function renameImageWithNumbering($prefix)
{
    return function ($folder, $filename, $newFilename) use ($prefix) {
        if (preg_match('/[^0-9]*([0-9]+)(\.[a-zA-Z]+)$/', $newFilename, $matches)) {
            $num = str_pad($matches[1], 3, '0', STR_PAD_LEFT);
            $ext = $matches[2];
            $newFilename = $prefix . $num . $ext;
        }

        return $newFilename;
    };
}
