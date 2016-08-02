<?php
/**
 * Replace placeholders in format string with variable values
 *
 * Supported placeholders:
 *   {variableName}
 *       This will be replaced by $data['variableName']
 *   {{functionName:variableName}}
 *       This will be replaced by applying the function functionName on $data['variableName']
 *   {{functionName_arg1_arg2:variableName}}
 *       Arguments can be passed into the function via underscores
 *
 * Supported functions if $functions not provided (all args are optional - see $functions in code for assumed defaults):
 *   {{pad_str_len_type:variableName}}
 *       Pads $data['variableName'] to len using str based on pad type (left, right, both)
 *   {{loop_rowName_counterName_type_start_end:rows}}
 *       Iterates over $data['rows'] using $context['rowName'] as format string for each row, {counterName} as
 *       placeholder in row format string for loop counter. The loop counter begins from 1 and
 *       will be stored in $context['counterName']. The type can be foreach, keyvalue or for, with differences below.
 *           foreach: Like `foreach (rows as row)`, using each row's array keys for placeholders, eg. {id} for row['id']
 *           keyvalue: Like `foreach (rows as key => value)`, with row format string using only {key} and {value}
 *           for: Like `for (i=start; i<=end; i++)`, with (start >= 1) and (end <= count(rows))
 *                To do a simple for loop to print numbers, ie. no variables, use _ for rows.
 *
 * @link   https://github.com/zionsg/standalone-php-scripts/tree/master/replacePlaceholders
 * @param  array  $data      Variable-value pairs. For simplicity's sake, $data cannot be an object even in recursion.
 * @param  string $format    Format string with placeholders
 * @param  array  $context   Variable-format pairs, ie. format strings for individual variables
 * @param  array  $functions functionName-callable pairs, each taking in params below and returning replacement string.
 *                           (string $variableName, $value, array $context, array $args) : string
 * @return string
 */
function replacePlaceholders(array $data, string $format, array $context, array $functions = null)
{
    if (! $format) { // cannot return if $data is empty else cannot cater for simple loop with no variables
        return '';
    }

    $me = __FUNCTION__;
    if (null === $functions) {
        $functions = [
            'pad' => function (string $varName, $value, array $context, array $args) {
                $len = (int) ($args[0] ?? 0);
                $str = $args[1] ?? ' ';
                $type = $args[2] ?? 'right';
                $type = ('both' === $type)
                      ? STR_PAD_BOTH
                      : ('left' === $type ? STR_PAD_LEFT : STR_PAD_RIGHT); // right by default

                return str_pad($value . '', $len, $str, $type); // value must be cast to string - integers will not work
            },

            'loop' => function (string $varName, $value, array $context, array $args) use ($me) {
                $replace = '';
                $rows = is_array($value) ? $value : [];

                // Arguments
                $rowName = $args[0] ?? $varName; // assume $varName by default
                $counterName = $args[1] ?? 'counter'; // assume 'counter' by default.
                $type = $args[2] ?? 'foreach';
                $start = (int) ($args[3] ?? 1);
                $end = (int) ($args[4] ?? count($rows));
                $rowFormat = $context[$rowName] ?? '';
                if (! $rowFormat) {
                    return '';
                }

                // Simple for loop with no variables, useful for just printing counters
                if ('for' === $type && '_' === $varName) {
                    $rows = array_fill($start - 1, $end, []); // zero-based array
                }
                if (! $rows) {
                    return '';
                }

                // Loop
                $cnt = $start;
                foreach ($rows as $key => $value) {
                    // Row varies according to type
                    $row = null;
                    if ('foreach' === $type) {
                        $row = $value;
                    } elseif ('keyvalue' === $type) {
                        $row = ['key' => $key, 'value' => $value];
                    } elseif ('for' === $type) {
                        $row = $rows[$cnt - 1] ?? null; // arrays are zero-based
                    }

                    if (null === $row) {
                        continue;
                    }
                    if ($counterName) {
                        $context[$counterName] = $cnt;
                    }
                    $replace .= $me($row, $rowFormat, $context);

                    $cnt++; // loop counter starts at 1
                    if ($cnt > $end) {
                        break;
                    }
                }

                return $replace;
            },
        ];
    }

    // Regex patterns for placeholders
    $fnWithArgsPattern = '([a-z0-9 _\-]+)';
    $argDelimiter = '_';
    $varPattern = '([a-z0-9_\-]+)';
    $exprRegex = sprintf(
        '/\{\{%s:%s\}\}/i',
        $fnWithArgsPattern,
        $varPattern
    );
    $varRegex = sprintf(
        '/\{%s\}/i',
        $varPattern
    );

    // Find and replace expressions with the format {{function_optionalarg1_optionalarg2:variable}}
    preg_match_all($exprRegex, $format, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        list($find, $fnWithArgs, $varName) = $match;
        $parts = explode($argDelimiter, $fnWithArgs);
        $fnName = $parts[0] ?? '';
        $args = array_slice($parts, 1);

        // Apply function
        $fn = $functions[$fnName] ?? null;
        $value = $data[$varName] ?? $context[$varName] ?? ''; // context may be used for loop counter
        if (is_callable($fn)) {
            $replace = $fn($varName, $value, $context, $args);
            $format = str_replace($find, $replace, $format);
        }
    }

    // Find and replace variables with the format {variable}
    preg_match_all($varRegex, $format, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        list($find, $varName) = $match;
        $replace = $data[$varName] ?? $context[$varName] ?? ''; // context may be used for loop counter
        $format = str_replace($find, $replace, $format);
    }

    return $format;
}
