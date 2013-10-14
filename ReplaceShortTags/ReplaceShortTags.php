<?php
/**
 * Replace short tags in PHP file
 *
 * Usage:
 *     $replacer = new ReplaceShortTags();
 *     $output = $replacer('/path/to/filename.php');
 *     echo '<pre>' . htmlspecialchars($output) . '</pre>';
 *     file_put_contents('/path/to/updatedFilename.php', $output);
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/ReplaceShortTags
 * @since   2013-10-14T10:30+08:00
 */
class ReplaceShortTags
{
    /**
     * __invoke
     *
     * Takes in filename and replaces PHP short tags.
     *
     * @param  string $filename
     * @throws Exception if filename does not exist or short_open_tag setting not enabled
     * @return string
     */
    public function __invoke($filename)
    {
        if (!file_exists($filename)) {
            throw new Exception('Filename does not exist');
        }

        $shortTagEnabled = ini_get('short_open_tag');
        if (empty($shortTagEnabled)) {
            throw new Exception('short_open_tag must be enabled for replacing of short tags to work');
        }

        $content = file_get_contents($filename);
        $tokens = token_get_all($content);
        $output = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($tokenIndex, $code, $line) = $token;
                switch ($tokenIndex) {
                    case T_OPEN_TAG_WITH_ECHO:
                        $output .= '<?php echo ';
                        break;
                    case T_OPEN_TAG:
                        $output .= '<?php ';
                        break;
                    default:
                        $output .= $code;
                        break;
                }
            }
            else {
                $output .= $token;
            }
        }

        return $output;
    }
}
