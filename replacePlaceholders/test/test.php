<?php
include '../replacePlaceholders.php';

$data = [
    'title' => 'Test Classes',
    'classes' => [
        [
            'name' => '4A',
            'size' => 30,
        ],
        [
            'name' => '4B',
            'size' => 35,
        ],
    ],
];

$context = [
    'class' => "Name: *{name}*\nSize: {{pad_0_3_left:size}}\n\n",
];

$format = <<< 'EOD'
{title}

{{loop_class:classes}}
EOD;

printf('<pre>%s</pre>', replacePlaceholders($data, $format, $context));
