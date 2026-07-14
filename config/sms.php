<?php

use App\Core\Env;

return [
    'enabled' => filter_var(Env::get('SMS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'driver' => Env::get('SMS_DRIVER', 'log_only'),
    'default_sender' => Env::get('SMS_SENDER_ID', ''),
    'africastalking' => [
        'username' => Env::get('AFRICASTALKING_USERNAME', ''),
        'api_key' => Env::get('AFRICASTALKING_API_KEY', ''),
        'endpoint' => Env::get('AFRICASTALKING_ENDPOINT', 'https://api.africastalking.com/version1/messaging'),
    ],
];
