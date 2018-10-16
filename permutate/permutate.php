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
    "<pre>%.3f secs to compute %d permutations:\n\n%s</pre>",
    microtime(true) - $start,
    count($ans),
    implode("\n", $ans)
);

/**
 * Generate permutations of a fixed length given a list of characters
 *
 * @example permutate(range('a', 'b'), 2) will return ['aa', 'ab', 'ba', 'bb']
 * @param  array $chars
 * @param  int $length Length of each permutation
 * @return array
 */
function permutate(array $chars, $length) {
    $result = [];
    $charCount = count($chars);

    $permutations = (2 === $length) ? $chars : permutate($chars, $length - 1);
    for ($i = 0; $i < $charCount; $i++) {
        foreach ($permutations as $p) {
            $result[] = $chars[$i] . $p;
        }
    }

    return $result;
}
