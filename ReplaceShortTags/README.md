### ReplaceShortTags

**Purpose**<br />
Reads PHP file and replaces short tags

**Example**
```php
<?php // test.php ?>
<? $a = 1; ?>
<?=$a;?>
```

```php
<?php
$instance = new ReplaceShortTags();
echo '<pre>' . htmlspecialchars($instance('test.php')) . '</pre>';
?>
```
_BECOMES_
```
<?php // test.php ?>
<?php  $a = 1; ?>
<?php echo $a;?>
```
