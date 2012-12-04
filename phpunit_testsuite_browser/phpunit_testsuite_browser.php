<?php
/**
 * Simple script to run PHPUnit test suite in browser using tests in current folder
 *
 * Useful if there is no access to command shell, eg. restricted permissions
 * Assuming this file and the tests are in <webroot>/test, just type this in the browser:
 *     http://localhost/test/phpunit_browser.php
 *
 * The script identifies a test as ending in 'Test.php'
 *
 * @link   (Original idea from comment) http://sebastian-bergmann.de/archives/638-PHPUnit-3.0.html#c5048
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/phpunit_testsuite_browser
 * @since  2012-12-04T13:30+08:00
 */
require_once 'PHPUnit/vendor/autoload.php'; // assumes PHPUnit library is in include path

// Test suite
$suite = new PHPUnit_Framework_TestSuite();

// Add test suites
$testFilePattern = 'Test.php';
$testFilePatternLen = strlen($testFilePattern);
foreach (scandir('.') as $file) {
    if ('.' == $file || '..' == $file) {
        continue;
    }

    if (is_file($file) && $testFilePattern == substr($file, -$testFilePatternLen)) {
        include $file;
        $suite->addTestSuite(basename($file, '.php'));
    }
}
?>
<pre>
  <?php PHPUnit_TextUI_TestRunner::run($suite); // output result in <pre> tags ?>
</pre>
