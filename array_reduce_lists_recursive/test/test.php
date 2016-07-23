<?php
include '../array_reduce_lists_recursive.php';

$plainList = [1, 2, 3, 4]; // nesting level 0
printf(
    "<pre>Plain List: %s\nResult: %s\n\n</pre>",
    json_encode($plainList),
    json_encode(array_reduce_lists_recursive($plainList))
);

$a = json_decode(file_get_contents('alpha.json'), true);
printf(
    "<pre>Sample List: %s\nResult: %s\n\n</pre>",
    json_encode($a),
    json_encode(array_reduce_lists_recursive($a), JSON_PRETTY_PRINT)
);

$nestingLevels = [
    // level 0
    [
        // level 1
        [
            // level 3
            1,
            2,
            3,
            4,
        ],
        [5, 6, 7, 8],
        [9, 10, 11, 12],
    ],
    [
        [13, 14, 15, 16],
        [17, 18, 19, 20],
        [21, 22, 23, 24],
    ],
];
$reductions = [
    1,
    [1, 2],
    [1, 2, 3],
    2
];
echo '<pre>';
printf("Nesting Level: %s\n", json_encode($nestingLevels));
foreach ($reductions as $reduceListsTo) {
    printf(
        "Result for \$reduceListsTo=%s:\n%s\n",
        json_encode($reduceListsTo),
        json_encode(array_reduce_lists_recursive($nestingLevels, $reduceListsTo), JSON_PRETTY_PRINT)
    );
}
echo '</pre>';
