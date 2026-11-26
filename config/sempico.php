<?php

return [
    // REST API token from the Sempico Solutions dashboard
    'token' => env('SEMPICO_TOKEN', ''),

    'base_url' => env('SEMPICO_BASE_URL', 'https://restapi.sempico.solutions/'),

    // seconds
    'timeout' => (int) env('SEMPICO_TIMEOUT', 30),
    'connect_timeout' => (int) env('SEMPICO_CONNECT_TIMEOUT', 10),

    'verify_ssl' => (bool) env('SEMPICO_VERIFY_SSL', true),

    // default Sender ID for the notification channel
    'sender_id' => env('SEMPICO_SENDER_ID', ''),

    // security key of the account, checked in the API_KEY header of DLR webhooks
    'webhook_key' => env('SEMPICO_WEBHOOK_KEY', ''),
];
