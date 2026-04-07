<?php

return [
    'enabled' => env('WEBPUSH_ENABLED', true),

    'vapid' => [
        'public_key' => env('WEBPUSH_VAPID_PUBLIC_KEY'),
        'private_key' => env('WEBPUSH_VAPID_PRIVATE_KEY'),
        'subject' => env('WEBPUSH_VAPID_SUBJECT', 'mailto:info@example.com'),
    ],

    'service_worker_path' => env('WEBPUSH_SERVICE_WORKER_PATH', 'push-sw.js'),
];

