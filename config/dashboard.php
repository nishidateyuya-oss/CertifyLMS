<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard Cache Config
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => (int) env('DASHBOARD_CACHE_TTL', 3600),
];