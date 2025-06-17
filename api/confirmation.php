<?php
require_once 'db.php';

function saveTransaction($transaction)
{
    $pdo = getDbConnection();

    $sql = "INSERT INTO transactions (
                TransactionType, 
                TransID, 
                TransTime, 
                TransAmount, 
                BusinessShortCode, 
                BillRefNumber, 
                InvoiceNumber, 
                OrgAccountBalance, 
                ThirdPartyTransID, 
                MSISDN, 
                FirstName
            ) 
            VALUES (
                :TransactionType, 
                :TransID, 
                :TransTime, 
                :TransAmount, 
                :BusinessShortCode, 
                :BillRefNumber, 
                :InvoiceNumber, 
                :OrgAccountBalance, 
                :ThirdPartyTransID, 
                :MSISDN, 
                :FirstName
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':TransactionType' => $transaction['TransactionType'] ?? null,
        ':TransID' => $transaction['TransID'] ?? null,
        ':TransTime' => $transaction['TransTime'] ?? null,
        ':TransAmount' => $transaction['TransAmount'] ?? null,
        ':BusinessShortCode' => $transaction['BusinessShortCode'] ?? null,
        ':BillRefNumber' => $transaction['BillRefNumber'] ?? null,
        ':InvoiceNumber' => $transaction['InvoiceNumber'] ?? null,
        ':OrgAccountBalance' => $transaction['OrgAccountBalance'] ?? null,
        ':ThirdPartyTransID' => $transaction['ThirdPartyTransID'] ?? null,
        ':MSISDN' => $transaction['MSISDN'] ?? null,
        ':FirstName' => $transaction['FirstName'] ?? null
    ]);
}

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
    header('Content-Type: application/json');
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
 * Log received M-Pesa transaction data
 * 
 * @param array $data Transaction data to log
 * @param string $file Log file name
 */
function logData($data, $file = 'mpesa_transaction_logs.txt')
{
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . $file;
    $timestamp = date('Y-m-d H:i:s');
    
    // Log the raw data
    $logEntry = "[{$timestamp}] Raw M-Pesa Confirmation Received: " . 
                json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL . 
                "------------------------------------------------------" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw input
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (empty($data) || json_last_error() !== JSON_ERROR_NONE) {
        // Log the invalid data for debugging
        $errorMsg = 'Invalid JSON data: ' . json_last_error_msg();
        logData(['error' => $errorMsg, 'raw_input' => $rawInput], 'mpesa_errors.txt');
        
        // Respond with M-Pesa expected format for errors
        http_response_code(400);
        echo json_encode([
            'ResultCode' => 1,
            'ResultDesc' => 'Invalid request data'
        ]);
        exit;
    }

    // Enable logging of raw data
    logData($data);

    try {
        // Save the transaction
        saveTransaction($data);
        
        // Log successful processing
        logData([
            'status' => 'success',
            'transaction_id' => $data['TransID'] ?? 'Unknown',
            'message' => 'Transaction processed successfully'
        ], 'mpesa_success.txt');
        
        // Respond with the required response to M-Pesa
        // Note: We follow the M-Pesa API response format here, not our standard API format
        // because M-Pesa expects this specific format
        http_response_code(200);
        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Confirmation received successfully'
        ]);
    } catch (Exception $e) {
        // Log the error
        error_log('Error saving transaction: ' . $e->getMessage());
        logData([
            'status' => 'error',
            'transaction_id' => $data['TransID'] ?? 'Unknown',
            'message' => $e->getMessage()
        ], 'mpesa_errors.txt');
          // Respond with M-Pesa expected format for errors
        http_response_code(500);
        echo json_encode([
            'ResultCode' => 1,
            'ResultDesc' => 'Failed to process transaction'
        ]);
    }
} else {
    // Handle non-POST requests
    apiResponse(405, false, 'Method not allowed. This endpoint only supports POST requests');
}
?>
