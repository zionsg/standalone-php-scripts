<?php
/**
 * Runs PHPUnit command in browser using phpunit.xml for configuration
 *
 * Useful if there is no access to commandline, eg. restricted permissions
 * Assuming this file and phpunit.xml are in <webroot>/test, just type this in the browser:
 *     http://localhost/test/phpunit_browser.php
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [PHPUnit] https://github.com/sebastianbergmann/phpunit/
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/phpunit_browser
 * @since  2012-12-06T19:00+08:00
 */

// Assumes PHPUnit has been installed via Composer and is in current folder or include path
require_once 'PHPUnit/vendor/autoload.php';
$command = new PHPUnit_TextUI_Command;
?>
<pre>
<?php $command->run(array(), true); // capture output in <pre> tags ?>
</pre>
