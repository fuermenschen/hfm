<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'webling' => [
        'base_url' => env('WEBLING_BASE_URL'),
        'api_key' => env('WEBLING_API_KEY'),
        // Optional cURL options supported by the Webling client
        'options' => [
            'connecttimeout' => env('WEBLING_CONNECT_TIMEOUT', 5),
            'timeout' => env('WEBLING_TIMEOUT', 10),
            'useragent' => env('WEBLING_USER_AGENT', 'HFM Webling Client'),
        ],
    ],
];
