<?php
/**
 * Generate Excel formula for substituting multiple words
 *
 * Example for computing a worksheet name (31 chars max in Excel) from a string
 * with "John, the First In Line" in cell A1 and "Doe-A-Deer-A-Female-Deer" in cell B1:
 *     getExcelSubstituteFormula(
 *         'A1 & " " & B1',       // note that Excel uses double quotes for strings not single quotes
 *         array('the', 'First' => 'Second and Next', ',', '-', ' '),
 *         '=LEFT(%s, 31)'
 *     )
 *
 * Formula returned (broken up for readability):
 *     =LEFT(TRIM(SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(CLEAN(A1 & " " & B1), "the", ""),
 *           "First", "Second and Next"), ",", ""), "-", ""), " ", "")), 31)
 *
 * Result if formula is used in Excel:
 *     JohnSecondandNextInLineDoeADeer
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/getExcelSubstituteFormula
 * @param  string $textOrCellRef Text or cell reference to use in formula. Text can be a formula also.
 * @param  array  $words         Words to replace. If key is not specified, word will be replaced by "".
 * @param  string $finalFormulaTemplate Template for returning formula using %s as the placeholder.
 * @return string
 */
function getExcelSubstituteFormula($textOrCellRef, array $words, $finalFormulaTemplate = '=%s')
{
    $formula = "CLEAN({$textOrCellRef})";

    foreach ($words as $key => $value) {
        if (is_int($key)) {
            $find = $value;
            $replace = '';
        } else {
            $find = $key;
            $replace = $value;
        }

        $formula = sprintf(
            'SUBSTITUTE(%s, "%s", "%s")',
            $formula,
            str_replace('"', '""', $find),
            $replace
        );
    }

    $formula = "TRIM({$formula})";

    return sprintf($finalFormulaTemplate, $formula);
}
