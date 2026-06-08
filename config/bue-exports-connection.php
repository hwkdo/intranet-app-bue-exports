<?php

declare(strict_types=1);

return [
    'name' => env('BUE_EXPORTS_CONNECTION', 'bue-exports-ro'),
    'driver' => 'oracle',
    'host' => env('BUE_EXPORTS_HOST', env('BUE_HOST', '10.37.100.17')),
    'port' => env('BUE_EXPORTS_PORT', '1521'),
    'database' => env('BUE_EXPORTS_DATABASE', 'hwkDO.universal'),
    'service_name' => env('BUE_EXPORTS_SERVICE_NAME', 'hwkDO.universal'),
    'username' => env('BUE_EXPORTS_USERNAME'),
    'password' => env('BUE_EXPORTS_PASSWORD'),
    'charset' => 'AL32UTF8',
    'prefix' => '',
];
