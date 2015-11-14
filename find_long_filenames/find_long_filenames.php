<?php
/**
 * Find long filenames in path
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/find_long_filenames
 * @since  2015-01-29T08:00+08:00
 */

$path = 'D:\example'; // change path
$limit = 250; // reduce if planning to copy files to new parent folder

set_time_limit(0);
$result = findLongFilenames($path, $limit);

printf('Found %d filenames exceeding %d characters in %s<br />', count($result), $limit, $path);
echo '<pre>';
foreach ($result as $filename) {
    echo $filename . ' (' . strlen($filename) . ")\n";
}
echo '</pre>';

/**
 * Go into path and its subfolders and return paths that are too long
 *
 * @param  string $path
 * @param  int    $limit  Default = 250. Max filename for Windows including
 *                        folders in path is 255
 * @param  array  $result Not specified by user - used in recursion
 * @return array  Filenames that exceed limit
 */
function findLongFilenames($path, $limit = 250, $result = array())
{
    $fn = __FUNCTION__;

    foreach (scandir($path) as $file) {
        if ('.' == $file || '..' == $file) {
            continue;
        }

        $filePath = $path . '/' . $file;

        if (is_dir($filePath)) {
            $result = $fn($filePath, $limit, $result);
            continue;
        }

        if (strlen($filePath) > $limit) {
            $result[] = $filePath;
        }
    }

    return $result;
}
