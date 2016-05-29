<?php
/**
 * Compare keys of 2 arrays recursively in both directions
 *
 * @link   https://github.com/zionsg/standalone-php-scripts/tree/master/array_diff_key_recursive
 * @param  array  $arr1
 * @param  array  $arr2
 * @param  string $notInArray2Msg Message to use if key from $arr1 is not found in $arr2
 * @param  string $notInArray1Msg Message to use if key from $arr2 is not found in $arr1
 * @return array
 */
function array_diff_key_recursive(array $arr1, array $arr2, $notInArray2Msg = 'missing', $notInArray1Msg = 'extra')
{
    /**
     * Internal function used to compare keys of both arrays recursively in 1 direction
     *
     * @param  array  $arr1
     * @param  array  $arr2
     * @param  string $diffMsg Message to use if key from $arr1 is not found in $arr2
     * @return array
     */
    $compare_recursive = function (array $arr1, array $arr2, $diffMsg) use (&$compare_recursive) {
        $fn = $compare_recursive;
        $diff = [];

        foreach ($arr1 as $key => $value) {
            if (!array_key_exists($key, $arr2)) {
                $diff[$key] = $diffMsg;
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            // Both keys must be non-empty arrays before considering recursion
            $value2 = $arr2[$key];
            if (!is_array($value2)) {
                $diff[$key] = 'not an array';
                continue;
            }
            if (!$value || !$value2) {
                continue;
            }

            // Recursion if value is an array
            if (array_values($value) === $value) {
                // If numerically sequential array, ie. list of items, only check the first item
                $arrayDiff = $fn([$value[0]], [$value2[0]], $diffMsg);
            } else {
                // If associative array, compare full array
                $arrayDiff = $fn($value, $value2, $diffMsg);
            }

            // Add key only if there is a difference in the nested arrays
            if ($arrayDiff) {
                $diff[$key] = $arrayDiff;
            }
        }

        return $diff;
    };

    $diff1 = $compare_recursive($arr1, $arr2, $notInArray2Msg);
    $diff2 = $compare_recursive($arr2, $arr1, $notInArray1Msg);

    return array_replace_recursive($diff1, $diff2);
}
