<?php
// Include the Dotenv library
require_once __DIR__ . '/vendor/autoload.php';

// Load the .env file
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    die('.env file is missing. Please ensure it exists in the project root.');
}

// Configuration array
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
