<?php
/**
 * Reduce lists in an array to a subset (usually 1 element) each recursively
 *
 * @link    https://github.com/zionsg/standalone-php-scripts/tree/master/array_reduce_lists_recursive
 * @example ['a' => 1, 'list' => ['alpha', 'beta']] becomes ['a' => 1, 'list' => ['alpha']]
 * @param   array     $arr
 * @param   int|int[] $reduceListsTo Number of elements to reduce each list to. An array of numbers can be provided
 *                                   to specify different reductions per nesting level. If the array is smaller than
 *                                   the no. of nesting levels, the last number is used for subsequent levels.
 * @return  array
 */
function array_reduce_lists_recursive(array $arr, $reduceListsTo = 1)
{
    $fn = __FUNCTION__;
    $result = [];

    // Normalize reduction values
    $reduceListsTo = $reduceListsTo ?: [1];
    if (! is_array($reduceListsTo)) {
        $reduceListsTo = [$reduceListsTo];
    }

    // Compute reduction value for current nesting level
    $listCnt = count($reduceListsTo);
    $reduceTo = (int) $reduceListsTo[0] ?: 1; // reduce, not delete, hence min value of 1, not 0
    $nextReduceListsTo = (1 === $listCnt) ? $reduceListsTo : array_splice($reduceListsTo, 1);

    // If numerically sequential array, ie. list of items, process subset. Not reduced in loop as $arr may be plain list
    if (array_values($arr) === $arr) {
        $arr = array_slice($arr, 0, $reduceTo);
    }

    // Iterate thru keys
    foreach ($arr as $key => $value) {
        if (! is_array($value)) {
            $result[$key] = $value;
            continue;
        }

        $result[$key] = $fn($value, $nextReduceListsTo);
    }

    return $result;
}
