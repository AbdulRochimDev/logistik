<?php

return [
    'auth' => [
        'admin_password' => env('ADMIN_PASSWORD', 'ChangeMe!123'),
        'driver_password' => env('DRIVER_PASSWORD', 'password'),
        'demo_driver_email' => env('DEMO_DRIVER_EMAIL', 'driver.demo@example.com'),
        'demo_driver_password' => env('DEMO_DRIVER_PASSWORD', 'password'),
    ],

    'database' => [
        'ssl' => [
            'enabled' => filter_var(env('DB_SSL', false), FILTER_VALIDATE_BOOL),
            'ca_path' => env('DB_CA_PATH'),
            'verify_server_cert' => env('DB_SSL_VERIFY_SERVER_CERT'),
        ],
    ],

    'storage' => [
        'pod_disk' => env('POD_STORAGE_DISK', env('FILESYSTEM_DISK', 's3')),
        'pod_url_ttl' => (int) env('POD_URL_TTL', 900),
    ],
];
