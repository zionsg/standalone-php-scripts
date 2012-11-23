<?php
/**
 * Grep (UNIX command) all files for search text recursively in current directory
 *
 * Usage:
 *     $instance = new Grep();
 *     $params = array();
 *     echo $instance($params);
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/Grep
 * @since   2012-11-23T21:30+08:00
 */
class Grep
{
    /**
     * __invoke
     *
     * @params array $params Key-value pairs with the following keys:
     *         @key array   $exclude   Files to exclude. Can include wildcard, eg. *.jpg
     *         @key array   $include   Files to include. Can include wildcard, eg. *.ph*
     *         @key string  $pattern   Regular expression pattern to use for search
     *         @key boolean $recursive DEFAULT=true. Whether to search thru subdirectories
     *                                 recursively
     * @return string Output from 'grep' shell command sorted in ascending order
     * @throws RuntimeException         If 'grep' command is not available as a shell command
     * @throws InvalidArgumentException If $pattern is empty, or $exclude/$include are not arrays
     */
    public function __invoke(array $params = array())
    {
        // Check if 'grep' is available as a shell command
        $returnValue = shell_exec('grep');
        if (empty($returnValue)) {
            throw new RuntimeException("'grep' is not a valid shell command");
        }

        // Ensure all keys are set before extracting to prevent notices
        $params = array_merge(
            array(
                'exclude' => array(),
                'include' => array(),
                'pattern' => '',
                'recursive' => true,
            ),
            $params
        );
        extract($params);

        // Check parameters
        if (empty($pattern)) {
            throw new InvalidArgumentException("Parameter 'pattern' must be a non-empty string");
        }
        if (!is_array($exclude)) {
            throw new InvalidArgumentException("Parameter 'exclude' must be an array");
        }
        if (!is_array($include)) {
            throw new InvalidArgumentException("Parameter 'include' must be an array");
        }

        // Collate arguments
        $excludeArgs = '';
        foreach ($exclude as $file) {
            $excludeArgs .= '--exclude=' . $file . ' ';
        }

        $includeArgs = '';
        foreach ($include as $file) {
            $includeArgs .= '--include=' . $file . ' ';
        }

        // Run shell command
        $command = "grep -r -n {$excludeArgs} {$includeArgs} --regexp='{$pattern}' . | sort";
        $result = shell_exec($command);

        // Process results
        if (empty($result)) {
            $output = 'No matches found';
        } else {
            $output = '<pre>' . htmlspecialchars($result) . '</pre>';
        }

        return sprintf(
            "Searching for regex '%s' in <b>%s</b><br />"
            . 'Files excluded: %s<br />'
            . 'Files included: %s<br /><br />%s',
            $pattern,
            getcwd(),
            (empty($exclude) ? '0' : implode(', ', $exclude)),
            (empty($include) ? '0' : implode(', ', $include)),
            $output
        );
    } // end function __invoke

} // end class