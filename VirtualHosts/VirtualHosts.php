<?php
/**
 * Generate list and config for port-based virtual hosts on local development machine
 *
 * The developer may not always have the permissions to edit the Windows
 * HOSTS file when creating virtual hosts. Since 127.0.0.1 is defined in the
 * HOSTS file by default, one alternative is to create port-based
 * virtual hosts instead of name-based virtual hosts
 *
 * This class maps all the top-level directories in the current directory
 * as port-based virtual hosts, generating the links and Apache config.
 *
 * Usage:
 *     $instance = new VirtualHosts();
 *     $params = array();
 *     print_r($instance($params));
 *
 * Note: Some ports are listed as unsafe and blocked in Chrome.
 *       List of ports - http://www-archive.mozilla.org/projects/netlib/PortBanning.html#portlist
 *       How to unblock in Chrome - http://douglastarr.com/how-to-allow-unsafe-ports-in-chrome/
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/VirtualHosts
 * @since  2012-11-14T23:50+08:00 zion.ng
 */
class VirtualHosts
{
    /**
     * __invoke
     *
     * @param  array $params Key-value pairs with the following keys
     *         'filterFunction' callback Callback to filter directories.
     *                                   Takes in folder name and returns true
     *                                   if passed, false if failed
     *         'currentDir'     string   Default = '.'. Directory to scan
     *         'extraDirs'      array    Extra directories to add to those from the scan for generating of links/config
     *         'path'           string   Default = '${path}'. Path for Apache config
     *         'server'         string   Default = '127.0.0.1'
     *         'webFolder'      string   Default = 'public_html'. Publishing folder for each domain, eg: www, htdocs
     * @return array Eg.: array(
     *                        'a.com' => array('uri' => '127.0.0.1:1234', 'duplicatePort' => false, 'config' => '...'),
     *                        'b.net' => array('uri' => '127.0.0.1:5678', 'duplicatePort' => false, 'config' => '...'),
     *                    )
     */
    public function __invoke(array $params = array())
    {
        extract(array_merge(
            array(
                'filterFunction' => null,
                'currentDir' => '.',
                'extraDirs'  => array(),
                'path'       => '${path}',
                'server'     => '127.0.0.1',
                'webFolder'  => 'public_html',
            ),
            $params
        ));
        $hasFilter = ($filterFunction && is_callable($filterFunction));

        // Scan current directory
        $dirs = array();
        foreach (scandir($currentDir) as $filename) {
            if ('.' == $filename || '..' == $filename || is_file($filename)) {
                continue;
            }
            if ($hasFilter && !$filterFunction($filename)) {
                continue;
            }
            $dirs[] = $filename;
        }
        $dirs = array_merge($dirs, $extraDirs);

        // Compute links and Apache config for each directory
        $hosts = array();
        $ports = array(); // for checking of duplicate ports
        foreach ($dirs as $dir) {
            $port = 0;
            $pos = 0;
            foreach (str_split($dir) as $char) {
                $port += (++$pos) * ord($char);
            }
            $isDuplicate = in_array($port, $ports);
            $ports[] = $port;

            $uri = "{$server}:{$port}";
            $config = sprintf(
                  "# %s\n"
                . "Listen %s:%d\n"
                . "NameVirtualHost %s:%d\n"
                . "<VirtualHost %s:%d>\n"
                . "  DocumentRoot \"%s\"\n"
                . "</VirtualHost>\n\n",
                $dir,
                $server, $port,
                $server, $port,
                $server, $port,
                $path . '/' . $dir . '/' . $webFolder
            );

            $hosts[$dir] = array(
                'uri' => $uri,
                'duplicatePort' => $isDuplicate,
                'config' => $config,
            );
        }

        return $hosts;
    }
}
