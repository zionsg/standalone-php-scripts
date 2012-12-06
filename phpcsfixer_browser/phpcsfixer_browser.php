<?php
/**
 * Simple script to run PHP-CS-Fixer command in browser
 *
 * Useful if there is no access to commandline, eg. restricted permissions
 * Assuming this file and the files to check are in <webroot>/check, just type this in the browser:
 *     http://localhost/check/phpcsfixer_browser.php
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [PHP-CS-Fixer] https://github.com/fabpot/PHP-CS-Fixer
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/phpcsfixer_browser
 * @since  2012-12-06T19:00+08:00
 */

// Assumes PHP-CS-Fixer has been installed via Composer and is in include path
require_once 'PHP-CS-Fixer/vendor/autoload.php';

use Symfony\CS\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

$application = new Application();
$input = new StringInput('fix --verbose --dry-run --level=all .');
$output = new StreamOutput(fopen('php://output', 'a'), StreamOutput::VERBOSITY_VERBOSE);
?>
<pre>
<?php $application->run($input, $output); ?>
</pre>
