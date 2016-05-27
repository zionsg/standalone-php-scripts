<?php
include '../array_diff_key_recursive.php';

$a = json_decode(file_get_contents('a.json'), true);
$b = json_decode(file_get_contents('b.json'), true);

printf('<pre>%s</pre>', json_encode(array_diff_key_recursive($a, $b), JSON_PRETTY_PRINT));
