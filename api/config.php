<?php
return [
    'db' => [
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME')
    ],
    'mpesa' => [
        'consumer_key' => getenv('CONSUMER_KEY'),
        'consumer_secret' => getenv('CONSUMER_SECRET'),
        'business_short_code' => getenv('BUSINESS_SHORT_CODE'),
        'passkey' => getenv('LIPA_NA_MPESA_PASSKEY'),
        'callback_url' => getenv('CALLBACK_URL')
    ]
];
?>