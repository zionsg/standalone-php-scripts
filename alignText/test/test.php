<?php
include '../alignText.php';

$text = <<< 'EOD'
S/N | Name  | Address     | Phone
1   | Alice | 1 Alpha Ave | 12345678
2|Bob|    2 Beta Street       |23456789
10 | Charlie | 3 Gamma Driveway, #10-01 | 34567890
EOD;

printf('<pre>%s</pre>', alignText($text, '|'));

/* Output
S/N | Name    | Address                  | Phone
1   | Alice   | 1 Alpha Ave              | 12345678
2   | Bob     | 2 Beta Street            | 23456789
10  | Charlie | 3 Gamma Driveway, #10-01 | 34567890
*/
