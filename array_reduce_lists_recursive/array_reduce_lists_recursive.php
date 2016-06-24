<?php
/**
 * Reduce lists in an array to a subset (usually 1 element) each recursively
 *
 * @link    https://github.com/zionsg/standalone-php-scripts/tree/master/array_reduce_lists_recursive
 * @example ['a' => 1, 'list' => ['alpha', 'beta']] becomes ['a' => 1, 'list' => ['alpha']]
 * @param   array $arr
 * @param   int   $reduceListTo Optional number of elements to reduce each list to
 * @return  array
 */
function array_reduce_lists_recursive(array $arr, $reduceListTo = 1)
{
    $fn = __FUNCTION__;
    $result = [];

    foreach ($arr as $key => $value) {
        if (!is_array($value)) {
            $result[$key] = $value;
            continue;
        }

        if (array_values($value) === $value) {
            // If numerically sequential array, ie. list of items, process only a subset
            $result[$key] = $fn(array_slice($value, 0, $reduceListTo));
        } else {
            // Process full array
            $result[$key] = $fn($value);
        }
    }

    return $result;
}
