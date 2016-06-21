<?php
/**
 * Reduce lists in an array to 1 element each recursively
 *
 * @link    https://github.com/zionsg/standalone-php-scripts/tree/master/array_reduce_lists_recursive
 * @example ['a' => 1, 'list' => ['alpha', 'beta']] becomes ['a' => 1, 'list' => ['alpha']]
 * @param   array $arr
 * @return  array
 */
function array_reduce_lists_recursive(array $arr)
{
    $fn = __FUNCTION__;
    $result = [];

    foreach ($arr as $key => $value) {
        if (!is_array($value)) {
            $result[$key] = $value;
            continue;
        }

        // If numerically sequential array, ie. list of items, keep only the first item (if any)
        if (array_values($value) === $value) {
            $result[$key] = array_slice($value, 0, 1);
            continue;
        }

        // Recursive call for associative arrays
        $result[$key] = $fn($value);
    }

    return $result;
}
