<?php

return [
    'access_key_id' => env('AWS_ACCESS_KEY_ID'),
    'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_PINPOINT_REGION', 'us-east-1'),
    'sender_id' => env('AWS_PINPOINT_SENDER_ID', null),
    'application_id' => env('AWS_PINPOINT_APPLICATION_ID'),
];
