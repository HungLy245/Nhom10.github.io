<?php

use Illuminate\Support\Str;

return [
    'driver' => 'file',

    'lifetime' => 120,

    'expire_on_close' => false,

    'encrypt' => false,

    'files' => storage_path('framework/sessions'),

    'connection' => null,

    'table' => 'sessions',

    'store' => null,

    'lottery' => [2, 100],

    'cookie' => 'laravel_session',

    'path' => '/',

    'domain' => null,

    'secure' => true,

    'http_only' => true,

    'same_site' => 'lax',
]; 