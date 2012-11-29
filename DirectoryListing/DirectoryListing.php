<?php
/**
 * Retrieve directory listing with folder/file count and size info
 *
 * Usage:
 *     $instance = new DirectoryListing('D:/myfolder');
 *     echo $instance();
 *
 * Result can be formatted with CSS:
 *     <div class="directoryListing">
 *       <span class="folder level0">...</span>
 *         <span class="file level0">...</span>
 *         <span class="folder level1">...</span>
 *           <span class="file level1">...</span>
 *     </div>
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/DirectoryListing
 * @since   2012-11-10T09:30+08:00
 */
class DirectoryListing
{
    /**
     * Directory path to list
     * @var string
     */
    protected $path = '';

    /**
     * Listing of subfolders and files with file count and size info
     * @var array
     */
    protected $listing = array();

    /**
     * Constructor
     *
     * Takes in directory path to list and check if it exists and is readable
     *
     * @param  string $path Directory path to list
     * @throws InvalidArgumentException Thrown if directory does not exist or
     *                                  is not readable
     */
    public function __construct($path)
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException(
                "Path {$path} does not exist or is not readable"
            );
        }

        $this->path = $path;
    }

    /**
     * __invoke
     *
     * Retrieves directory listing and formats it
     *
     * @param  null|callback $filterCallback Callback function to filter folders
     *                                       and files. Takes in path as a string
     *                                       and returns true for passed, false
     *                                       for failed
     * @return string
     */
    public function __invoke($filterCallback = null)
    {
        return '<div class="directoryListing">' . PHP_EOL
             . $this->formatListing($this->getListing($this->path, $filterCallback))
             . '</div>' . PHP_EOL;
    }

    /**
     * Format bytes to human-readable form
     *
     * The final numeric value should be less than 1024 and more than 0
     * when shown with the final unit and up to 2 decimal places
     *
     * @param int     $bytes            Value in bytes
     * @param string  $byteString       DEFAULT='B'. String to use to denote "bytes"
     * @param boolean $returnMultiplier DEFAULT=false. Whether to return multiplier only
     */
    protected function formatBytes($bytes, $byteString = 'B', $returnMultiplier = false)
    {
        $prefix = array('', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi');
        $bytes = (int) $bytes;

        $multiplier = 1;
        foreach ($prefix as $key => $value) {
            if ($bytes < 1024) {
                return (  $returnMultiplier
                        ? $multiplier
                        : sprintf('%.2f %s%s', $bytes, $value, $byteString));
            }
            $bytes /= 1024;
            $multiplier *= 1024;
        }

        return (  $returnMultiplier
                ? $multiplier
                : sprintf('%.2f %s%s', $bytes * 1024, end($this->_prefix), $byteString));
    }

    /**
     * Recursively format directory listing
     *
     * @param  array  $info   Directory info from getListing()
     * @param  int    $indent DEFAULT=0. Indent level
     * @param  int    $level  DEFAULT=0. Directory level
     * @return string
     */
    protected function formatListing($info, $indent = 0, $level = 0)
    {
        $output = '';
        if ($level == 0) {
            $output = 'Directory Listing for ' . $info['path'] . '<br /><br />' . PHP_EOL
                    . 'Total folders filtered out: ' . $info['totalFolderFiltered'] . '<br />' . PHP_EOL
                    . 'Total files filtered out (excluding those in filtered folders): '
                    . $info['totalFileFiltered'] . '<br /><br />' . PHP_EOL
                    . '* Type * Path * Total Size (Bytes) * Total Size (Human-Readable) '
                    . '* Folders * Files * Total Nested Folders * Total Nested Files *'
                    . '<br /><br />' . PHP_EOL;
        }
        $levelClass   = 'level' . $level;
        $folderFormat = "%s<span class=\"folder {$levelClass}\">"
                      . '* %s * %s * %s * %s * Folders: %d * Files: %d * '
                      . 'Total nested folders: %d * Total nested files: %d *'
                      . '</span><br />' . PHP_EOL;
        $fileFormat   = "%s<span class=\"file {$levelClass}\">"
                      . '* %s * %s * %s * %s *'
                      . '</span><br />' . PHP_EOL;

        // Current folder info
        $output .= sprintf(
            $folderFormat,
            str_repeat('&nbsp;', $indent),
            'FOLDER', ($level == 0 ? $info['path'] : $info['name']),
            $info['totalSize'] . ' bytes', $this->formatBytes($info['totalSize']),
            $info['folderCount'], $info['fileCount'],
            $info['totalFolderCount'], $info['totalFileCount']
        );

        // Show files in current folder first
        foreach ($info['files'] as $filename => $size) {
            $output .= sprintf(
                $fileFormat,
                str_repeat('&nbsp;', $indent + 2),
                'File', $filename,
                $size . ' bytes', $this->formatBytes($size)
            );
        }

        // Recursively format subfolders
        foreach ($info['folders'] as $folderInfo) {
            $output .= $this->formatListing($folderInfo, $indent + 2, $level + 1);
        }

        return $output;
    }

    /**
     * Recursive function to list directory contents, count subdirs and files
     *
     * @param  string $currDir Current directory
     * @param  null|callback $filterCallback Callback function to filter folders
     *                                       and files. Takes in path as a string
     *                                       and returns true for passed, false
     *                                       for failed
     * @return array
     * @throws InvalidArgumentException When $filterCallback is not callable
     */
    protected function getListing($currDir, $filterCallback = null)
    {
        // Type checking for $filterCallback
        if ($filterCallback && !is_callable($filterCallback)) {
            throw new InvalidArgumentException('Invalid filter callback provided');
        }

        $info = array( // array for holding info
            'path'        => $currDir, // current path
            'name'        => basename($currDir),
            'folderCount' => 0,      // subfolder count (no nesting) in current folder
            'fileCount'   => 0,      // file count (no nesting) in current folder
            'totalFolderCount' => 0, // total nested subfolders
            'totalFileCount'   => 0, // total nested files in folder and subfolders
            'totalFolderFiltered' => 0, // total no. of nested folders filtered
            'totalFileFiltered'   => 0, // total no. of nested files filtered
            'totalSize' => 0, // total size of folder including nesting
            'folders'   => array(),    // store descriptions of subfolders and their files
            'files'     => array(),    // store descriptions of files in $currDir
        );

        foreach ((scandir($currDir) ?: array()) as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }

            $filePath = $currDir . '/' . $filename;

            if (is_file($filePath)) { // file
                if ($filterCallback && !$filterCallback($filePath)) {
                    $info['totalFileFiltered']++;
                    continue;
                }
                $fileSize = filesize($filePath);
                $info['files'][$filename] = $fileSize;
                $info['fileCount']++;
                $info['totalFileCount']++;
                $info['totalSize'] += $fileSize;
            } else if (is_dir($filePath)) { // subfolder
                if ($filterCallback && !$filterCallback($filePath)) {
                    $info['totalFolderFiltered']++;
                    continue;
                }
                $subfolderInfo = $this->getListing($filePath, $filterCallback);
                $info['folders'][$filename] = $subfolderInfo;
                $info['folderCount']++;
                $info['totalFolderCount']++;
                $info['totalFolderCount'] += $subfolderInfo['totalFolderCount'];
                $info['totalFileCount']   += $subfolderInfo['totalFileCount'];
                $info['totalFolderFiltered'] += $subfolderInfo['totalFolderFiltered'];
                $info['totalFileFiltered'] += $subfolderInfo['totalFileFiltered'];
                $info['totalSize'] += $subfolderInfo['totalSize'];
            }
        } // end foreach $currDir

        return $info;
    } //end function getListing

}
