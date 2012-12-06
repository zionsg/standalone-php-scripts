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

_to be filled in later_
