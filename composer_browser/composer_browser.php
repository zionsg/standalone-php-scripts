<?php
/**
 * Simple script to run Composer install command in browser
 *
 * Useful if there is no access to commandline, eg. restricted permissions
 * Assuming this file, composer.phar, and composer.json are in <webroot>/getcomposer,
 * just type this in the browser:
 *     http://localhost/getcomposer/composer_browser.php
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Composer] https://getcomposer.org
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/composer_browser
 * @since  2012-12-07T00:40+08:00
 */

set_time_limit(0);
ini_set('max_execution_time', 0);
$_SERVER['argv'] = array('composer.phar', 'install');
?>
<pre>
<?php include 'composer.phar'; ?>
</pre>