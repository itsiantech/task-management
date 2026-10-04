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

    'meta' => [
        'verify_token' => env('META_VERIFY_TOKEN', 'demo_verify_token'),
        'facebook_app_id' => env('META_FACEBOOK_APP_ID'),
        'facebook_app_secret' => env('META_FACEBOOK_APP_SECRET'),
        'facebook_page_token' => env('META_FACEBOOK_PAGE_TOKEN', env('FACEBOOK_PAGE_ACCESS_TOKEN')),
        'facebook_page_id' => env('META_FACEBOOK_PAGE_ID', env('FACEBOOK_PAGE_ID')),
        'whatsapp_access_token' => env('META_WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_ACCESS_TOKEN')),
        'whatsapp_phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID', env('WHATSAPP_PHONE_NUMBER_ID')),
    ],

    'facebook' => [
        'page_id' => env('FACEBOOK_PAGE_ID', env('META_FACEBOOK_PAGE_ID')),
        'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN', env('META_FACEBOOK_PAGE_TOKEN')),
    ],

];
