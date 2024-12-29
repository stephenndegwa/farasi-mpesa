<?php
function saveTransaction($transaction)
{
    $pdo = getDbConnection();

    $sql = "INSERT INTO transactions (TransactionType, TransID, TransTime, TransAmount, BusinessShortCode, BillRefNumber, InvoiceNumber, OrgAccountBalance, ThirdPartyTransID, MSISDN, FirstName) 
            VALUES (:TransactionType, :TransID, :TransTime, :TransAmount, :BusinessShortCode, :BillRefNumber, :InvoiceNumber, :OrgAccountBalance, :ThirdPartyTransID, :MSISDN, :FirstName)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($transaction);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }

    try {
        saveTransaction($data);
        http_response_code(200);
    } catch (Exception $e) {
        error_log('Error saving transaction: ' . $e->getMessage());
        http_response_code(500);
    }
}
?>