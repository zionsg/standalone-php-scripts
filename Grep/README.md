### Grep

**Purpose**<br />
Grep (UNIX command) all files for search text recursively in current directory

**Example**
```php
<?php
$instance = new Grep();
$params = array(
    'include' => array('*.ph*'),
    'pattern' => 'test[0-9]{3}',
);
echo $instance($params);
?>
```
_BECOMES_

```
Searching for regex 'test' in D:/localhost/www/GrepTest
Files excluded: greptest.php
Files included: *.ph*

./test123.php:1:This is a test string
./test456.php:2:The quick brown fox jumps over the lazy old dog. Line test
```
