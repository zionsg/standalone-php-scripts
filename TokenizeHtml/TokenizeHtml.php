<?php
/**
 * Tokenize HTML content into words and tags
 *
 * Usage:
 *     $instance = new TokenizeHtml();
 *     $html = '';
 *     print_r($instance($html));
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/Tokenize
 * @since   2012-11-23T22:00+08:00
 */
class TokenizeHtml
{
    /**
     * __invoke
     *
     * @see    tokenize()
     * @param  string $text Text to tokenize
     * @return array Array of tokens
     */
    public function __invoke($text)
    {
        return $this->tokenize($text);
    }

    /**
     * Tokenize string into words and HTML tags
     *
     * This function does not handle line breaks at the moment
     * Assumes HTML is valid and that there are no misplaced or broken tags
     *
     * @param  string $text    Text to tokenize
     * @param  string $pattern Not for user to specify. For use in internal recursion.
     *                         Regex pattern to use
     * @param  int    $level   Not for user to specify. For debugging use in internal recursion.
     *                         Level of recursion
     * @return array Array of tokens
     */
    protected function tokenize($text, $pattern = null, $level = 0)
    {
        $combinedPattern = '~^([^<]*)(<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>)(.*)(</\3>)(.*)$~'; // recursion on all groups
        $wordPattern = '/([^ ]*)( .*)/'; // recursion on all groups
        $patternSkipGroups = array( // skip these groups as they are used for backreference only
            $combinedPattern => array(3), $wordPattern => array(),
        );
        $patternBaseGroups = array( // do not apply recursion to these groups
            $combinedPattern => array(2, 5), $wordPattern => array(),
        );
        $parts = array();

        $text = trim($text);
        if (empty($text)) {
            return array();
        }

        // Test combined pattern first - most exact pattern
        if (empty($pattern)) {
            $pattern = $combinedPattern;
        }
        $skipGroups = $patternSkipGroups[$pattern];
        $baseGroups = $patternBaseGroups[$pattern];

        $matches = array();

        if (preg_match($pattern, $text, $matches)) { // pattern matches
            $count = count($matches);
            for ($i = 1; $i < $count; $i++) {
                // skip group as it is used for backreference only
                if (in_array($i, $skipGroups)) {
                    continue;
                }

                $matchText = trim($matches[$i]);
                if (empty($matchText)) {
                    continue;
                }

                // exact match or groups which need no recursion
                if ($matchText == $text || in_array($i, $baseGroups)) {
                    $parts[] = $matchText;
                    continue;
                }

                $result = $this->tokenize($matchText, null, $level + 1);
                if (empty($result)) {
                    continue;
                }
                if (is_array($result)) {
                    foreach ($result as $part) {
                        $parts[] = $part;
                    }
                } else {
                    $parts[] = $result;
                }
            } // end for $i

            return $parts;
        } // end if pattern matches

        // Try the other patterns only if previous patterns were tested, else will
        // have infinite recursion

        // Test word pattern next if combined pattern has no matches in this
        // iteration
        if ($pattern == $combinedPattern) {
            // Test word pattern last as it is the most general
            $result = $this->tokenize($text, $wordPattern, $level);
            if (!empty($result)) {
                return $result;
            }
        }

        // Base case: No pattern matches - return text
        return $text;

    } // end function tokenize

} // end class