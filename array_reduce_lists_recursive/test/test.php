<?php
include '../array_reduce_lists_recursive.php';

$a = json_decode(file_get_contents('alpha.json'), true);
printf('<pre>%s</pre>', json_encode(array_reduce_lists_recursive($a), JSON_PRETTY_PRINT));
