<?php
/**
 * Generate list and config for port-based virtual hosts on local development machine
 *
 * The developer may not always have the permissions to edit the Windows
 * HOSTS file when creating virtual hosts. Since 127.0.0.1 is defined in the
 * HOSTS file by default, one alternative is to create port-based
 * virtual hosts instead of name-based virtual hosts
 *
 * This class maps all the top-level directories in the server web root
 * (eg: D:\EasyPHP\www) as port-based virtual hosts, generating the links and
 * Apache config
 *
 * Usage:
 *     $instance = new VirtualHosts();
 *     $params = array();
 *     echo $instance($params);
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
     *                                  Takes in folder name and returns true
     *                                  if passed, false if failed
     *         'path'           string  DEFAULT='${path}'
     *         'scheme'         string  DEFAULT='http'
     *         'server'         string  DEFAULT='127.0.0.1'
     *         'showConfig'     string  DEFAULT=true. Whether to show
     *                                  config to use for Apache
     *         'webFolder'      string  DEFAULT='www'. Eg: public_html, htdocs
     * @return string
     */
    public function __invoke(array $params = array())
    {
        extract(array_merge(
            array(
                'filterFunction' => null,
                'path' => '${path}',
                'scheme' => 'http',
                'server' => '127.0.0.1',
                'showConfig' => true,
                'webFolder' => 'www',
            ),
            $params
        ));

        $hosts = array();
        foreach (scandir('.') as $filename) {
            if ('.' == $filename || '..' == $filename || is_file($filename)) {
                continue;
            }

            if ($filterFunction && is_callable($filterFunction)) {
                if (!$filterFunction($filename)) {
                    continue;
                }
            }

            $port = 0;
            $pos = 0;
            foreach (str_split($filename) as $char) {
                $port += (++$pos) * ord($char);
            }

            $hosts[$filename] = $port;
        }

        // Print out hosts as <ul>
        if (!empty($hosts)) {
            $ports = array();
            $output = '<ul>' . PHP_EOL;
            foreach ($hosts as $website => $port) {
                $output .= sprintf(
                    '  <li><a href="%s%s:%d">%s</a> (%s:%d)%s</li>' . PHP_EOL,
                    (empty($scheme) ? '' : $scheme . '://'),
                    $server, $port,
                    $website,
                    $server, $port,
                    (in_array($port, $ports) ? ' *DUPLICATE*' : '')
                );
            }
            $output .= '</ul>' . PHP_EOL;

            // Show config for Apache httpd.conf
            if ($showConfig) {
                $output .= '[CONFIG FOR APACHE HTTPD.CONF]<br />' . PHP_EOL;

                foreach ($hosts as $website => $port) {
                    $hostConfig = sprintf(
                        '# %s' . PHP_EOL
                        . 'Listen %s:%d' . PHP_EOL
                        . 'NameVirtualHost %s:%d' . PHP_EOL
                        . '<VirtualHost %s:%d>' . PHP_EOL
                        . '  DocumentRoot "%s"' . PHP_EOL
                        . '</VirtualHost>' . PHP_EOL . PHP_EOL,
                        $website,
                        $server, $port,
                        $server, $port,
                        $server, $port,
                        $path . DIRECTORY_SEPARATOR . $website . DIRECTORY_SEPARATOR . $webFolder
                    );
                    $output .= nl2br(htmlspecialchars($hostConfig));
                }
            }
        }

        return $output;
    } // end function __invoke

}
