<?php
header('Content-Type: application/json');

/**
 * Standard API Response
 * 
 * @param int $statusCode HTTP status code
 * @param bool $success Whether the request was successful
 * @param string $message Message to describe the result
 * @param array $data Optional data payload
 * @param array $meta Optional metadata
 * @return string JSON encoded response
 */
function apiResponse($statusCode, $success, $message, $data = null, $meta = null) {
    http_response_code($statusCode);
    
    $response = [
        'status' => $statusCode,
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($meta !== null) {
        $response['meta'] = $meta;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Log API request and response
 * 
 * @param string $type Request or Response
 * @param array $data Data to log
 * @return void
 */
function logApiTransaction($type, $data) {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logFile = $logDir . '/mpesa_api_' . date('Y-m-d') . '.log';
    $logData = "[{$timestamp}] {$type}: " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    file_put_contents($logFile, $logData, FILE_APPEND);
}

/**
 * Get M-Pesa API access token
 * 
 * @return string Access token
 * @throws Exception If token cannot be retrieved
 */
function getAccessToken()
{
    $config = include('config.php');
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $auth = base64_encode($config['mpesa']['consumer_key'] . ':' . $config['mpesa']['consumer_secret']);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $auth));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    
    // Log the API request
    logApiTransaction('AccessToken Request', ['url' => $url, 'headers' => ['Authorization' => 'Basic ' . substr($auth, 0, 10) . '...']]);

    if (curl_errno($ch)) {
        throw new Exception('Error fetching access token: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        $errorResponse = json_decode($response, true);
        $errorMessage = isset($errorResponse['errorMessage']) ? $errorResponse['errorMessage'] : 'Unknown error';
        // Log the error response
        logApiTransaction('AccessToken Error', ['http_code' => $httpCode, 'error' => $errorMessage]);
        throw new Exception('Error fetching access token: ' . $errorMessage);
    }

    curl_close($ch);

    $data = json_decode($response, true);
    // Log successful response (but mask the token)
    logApiTransaction('AccessToken Response', ['success' => true, 'token' => substr($data['access_token'], 0, 10) . '...']);
    
    return $data['access_token'];
}

/**
 * Initiate STK Push request to M-Pesa
 * 
 * @param string $phone Phone number in format 254XXXXXXXXX
 * @param int $amount Amount to be paid
 * @param string $accountReference Account reference
 * @param string $description Optional transaction description
 * @return array API response
 * @throws Exception If request fails
 */
function initiateStkPush($phone, $amount, $accountReference, $description = 'STK Push Payment')
{
    $config = include('config.php');
    $timestamp = date('YmdHis');
    $password = base64_encode($config['mpesa']['business_short_code'] . $config['mpesa']['passkey'] . $timestamp);
    $accessToken = getAccessToken();
    
    // Format phone number (ensure it has 254 prefix)
    $formattedPhone = $phone;
    if (substr($phone, 0, 1) === '0') {
        $formattedPhone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 4) === '+254') {
        $formattedPhone = substr($phone, 1);
    }
    
    // Use the transaction type from config or fallback to default
    $transactionType = $config['mpesa']['default_transaction_type'] ?? 'CustomerBuyGoodsOnline';
    // Use Party B from config or fallback to business short code
    $partyB = $config['mpesa']['party_b'] ?? $config['mpesa']['business_short_code'];

    $requestData = [
        'BusinessShortCode' => $config['mpesa']['business_short_code'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => $transactionType,
        'Amount' => $amount,
        'PartyA' => $formattedPhone,
        'PartyB' => $partyB,
        'PhoneNumber' => $formattedPhone,
        'CallBackURL' => $config['mpesa']['callback_url'],
        'AccountReference' => $accountReference,
        'TransactionDesc' => $description
    ];

    // Log the request (mask sensitive data)
    $logRequestData = $requestData;
    $logRequestData['Password'] = substr($logRequestData['Password'], 0, 10) . '...';
    logApiTransaction('STK Push Request', $logRequestData);

    $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $responseData = json_decode($response, true);

    // Log the response
    logApiTransaction('STK Push Response', [
        'http_code' => $httpCode,
        'response' => $responseData
    ]);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error initiating STK Push: ' . $error);
    }

    if ($httpCode != 200) {
        $errorMessage = isset($responseData['errorMessage']) ? $responseData['errorMessage'] : 'Unknown error';
        curl_close($ch);
        throw new Exception('Error initiating STK Push: ' . $errorMessage);
    }

    curl_close($ch);
    return $responseData;
}

/**
 * Check the status of an STK Push transaction
 * 
 * @param string $checkoutRequestId The CheckoutRequestID from the STK push response
 * @return array API response
 * @throws Exception If request fails
 */
function checkStkPushStatus($checkoutRequestId)
{
    $config = include('config.php');
    $timestamp = date('YmdHis');
    $password = base64_encode($config['mpesa']['business_short_code'] . $config['mpesa']['passkey'] . $timestamp);
    $accessToken = getAccessToken();

    $requestData = [
        'BusinessShortCode' => $config['mpesa']['business_short_code'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestId
    ];

    // Log the request (mask sensitive data)
    $logRequestData = $requestData;
    $logRequestData['Password'] = substr($logRequestData['Password'], 0, 10) . '...';
    logApiTransaction('STK Push Status Check Request', $logRequestData);

    $url = 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $responseData = json_decode($response, true);

    // Log the response
    logApiTransaction('STK Push Status Check Response', [
        'http_code' => $httpCode,
        'response' => $responseData
    ]);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error checking STK Push status: ' . $error);
    }

    curl_close($ch);
    return $responseData;
}

// Main request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Parse and validate input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            apiResponse(400, false, 'Invalid JSON payload: ' . json_last_error_msg());
        }
        
        // Validate required fields
        $requiredFields = ['phone', 'amount', 'invoiceid'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            apiResponse(400, false, 'Missing required fields: ' . implode(', ', $missingFields));
        }
        
        // Extract and sanitize inputs
        $phone = trim($input['phone']);
        $amount = intval($input['amount']);
        $accountReference = trim($input['invoiceid']);
        $description = isset($input['description']) ? trim($input['description']) : 'STK Push Payment';
        
        // Validate phone number format
        if (!preg_match('/^(?:\+?254|0)[17]\d{8}$/', $phone)) {
            apiResponse(400, false, 'Invalid phone number format. Use format: 254XXXXXXXXX or 0XXXXXXXXX');
        }
        
        // Validate amount
        if ($amount <= 0) {
            apiResponse(400, false, 'Amount must be greater than zero');
        }

        // Initiate STK Push
        $response = initiateStkPush($phone, $amount, $accountReference, $description);

        // Process response
        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            // Success response
            apiResponse(200, true, 'Payment request initiated successfully', [
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'customer_message' => 'Payment request has been sent to ' . htmlspecialchars($phone) . '. Please check your phone and enter PIN.',
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'response_code' => $response['ResponseCode'],
                'response_description' => $response['ResponseDescription'] ?? 'Success'
            ]);
        } else {
            // Failed request but got a response
            $errorMessage = $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'An unknown error occurred';
            apiResponse(400, false, 'STK push request failed', [
                'error' => $errorMessage,
                'response' => $response
            ]);
        }
    } catch (Exception $e) {
        // Exception occurred
        apiResponse(500, false, 'STK push request failed', [
            'error' => $e->getMessage()
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['checkoutRequestId'])) {
    // Check STK Push status
    try {
        $checkoutRequestId = trim($_GET['checkoutRequestId']);
        
        // Validate CheckoutRequestID
        if (empty($checkoutRequestId)) {
            apiResponse(400, false, 'Invalid CheckoutRequestID');
        }
        
        $response = checkStkPushStatus($checkoutRequestId);
        
        // Process response
        if (isset($response['ResponseCode'])) {
            if ($response['ResponseCode'] == '0') {
                // Extract result details
                $resultCode = isset($response['ResultCode']) ? intval($response['ResultCode']) : null;
                $isSuccessful = ($resultCode === 0);
                $statusMessage = $response['ResultDesc'] ?? 'Unknown status';
                
                apiResponse(200, $isSuccessful, $statusMessage, [
                    'checkout_request_id' => $checkoutRequestId,
                    'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                    'result_code' => $resultCode,
                    'result_description' => $response['ResultDesc'] ?? null,
                    'transaction_status' => $isSuccessful ? 'COMPLETED' : 'FAILED',
                    'transaction_date' => $response['TransactionDate'] ?? null,
                    'phone_number' => isset($response['MSISDN']) ? $response['MSISDN'] : null,
                    'amount' => isset($response['Amount']) ? $response['Amount'] : null
                ]);
            } else {
                apiResponse(400, false, 'Failed to check transaction status', [
                    'checkout_request_id' => $checkoutRequestId,
                    'response_code' => $response['ResponseCode'],
                    'response_description' => $response['ResponseDescription'] ?? 'Unknown error'
                ]);
            }
        } else {
            apiResponse(400, false, 'Unable to retrieve STK Push status', $response);
        }
    } catch (Exception $e) {
        apiResponse(500, false, 'Error checking STK Push status', [
            'error' => $e->getMessage()
        ]);
    }
} else {
    // Invalid request method
    apiResponse(405, false, 'Method not allowed. This endpoint only supports POST requests');
}
?>
