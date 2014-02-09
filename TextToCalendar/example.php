<?php
include 'PHPExcel/PHPExcel.php';
include 'TextToCalendar.php';

set_time_limit(0);
$instance = new TextToCalendar();
$workbook = $instance->generateFromFile('example.txt');
$instance->download($workbook, 'example');
