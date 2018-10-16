<?php
/**
 * Simple script to generate permutations for a list of characters
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/permutate
 * @since  2018-10-16T12:00+08:00
 */

$start = microtime(true);
$ans = permutate(range('a', 'z'), 2);
printf(
    "<pre>%.6f secs to compute %d permutations:\n\n%s</pre>",
    microtime(true) - $start,
    count($ans),
    implode("\n", $ans)
);

/**
 * Generate permutations given a list of characters
 *
 * @example permutate(range('a', 'b'), 2) will return ['aa', 'ab', 'ba', 'bb']
 * @param array $chars
 * @param int $length Length of each permutation
 * @param array $prevResult Used internally by function to pass result of previous iteration.
 * @return array
 */
function permutate(array $chars, $length, array $prevResult = []) {
    if (0 === $length) {
        return $prevResult;
    }

    if (! $prevResult) {
        $result = $chars;
    } else {
        $result = [];

        foreach ($prevResult as $prev) {
            foreach ($chars as $char) {
                $result[] = $prev . $char;
            }
        }
    }

    return permutate($chars, $length - 1, $result);
}
