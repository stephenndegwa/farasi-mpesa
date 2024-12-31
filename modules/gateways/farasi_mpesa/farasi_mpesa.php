<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$farasifilename = basename(__FILE__, '.php');

if (isset($_SESSION['amount']) && isset($_SESSION['invoiceId'])) {
    $farasiparams = getGatewayVariables($farasifilename);

    $amount = $_SESSION['amount'];
    $invoiceid = $_SESSION['invoiceId'];
    $billreference = $_SESSION['invoiceId'];

    // Construct the URL
    $url = 'https://www.farasi.co.ke/pay/request.php?transactionRef=' . urlencode($billreference);

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $obj = json_decode($response, true);

    if (isset($obj['success']) && $obj['success'] && isset($obj['transactions']) && is_array($obj['transactions'])) {
        foreach ($obj['transactions'] as $transaction) {
            // Check if the transaction has already been processed
            try {
                checkCbTransID($transaction['transid']);

                // Call the function to add invoice payment
                addInvoicePayment(
                    $invoiceid,
                    $transaction['transid'],
                    $transaction['amount'],
                    0,
                    $farasiparams['name']
                );
            } catch (Exception $e) {
                // Log if a duplicate transaction is found
                error_log('Duplicate Transaction: ' . $transaction['transid']);
                continue;
            }
        }

        // Return success message as JSON
        echo json_encode([
            'success' => 'success',
            'msg' => "<br><div class='alert alert-success'>Success! You have paid (Kshs {$amount})</div>"
        ]);
    } else {
        // Return error message as JSON
        echo json_encode([
            'error' => 'error',
            'msg' => "<br><div class='alert alert-danger'>Error! Transaction failed.</div>"
        ]);
    }
} else {
    echo json_encode([
        'error' => 'error',
        'msg' => "<br><div class='alert alert-danger'>Error! Missing session data.</div>"
    ]);
}
?>
