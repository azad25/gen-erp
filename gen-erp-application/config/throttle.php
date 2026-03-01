<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Define your API rate limiters here.
    |
    */

    'api' => [
        'limit' => 60,
        'per_minute' => 1,
    ],

    'api:read' => [
        'limit' => 120,
        'per_minute' => 1,
    ],

    'api:write' => [
        'limit' => 30,
        'per_minute' => 1,
    ],

];
