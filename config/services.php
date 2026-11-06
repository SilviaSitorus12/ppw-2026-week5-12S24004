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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    // Layanan eksternal OpenLibrary (REST Consumer - Minggu 9)
    'openlibrary' => [
        'base_url'   => env('OPENLIBRARY_BASE_URL', 'https://openlibrary.org'),
        'covers_url' => env('OPENLIBRARY_COVERS_URL', 'https://covers.openlibrary.org'),
        'timeout'    => (int) env('OPENLIBRARY_TIMEOUT', 8),
        'contact'    => env('OPENLIBRARY_CONTACT', 'pustakawan@del.ac.id'),
    ],

    // Opsional: token GitHub untuk latihan Lab 2 (JANGAN di-commit nilainya)
    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],
];
