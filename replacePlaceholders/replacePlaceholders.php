<?php
/**
 * Replace placeholders in format string with variable values
 *
 * Supported placeholders:
 *   {variableName} This will be replaced by $data['variableName']
 *   {{function:variableName}} This will be replaced by applying the function on $data['variableName']
 *   {{function_arg1_arg2:variableName}} Arguments can be passed into the function via underscores
 *
 * Supported functions if $functions not provided:
 *   {{loop_rowname:rows}} Iterates over rows using $context['rowname'] as format string for each row
 *   {{pad_str_len_type:variable}} Pads variable to len using str based on pad type (left, right, both)
 *
 * @link   https://github.com/zionsg/standalone-php-scripts/tree/master/replacePlaceholders
 * @param  array  $data      Variable-value pairs
 * @param  string $format    Format string with placeholders
 * @param  array  $context   Variable-format pairs, ie. format strings for individual variables
 * @param  array  $functions functionName-callable pairs, each taking in params below and returning replacement string.
 *                           (string $variableName, $value, array $context, array $args) : string
 * @return string
 */
function replacePlaceholders(array $data, string $format, array $context, array $functions = null)
{
    if (! $data || ! $format) {
        return '';
    }

    $me = __FUNCTION__;
    if (null === $functions) {
        $functions = [
            'loop' => function (string $varName, $value, array $context, array $args) use ($me) {
                $replace = '';
                $rowName = $args[0] ?? $varName;
                $rowFormat = $context[$rowName] ?? '';
                foreach (($value ?: []) as $row) {
                    $replace .= $me($row, $rowFormat, $context);
                }

                return $replace;
            },

            'pad' => function (string $varName, $value, array $context, array $args) {
                $len = $args[0] ?? 0;
                $str = $args[1] ?? ' ';
                $type = $args[2] ?? 'right';
                $type = ('both' === $type)
                      ? STR_PAD_BOTH
                      : ('left' === $type ? STR_PAD_LEFT : STR_PAD_RIGHT); // right by default

                return str_pad($value, $len, $str, $type);
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
        $value = $data[$varName] ?? '';
        if (is_callable($fn)) {
            $replace = $fn($varName, $value, $context, $args);
            $format = str_replace($find, $replace, $format);
        }
    }

    // Find and replace variables with the format {variable}
    preg_match_all($varRegex, $format, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        list($find, $varName) = $match;
        $replace = $data[$varName] ?? '';
        $format = str_replace($find, $replace, $format);
    }

    return $format;
}
