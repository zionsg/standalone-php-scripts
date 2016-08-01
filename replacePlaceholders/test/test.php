<?php
include '../replacePlaceholders.php';

$data = [
    'title' => 'Test Title',

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

    'object' => [
        'id' => 1,
        'name' => 'My Object',
    ],
];

$format = <<< 'EOD'
{title}
{{loop_class:classes}}
{{loop_keyValueRow_counter_keyvalue:object}}
{{loop_forRow_oneBasedIndex_for_1_5:_}}
EOD;

$context = [
    'counter' => 1000, // this will be overridden in the loops where counterName is "counter"
    'class' => "\nName: *{name}*\nSize: {{pad_3_0_left:size}}\n",
    'keyValueRow' => "{{pad_4_ _right:key}} = {value}\n",
    'forRow' => "Counter: {oneBasedIndex}\n",
];

printf('<pre>%s</pre>', replacePlaceholders($data, $format, $context));
