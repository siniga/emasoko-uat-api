<?php

return [
    'enabled' => (bool) env('TEST_DATA_ENABLED', false),
    'rows' => (int) env('TEST_DATA_ROWS', 30),
    'password' => env('TEST_DATA_PASSWORD'),
];
