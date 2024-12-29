<?php
header('Content-Type: application/json');

function getAccessToken()
{
    $config = include('config.php');
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $auth = base64_encode($config['mpesa']['consumer_key'] . ':' . $config['mpesa']['consumer_secret']);

    $options = [
        'http' => [
            'header' => "Authorization: Basic $auth\r\n",
            'method' => 'GET'
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        throw new Exception('Error fetching access token');
    }

    $data = json_decode($response, true);
    return $data['access_token'];
}

function initiateStkPush($phone, $amount, $accountReference)
{
    $config = include('config.php');
    $timestamp = date('YmdHis');
    $password = base64_encode($config['mpesa']['business_short_code'] . $config['mpesa']['passkey'] . $timestamp);
    $accessToken = getAccessToken();

    $data = [
        'BusinessShortCode' => $config['mpesa']['business_short_code'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $config['mpesa']['business_short_code'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $config['mpesa']['callback_url'],
        'AccountReference' => $accountReference,
        'TransactionDesc' => 'STK Push Payment'
    ];

    $options = [
        'http' => [
            'header' => "Authorization: Bearer $accessToken\r\nContent-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents('https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest', false, $context);

    if ($response === FALSE) {
        throw new Exception('Error initiating STK Push');
    }

    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $phone = $input['phone'];
    $amount = intval($input['amount']);
    $accountReference = $input['invoiceid'];

    try {
        $response = initiateStkPush($phone, $amount, $accountReference);

        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            echo json_encode(['status' => 'success', 'message' => 'Payment Request has been sent to ' . htmlspecialchars($phone) . '. Check your phone and enter PIN']);
        } else {
            $errorMessage = isset($response['errorMessage']) ? $response['errorMessage'] : 'An unknown error occurred.';
            echo json_encode(['status' => 'error', 'message' => htmlspecialchars($errorMessage)]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>