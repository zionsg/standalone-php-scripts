<?php
/**
 * Generate Excel .xlsx calendar from formatted text
 *
 * @uses   PHPExcel (http://phpexcel.codeplex.com)
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/TextToCalendar
 * @since  2014-02-09T21:00+08:00
 */

class TextToCalendar
{
    /**
     * Constants defined in text
     *
     * Currently used for defining color constants.
     *
     * @var array
     */
    protected $constants = array(
        'None'    => '',
        'Red'     => 'FF0000',
        'Green'   => '00FF00',
        'Blue'    => '0000FF',
        'Cyan'    => '00FFFF',
        'Magenta' => 'FF00FF',
        'Yellow'  => 'FFFF00',
        'Gray'    => 'C0C0C0',
        'Black'   => '000000',
        'White'   => 'FFFFFF',
    );

    /**
     * Workbook
     *
     * @var PHPExcel
     */
    protected $workbook;

    /**
     * Worksheet
     *
     * @var PHPExcel_Worksheet
     */
    protected $sheet;

    /**
     * Default column width - can be changed by config in text
     *
     * @var int
     */
    protected $defaultColumnWidth = 10;

    /**
     * Current column
     *
     * In PHPExcel, A = 0.
     *
     * @var int
     */
    protected $currCol = 0;

    /**
     * Columns for current day
     *
     * A day may span many columns as events overlapping in start time
     * cannot be placed in the same column. This serves to keep track
     * of which rows (a copy of $timeRows) are used for each column.
     *
     * @see trackNewDayColumn() which initializes it
     * @example
     *     array(
     *       <Column Number> => array(
     *           <1st time row> => <true|false>,
     *       ),
     *       3 => array(
     *           '0900' => false,
     *           '0930' => true,
     * @var array
     */
    protected $currDayCols = array();

    /**
     * Header row number
     *
     * @var int
     */
    protected $headerRow = 4;

    /**
     * Time format used for $timeRows
     *
     * @var string
     */
    protected $timeRowFormat = 'Hi'; // eg. 0900, 2330

    /**
     * Map start times to row numbers
     *
     * This will be filled by addConfig().
     *
     * @example array('0900' => 2, '0930' => 3, ..., '2330' => 30)
     * @var array
     */
    protected $timeRows = array();

    /**
     * Format for section method
     *
     * @var string
     */
    protected $sectionMethodFormat = 'add%s';

    /**
     * Format for section start tag
     *
     * @var string
     */
    protected $sectionStartFormat = '*START %s*';

    /**
     * Format for section end tag
     *
     * @var string
     */
    protected $sectionEndFormat = '*END %s*';

    /**
     * Regex for detail lines in a section
     *
     * 1st group matches the detail tag, 2nd group matches the value.
     *
     * @var string
     */
    protected $detailPattern = '/^([^:]+):(.*)$/';

    /**
     * Text to insert blank line between details or insert newline in the value of a detail
     *
     * @example "Test==123" will show up as 2 lines in the Excel file
     * @var string
     */
    protected $newline = '==';

    /**
     * Lines starting with this regex pattern will be treated as comments
     *
     * @var string
     */
    protected $commentLinePattern = '/^\*\*/';

    /**
     * Default border style
     *
     * PHPExcel constants cannot be used here as it is not loaded yet.
     *
     * @var array
     */
    protected $borderStyle = array(
        'outline' => array(
            'style' => 'thin',
            'color' => array('rgb' => '000000'),
        ),
    );

    /**
     * Section tags and their respective detail tags
     *
     * A section consists of many details.
     * For every section, there must be a 'add<Name>' method that takes in an array & modifies $sheet, eg. addConfig.
     * The array must contain a key for every detail listed here, hence the default values.
     *
     * Note that for PHPExcel, column A = column 0.
     *
     * @example A section begins with a start tag, followed by 1 detail per line and closes with an end tag.
     *     *START CONFIG*
     *     Start Time: 0900
     *     End Time: 2330
     *     Interval: 30
     *     *END CONFIG*
     * @var array array(section1 => array(detail1 => defaultValue, detail2 => defaultValue), section2 => ...)
     */
    protected $sections = array(
        'Comment' => array(), // No detail tags needed as text between start and end comment tags will be ignored

        // Configuration settings - this section MUST come before all the other sections except comments
        // In the @example above, there will be 30 rows marking half an hour each from 9am to 11.30pm
        // The value for 'Title' will be printed in cell A1 of the worksheet
        'Config' => array(
            'Title' => 'Text to Calendar (https://github.com/zionsg/standalone-php-scripts/tree/master/TextToCalendar)',
            'Start Time' => '0900', // 24hr format
            'End Time'   => '2330',
            'Interval'   => 30, // minutes
            'Font Name'  => 'Calibri',
            'Font Size'  => 11,
            'Column Width' => 10, // default column width for event columns
        ),

        // Color constants. Format - <Name>: <RGB color code>
        // @see $constants for default colors
        'Colors' => array(),

        // A day consists of many events and may span more than 1 column
        'Day' => array(
            'Fill Color' => '',
            'Font Color' => 'Black',
            'Title' => '',
        ),

        // Additional details will be output as 1 line each
        // Blank lines can be inserted using $newline
        // Newlines within values can be inserted using $newline
        // Position of 'Title' and 'Start Time' among details determine printing position
        'Event' => array(
            'Fill Color' => '',
            'Font Color' => 'Black',
            'Title' => '',
            'Start Time' => '', // Time will be printed as Start Time - End Time
            'End Time'   => '',   // Will not be printed as covered by 'Start Time'
        ),
    );

    /**
     * Constructor
     *
     * Initialize workbook and worksheet.
     */
    public function __construct()
    {
        $this->workbook = new PHPExcel();
        $this->sheet = $this->workbook->getSheet(0);
    }

    /**
     * Generate Excel workbook from text file
     *
     * @param  string   $filePath Path to text file
     * @return PHPExcel
     */
    public function generateFromFile($filePath)
    {
        if (!file_exists($filePath)) {
            return $this->workbook;
        }

        return $this->generateFromText(file_get_contents($filePath));
    }

    /**
     * Generate Excel workbook from formatted text
     *
     * Details are put into an array with the detail tag as key.
     * If the detail line is $newline, uniqid() is used as key with $newline as value.
     *
     * @param  string   $text
     * @return PHPExcel
     */
    public function generateFromText($text)
    {
        $lines = explode("\n", $text);
        if ($lines === false) {
            return false;
        }

        $lineCount = count($lines);
        $currLine = 0;

        while ($currLine < $lineCount) {
            $line = trim($lines[$currLine]);
            if (!$line || preg_match($this->commentLinePattern, $line)) {
                $currLine++; // important else infinite loop
                continue;
            }

            // Look for sections and pass all lines within start & end tags to the respective section methods
            foreach ($this->sections as $sectionTag => $detailTags) {
                $startTag = sprintf($this->sectionStartFormat, $sectionTag);
                if (strcasecmp($line, $startTag) != 0) {
                    continue;
                }

                // Start of section
                $endTag = sprintf($this->sectionEndFormat, $sectionTag);
                $details = array();
                do {
                    $currLine++;
                    $line = trim($lines[$currLine]);
                    if (!$line) {
                        continue; // skip blank lines
                    }
                    if ($line == $this->newline) {
                        $details[uniqid('', true)] = $line; // microtime must be cast to string
                    } elseif (preg_match($this->detailPattern, $line, $matches)) {
                            $details[trim($matches[1])] = trim($matches[2]);
                    }
                } while (strcasecmp($line, $endTag) != 0);

                // Add default details that were not specified - cannot simply merge due to override
                // Cannot init $details to $detailTags as that will affect the sequence of details
                foreach ($detailTags as $detail => $value) {
                    if (!isset($details[$detail])) {
                        $details[$detail] = $value;
                    }
                }

                // Let section method process $details
                $sectionMethod = sprintf($this->sectionMethodFormat, $sectionTag);
                $this->$sectionMethod($details);
                break;
            }

            $currLine++;
        } // end while

        // Tie up loose ends
        $this->endPreviousDay();
        $this->sheet->setSelectedCell('A1');

        return $this->workbook;
    } // end function generate

    /**
     * Download workbook via HTTP
     *
     * @param  PHPExcel $workbook
     * @param  string   $filename  Filename without extension
     * @param  string   $extension Default = 'xlsx'. Optional extension
     * @return void
     */
    public function download($workbook, $filename, $extension = 'xlsx')
    {
        if (!$this->workbook instanceof PHPExcel) {
            return $this->workbook;
        }

        // Redirect output to a client's web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . ($extension ? '.' . $extension : '') . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1'); // If you're serving to IE 9, then the following may be needed
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        // Save file
        $workbook->setActiveSheetIndex(0); // set 1st sheet active
        $excelWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
        $excelWriter->save('php://output');
        exit;
    }

    /**
     * Resolve value
     *
     * This checks whether the argument is a defined constant and returns
     * the value of the constant, or returns back the argument itself
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function resolveValue($value)
    {
        return (isset($this->constants[$value]) ? $this->constants[$value] : $value);
    }

    /**
     * Estimate row height and set specified rows to that height
     *
     * A row of height 15 and width 10 can display 8 uppercase letters on a line based on Calibri font, font size 11.
     * Text is taken from cell at $startCol, $startRow.
     * If there are multiple rows as like in a merged cell, the row height is distributed equally.
     *
     * @param  int $startCol Start column index
     * @param  int $startRow Start row index
     * @param  int $endCol   Optional end column index
     * @param  int $endRow   Optional end row index
     * @return int
     */
    protected function setEstimatedRowHeight($startCol, $startRow, $endCol = null, $endRow = null)
    {
        if (null === $endCol) {
            $endCol = $startCol;
        }
        if (null === $endRow) {
            $endRow = $startRow;
        }

        $fontSize = $this->sheet->getStyleByColumnAndRow($startCol, $startRow)->getFont()->getSize();
        $colWidth = max(
            $this->defaultColumnWidth,
            $this->sheet->getColumnDimensionByColumn($startCol)->getWidth() // may be -1 due to autosize
        );
        $text = $this->sheet->getCellByColumnAndRow($startCol, $startRow)->getValue();
        $lineCount = ceil(strlen($text) / 8) + ceil(substr_count($text, "\n") / 2);

        $totalRowHeight = ($fontSize / 11) * (10 / $colWidth) * ($lineCount * 15);
        $rowHeight = $totalRowHeight / ($endRow - $startRow + 1); // distribute among multiple rows

        for ($i = $startRow; $i <= $endRow; $i++) {
            $this->sheet->getRowDimension($i)->setRowHeight($rowHeight);
        }
    }

    /**
     * Section method for Comment
     *
     * @param  array $details
     * @return void
     */
    protected function addComment($details)
    {
        // Do nothing
    }

    /**
     * Section method for Config
     *
     * @param  array $details
     * @return void
     */
    protected function addConfig($details)
    {
        // Configure style and page setup
        $this->sheet->getDefaultStyle()->applyFromArray(array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
			),
            'font' => array(
                'name' => $details['Font Name'],
                'size' => $details['Font Size'],
            ),
        ));
        $this->sheet->setShowGridlines(false);
        $this->sheet
             ->getPageSetup()
             ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
             ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A3)
             ->setFitToWidth(1)
             ->setFitToHeight(1);
        $this->sheet
             ->getPageMargins()
             ->setTop(0.5) // inches
             ->setBottom(0.5)
             ->setLeft(0.5)
             ->setRight(0.5);

        // Set sheet name. Print title and timestamp in cells A1 and A2 respectively
        $this->sheet->setTitle('Calendar');
        $this->sheet
             ->setCellValue('A1', $details['Title'])
             ->getStyle('A1')
             ->applyFromArray(array(
                   'alignment' => array(
                       'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                   ),
                   'font' => array(
                       'bold' => true,
                   ),
               ));
        $this->sheet
             ->setCellValue('A2', sprintf('(as of %s)', date('D d M Y H:i:s P')))
             ->getStyle('A2')
             ->getAlignment()
             ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        // Show time intervals in 1st column
        $row = $this->headerRow + 1;
        $today = date('Y-m-d ');
        $startTime = new DateTime($today . $details['Start Time']);
        $endTime = new DateTime($today . $details['End Time']);
        do {
            $time = $startTime->format($this->timeRowFormat);
            $this->sheet
                 ->setCellValueByColumnAndRow($this->currCol, $row, $time)
                 ->getStyleByColumnAndRow($this->currCol, $row)
                 ->applyFromArray(array(
                       'borders' => $this->borderStyle,
                       'fill' => array(
                           'type' => PHPExcel_Style_Fill::FILL_SOLID,
                           'startcolor' => array('rgb' => 'C0C0C0'),
                       ),
                   ));
            $this->timeRows[$time] = $row;
            $startTime->modify("+{$details['Interval']} minutes");
            $row++;
        } while ($startTime < $endTime);

        $this->defaultColumnWidth = $details['Column Width']; // default column width
        $this->currCol++; // Increment current column
    } // end function addConfig

    /**
     * Section method for Colors
     *
     * @param  array $details
     * @return void
     */
    protected function addColors($details)
    {
        foreach ($details as $name => $code) {
            $this->constants[$name] = $code;
        }
    }

    /**
     * Section method for Day
     *
     * @param  array $details
     * @return void
     */
    protected function addDay($details)
    {
        $fillColor = $this->resolveValue($details['Fill Color']);
        $style = array(
            'alignment' => array(
               'wrap' => true,
            ),
            'borders' => $this->borderStyle,
            'fill' => array(
                'type' => ($fillColor ? PHPExcel_Style_Fill::FILL_SOLID : PHPExcel_Style_Fill::FILL_NONE),
                'startcolor' => array('rgb' => $fillColor),
            ),
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => $this->resolveValue($details['Font Color'])),
            ),
        );

        $this->endPreviousDay();
        $this->trackNewDayColumn();

        $this->sheet
             ->setCellValueByColumnAndRow($this->currCol, $this->headerRow, $details['Title'])
             ->getStyleByColumnAndRow($this->currCol, $this->headerRow)
             ->applyFromArray($style);

    } // end function addDay

    /**
     * Keep track of new column in current day
     *
     * @return void
     */
    protected function trackNewDayColumn()
    {
        $this->currDayCols[$this->currCol] = array_fill_keys(array_keys($this->timeRows), false);
    }

    /**
     * End previous day - tie up loose ends
     *
     * @return void
     */
    protected function endPreviousDay()
    {
        if (!$this->currDayCols) { // ie. there is the first day
            return;
        }

        // Draw outline border for previous day
        reset($this->currDayCols);
        $firstCol = key($this->currDayCols);
        end($this->currDayCols);
        $lastCol = key($this->currDayCols);
        $firstRow = reset($this->timeRows);
        $lastRow = end($this->timeRows);
        $this->sheet
             ->getStyle(
                   PHPExcel_Cell::stringFromColumnIndex($firstCol)
                   . $firstRow
                   . ':'
                   . PHPExcel_Cell::stringFromColumnIndex($lastCol)
                   . $lastRow
               )
             ->applyFromArray(array(
                   'borders' => $this->borderStyle,
               ));

        // Merge day columns for header row
        // Must merge after day is over and not after each event as merging already-merged cells will cause errors
        $this->sheet
             ->mergeCellsByColumnAndRow($firstCol, $this->headerRow, $lastCol, $this->headerRow)
             ->getStyle(
                   PHPExcel_Cell::stringFromColumnIndex($firstCol)
                   . $this->headerRow
                   . ':'
                   . PHPExcel_Cell::stringFromColumnIndex($lastCol)
                   . $this->headerRow
               )
             ->applyFromArray(array(
                   'borders' => $this->borderStyle,
               ));

        // Increment column index and clear previous day column info
        $this->currCol++;
        $this->currDayCols = array();
    }

    /**
     * Section method for Event
     *
     * @param  array $details
     * @return void
     */
    protected function addEvent($details)
    {
        // Cell style
        $fillColor = $this->resolveValue($details['Fill Color']);
        $style = array(
            'alignment' => array(
                'wrap' => true,
            ),
            'borders' => $this->borderStyle,
            'fill' => array(
                'type' => ($fillColor ? PHPExcel_Style_Fill::FILL_SOLID : PHPExcel_Style_Fill::FILL_NONE),
                'startcolor' => array('rgb' => $fillColor),
            ),
            'font' => array(
                'color' => array('rgb' => $this->resolveValue($details['Font Color'])),
            ),
        );

        $today        = date('Y-m-d ');
        $startTime    = new DateTime($today . $details['Start Time']);
        $startTimeStr = $startTime->format($this->timeRowFormat);
        $endTime      = new DateTime($today . $details['End Time']);
        $endTimeStr   = $endTime->format($this->timeRowFormat);

        // Check if event can be slotted into current day column
        $eventCol = false;
        foreach ($this->currDayCols as $col => $rows) {
            $conflict = false;
            foreach ($rows as $rowTime => $filled) {
                if ($rowTime >= $startTimeStr && $rowTime <= $endTimeStr) {
                    if ($filled) {
                        $conflict = true;
                        break;
                    }
                }
            }

            if (!$conflict) {
                $eventCol = $col;
                break;
            }
        }
        // If event cannot be slotted, create new day column
        if ($eventCol === false) {
            $this->currCol++;
            $this->trackNewDayColumn();
            $eventCol = $this->currCol;
        }

        // Indicate time rows in day column to be filled by event
        foreach ($this->currDayCols[$eventCol] as $rowTime => $filled) {
            if ($rowTime >= $startTimeStr && $rowTime <= $endTimeStr) {
                $this->currDayCols[$eventCol][$rowTime] = true;
            }
        }

        // Create text
        $text = '';
        foreach ($details as $detail => $value) {
            // skip default details except Title and Start Time
            if ('Start Time' == $detail) {
                $value = $startTime->format('g:ia') . ' to ' . $endTime->format('g:ia');
            } elseif ($detail != 'Title' && isset($this->sections['Event'][$detail])) {
                continue;
            }
            if ($value == $this->newline) {
                $value = ''; // blank line
            }
            $text .= str_replace($this->newline, "\n", $value) . "\n";
        }

        // Set column width first as it will affect the estimated row height
        $this->sheet->getColumnDimensionByColumn($eventCol)->setWidth($this->defaultColumnWidth);

        // Set text, merge rows for event and set estimated height
        $this->sheet
             ->setCellValueByColumnAndRow($eventCol, $this->timeRows[$startTimeStr], $text)
             ->mergeCellsByColumnAndRow(
                   $eventCol,
                   $this->timeRows[$startTimeStr],
                   $eventCol,
                   $this->timeRows[$endTimeStr]
               )
             ->getStyle(
                   PHPExcel_Cell::stringFromColumnIndex($eventCol)
                   . $this->timeRows[$startTimeStr]
                   . ':'
                   . PHPExcel_Cell::stringFromColumnIndex($eventCol)
                   . $this->timeRows[$endTimeStr]
               )
             ->applyFromArray($style);
        $this->setEstimatedRowHeight(
            $eventCol, $this->timeRows[$startTimeStr], $eventCol, $this->timeRows[$endTimeStr]
        );

    } // end function addEvent
}
