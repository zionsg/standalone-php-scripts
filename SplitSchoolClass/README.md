### SplitSchoolClass

**Purpose**<br />
Split text containing school class names into individual classes

**Example**
```php
<?php
$splitter = new SplitSchoolClass();
$classes = $splitter('1A, 2B-D, 3W1, 3X2-4, 4Y1-4Y2 & 5Z1 (10) [] { }');
echo '<pre>' . print_r($classes, true) . '</pre>';
?>
```
_BECOMES_
```
Array
(
    [0] => 1A
    [1] => 2B
    [2] => 2C
    [3] => 2D
    [4] => 3W1
    [5] => 3X2
    [6] => 3X3
    [7] => 3X4
    [8] => 4Y1
    [9] => 4Y2
    [10] => 5Z1
)
```
