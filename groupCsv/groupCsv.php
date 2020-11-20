<?php
/**
 * Parse CSV, group by the first X columns and return CSV
 *
 * See README.md for example.
 *
 * To highlight different groups in Excel:
 *   - Open output CSV in Excel.
 *   - Select all cells.
 *   - Go to Home > Conditional Formatting > New Rule.
 *       + Rule type: Use a formula to determine which cells to format
 *       + Format values where this formula is true (assuming 2 grouping columns):
 *           * If highlighting by combined group: =(INDIRECT(ADDRESS(ROW(), 1)) = "combined:0:0")
 *           * If highlighting by 1st grouping column: =(INDIRECT(ADDRESS(ROW(), 2)) = "group:1:0")
 *           * If highlighting by 2nd grouping column: =(INDIRECT(ADDRESS(ROW(), 3)) = "group:2:0")
 *       + Format: Fill > select a color.
 *       + Click OK.
 *   - Background color for groups will alternate between the selected color and the default white color.
 *
 * If running via commandline and taking in 3 arguments to match the 3 method parameters:
 *   groupCsv($argv[1], $argv[2], $argv[3]);
 *
 * @link  https://github.com/zionsg/standalone-php-scripts/tree/master/groupCsv
 * @param string $inputCsvPath Path to input CSV file
 * @param int $columnsToGroup No. of columns to group. 0="no grouping", 1="group by first column",
 *                            2="group by the first 2 columns", etc.
 * @param string $inputCsvPath Path to output CSV file
 * @return void
 */
function groupCsv($inputCsvPath, $columnsToGroup = 0, $outputCsvPath = 'php://output')
{
    $inputHandle = fopen($inputCsvPath, 'r');
    $outputHandle = fopen($outputCsvPath, 'w');

    $rowIndex = 0;
    $groupIndices = $columnsToGroup ? array_fill(0, $columnsToGroup, 1) : [];
    $prevGroup = [];

    while (($data = fgetcsv($inputHandle)) !== false) {
        // Header row - prepend X columns to row as per columns to group, as well as a combined group
        if (0 === $rowIndex) {
            $headers = [
                'Combined Group: ' . implode(',', array_slice($data, 0, $columnsToGroup))
            ];
            for ($groupCol = 0; $groupCol < $columnsToGroup; $groupCol++) {
                $headers[] = 'Group ' . ($groupCol + 1) . ': ' . $data[$groupCol];
            }
            for ($col = 0; $col < count($data); $col++) {
                $headers[] = $data[$col];
            }

            fputcsv($outputHandle, $headers);
            $rowIndex++;

            continue;
        }

        // Determine groups
        for ($groupCol = 0; $groupCol < $columnsToGroup; $groupCol++) {
            $prev = $prevGroup[$groupCol] ?? null;
            $curr = $data[$groupCol];

            if ($curr !== $prev) {
                $groupIndices[$groupCol] = ($groupIndices[$groupCol] + 1) % 2;
                $prevGroup[$groupCol] = $curr;
            } else {
                // Blank out grouping columns if value is the same as previous row
                $data[$groupCol] = '';
            }
        }

        // Prepend X columns to row as per columns to group
        for ($groupCol = ($columnsToGroup - 1); $groupCol >= 0; $groupCol--) {
            array_unshift($data, 'group:' . ($groupCol + 1) . ':' . $groupIndices[$groupCol]);
        }
        array_unshift($data, 'combined:' . implode(':', $groupIndices));

        fputcsv($outputHandle, $data);
        $rowIndex++;
    }

    fclose($inputHandle);
    fclose($outputHandle);
}
