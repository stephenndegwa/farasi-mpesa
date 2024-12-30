<?php
// Include the Dotenv library
require_once __DIR__ . '/vendor/autoload.php';

// Check and load the .env file
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Ensure variables are available in both $_ENV and getenv()
    foreach ($_ENV as $key => $value) {
        putenv("$key=$value");
    }
} else {
    die('Error: .env file is missing. Please ensure it exists in the project root.');
}

// Validate required environment variables
$requiredEnvVars = [
    'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME',
    'CONSUMER_KEY', 'CONSUMER_SECRET',
    'BUSINESS_SHORT_CODE', 'LIPA_NA_MPESA_PASSKEY',
    'CALLBACK_URL', 'DEFAULT_TRANSACTION_TYPE', 'PARTY_B'
];

foreach ($requiredEnvVars as $var) {
    if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
        die("Error: Missing required environment variable: $var");
    }
}

// Configuration array using $_ENV
return [
    'db' => [
        'host' => $_ENV['DB_HOST'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
        'dbname' => $_ENV['DB_NAME'],
    ],
    'mpesa' => [
        'consumer_key' => $_ENV['CONSUMER_KEY'],
        'consumer_secret' => $_ENV['CONSUMER_SECRET'],
        'business_short_code' => $_ENV['BUSINESS_SHORT_CODE'],
        'passkey' => $_ENV['LIPA_NA_MPESA_PASSKEY'],
        'callback_url' => $_ENV['CALLBACK_URL'],
        'default_transaction_type' => $_ENV['DEFAULT_TRANSACTION_TYPE'], // Load transaction type
        'party_b' => $_ENV['PARTY_B'], // Load Paybill or Till number
    ],
];
