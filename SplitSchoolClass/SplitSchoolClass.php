<?php
/**
 * Split text containing school class names into individual classes
 *
 * Usage:
 *     $splitter = new SplitSchoolClass();
 *     $classes = $splitter('1A, 2B-D, 3W1, 3X2-4(brackets are ignored), 4Y1-4Y2 & 5Z1 (10) [] { }');
 *     echo '<pre>' . print_r($classes, true) . '</pre>';
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/SplitSchoolClass
 * @since  2014-04-23T13:30+08:00
 */
class SplitSchoolClass
{
    /**
     * Split text into school classes
     *
     * @example '1A, 2B-D, 3W1, 3X2-4(brackets are ignored), 4Y1-4Y2 & 5Z1 (10) [] { }' returns
     *          array(1A, 2B, 2C, 2D, 3W1, 3X2, 3X3, 3X4, 4Y1, 4Y2, 5Z1)
     * @param   string $text
     * @return  array of class names
     */
    public function __invoke($text)
    {
        // Normalise text and remove unwanted brackets and characters including spaces
        $text = strtoupper(str_replace('&', ',', $text));
        $text = preg_replace(
            '~\([^\)]*\)|\[[^\]]*\]|\<[^\>]*\>|\{[^\}]*\}|[^A-Z0-9,\-]~',
            '',
            $text
        );

        // Patterns for splitting class name into prefix and order
        // Does not cater for class names with hyphens, eg. 1-1
        $classPatterns = array(
            'numeric' => '~(?P<prefix>.+)(?P<order>[0-9]+)$~',
            'alphabetical' => '~(?P<prefix>.+)(?P<order>[A-Z]+)$~',
        );

        $classes = array();
        foreach (explode(',', $text) as $class) {
            if (stripos($class, '-') === false) { // single class
                $classes[] = $class;
                continue;
            }

            // Class range - assumes all classes in range use same prefix
            list($startClass, $endClass) = explode('-', $class);
            $matches = array();
            foreach ($classPatterns as $type => $pattern) {
                if (preg_match($pattern, $startClass, $matches)) {
                    $prefix = $matches['prefix'];
                    $start = $matches['order'];

                    if (preg_match($pattern, $endClass, $matches)) { // end class uses full name
                        $end = $matches['order'];
                    } else {
                        $end = $endClass;
                    }

                    // Is class ordered alphabetically or numerically?
                    $isAlphabetical = false;
                    if (!is_numeric($start)) {
                        $isAlphabetical = true;
                        $start = ord($start);
                        $end = ord($end);
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        $classes[] = $prefix . ($isAlphabetical ? chr($i) : $i);
                    }

                    break;
                }
            }
        }

        return $classes;
    }
}
