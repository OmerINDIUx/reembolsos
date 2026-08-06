<?php

return [
    'qpdf' => [
        'binary' => env('QPDF_BINARY', 'qpdf'),
        'timeout' => (int) env('QPDF_TIMEOUT', 60),
    ],
];
