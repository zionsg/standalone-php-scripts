<?php
/**
 * Output a 1-pixel transparent GIF image
 *
 * The Base64-encoded contents of the image is stored here instead of reading the image file every time.
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/pixel
 * @since  2015-03-27T14:45+08:00
 */

header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
exit;
