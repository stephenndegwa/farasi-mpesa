<?php
/**
 * WHMCS Merchant Gateway 3D Secure Callback File
 *
 * The purpose of this file is to demonstrate how to handle the return post
 * from a 3D Secure Authentication process.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * Users are expected to be redirected to this file as part of the 3D checkout
 * flow so it also demonstrates redirection to the invoice upon completion.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
App::load_function('gateway');
App::load_function('invoice');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$success = $_POST["x_status"];
$invoiceId = $_POST["x_invoice_id"];
$transactionId = $_POST["x_trans_id"];
$paymentAmount = $_POST["x_amount"];
$paymentFee = $_POST["x_fee"];
$hash = $_POST["x_hash"];

$transactionStatus = $success ? 'Success' : 'Failure';

/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */
$secretKey = $gatewayParams['secretKey'];
if ($hash != md5($secretKey . $invoiceId . $transactionId . $paymentAmount)) {
    $transactionStatus = 'Hash Verification Failure';
    $success = false;
}

/**
 * Process M-Pesa callbacks.
 * 
 * This function handles the callback data from the M-Pesa API.
 * It validates the invoice ID and transaction ID, then logs the transaction
 * and adds the payment to the invoice.
 */

// Check if we're receiving JSON data (new M-Pesa format)
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

if (!empty($jsonData) && json_last_error() === JSON_ERROR_NONE) {
    // Using the new JSON format from API
    $invoiceId = $jsonData['BillRefNumber'] ?? null;
    $transactionId = $jsonData['TransID'] ?? null;
    $paymentAmount = $jsonData['TransAmount'] ?? null;
    $phoneNumber = $jsonData['MSISDN'] ?? null;
    $firstName = $jsonData['FirstName'] ?? null;
    $transactionType = $jsonData['TransactionType'] ?? null;
    $transactionTime = $jsonData['TransTime'] ?? null;
    
    $success = (!empty($transactionId) && !empty($invoiceId) && $paymentAmount > 0);
    $transactionStatus = $success ? 'Success' : 'Failure';
    
    // Debug data for logging
    $debugData = [
        'json_data' => $jsonData,
        'success' => $success,
        'transaction_id' => $transactionId,
        'invoice_id' => $invoiceId,
        'payment_amount' => $paymentAmount,
    ];
} else {
    // Fallback to using traditional POST parameters
    $success = $_POST["x_status"] ?? false;
    $invoiceId = $_POST["x_invoice_id"] ?? null;
    $transactionId = $_POST["x_trans_id"] ?? null;
    $paymentAmount = $_POST["x_amount"] ?? null;
    $paymentFee = $_POST["x_fee"] ?? 0;
    $hash = $_POST["x_hash"] ?? '';

    $transactionStatus = $success ? 'Success' : 'Failure';
    
    // Debug data for logging
    $debugData = $_POST;
}

// Basic validation
if (empty($invoiceId) || empty($transactionId)) {
    logTransaction($gatewayParams['name'], $debugData, 'Missing required parameters');
    header('HTTP/1.1 400 Bad Request');
    exit('Missing required parameters');
}

/**
 * Validate Callback Invoice ID.
 * 
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 * 
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);

/**
 * Log Transaction.
 * 
 * @param string $gatewayName Display label
 * @param string|array $debugData Data to log
 * @param string $transactionStatus Status
 */
logTransaction($gatewayParams['name'], $debugData, $transactionStatus);

$paymentSuccess = false;

if ($success) {
    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        0, // No payment fee for M-Pesa
        $gatewayModuleName
    );

    $paymentSuccess = true;
    
    // Log successful M-Pesa payment
    logTransaction(
        $gatewayParams['name'], 
        array_merge($debugData, ['message' => 'M-Pesa payment added successfully']), 
        'Payment Successful'
    );
    
    // Return success response to M-Pesa API
    header('Content-Type: application/json');
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Confirmation received successfully'
    ]);
} else {
    // Log failed payment
    logTransaction(
        $gatewayParams['name'], 
        array_merge($debugData, ['message' => 'Failed to process M-Pesa payment']), 
        'Payment Failed'
    );
    
    // Return error response to M-Pesa API
    header('Content-Type: application/json');
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Failed to process transaction'
    ]);
}

// Do not redirect - this is an API endpoint
// The M-Pesa API expects a response, not a redirect
exit();
