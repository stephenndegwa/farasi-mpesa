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
                FirstName, 
                MiddleName, 
                LastName
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
                :FirstName, 
                :MiddleName, 
                :LastName
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
        ':FirstName' => $transaction['FirstName'] ?? null,
        ':MiddleName' => $transaction['MiddleName'] ?? null,
        ':LastName' => $transaction['LastName'] ?? null
    ]);
}

// function logData($data, $file = 'transaction_logs.txt')
// {
//     $logEntry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
//     file_put_contents($file, $logEntry, FILE_APPEND);
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }

    // Log incoming data
    // logData($data);

    try {
        saveTransaction($data);
        http_response_code(200);
        echo json_encode(['message' => 'Transaction saved successfully']);
    } catch (Exception $e) {
        error_log('Error saving transaction: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save transaction']);
    }
}
?>
