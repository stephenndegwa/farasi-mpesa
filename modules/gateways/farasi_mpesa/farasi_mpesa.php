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
    $transactionRef = $billreference;
    $url = 'https://www.farasi.co.ke/pay/request.php?transactionRef=' . urlencode($transactionRef); // Updated to transactionRef

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    // Handle cURL errors
    if (curl_errno($ch)) {
        $curlError = curl_error($ch);
        curl_close($ch);
        echo json_encode(['error' => 'error', 'msg' => "<br><div class='alert alert-danger'>cURL Error: {$curlError}</div>"]);
        exit;
    }
    curl_close($ch);

    // Decode JSON response
    $obj = json_decode($response, true);

    if (isset($obj['success']) && $obj['success']) {
        $transaction = $obj['data'];

        // WHMCS CheckTransaction to verify if the payment exists
        $checkTransaction = checkCbTransID($transaction['TransID']);
        if ($checkTransaction) {
            echo json_encode([
                'error' => 'error',
                'msg' => "<br><div class='alert alert-danger'>Error! Payment already processed.</div>"
            ]);
            exit;
        }

        $transId = $transaction['TransID'] ?? 'Unknown';
        $transAmount = $transaction['TransAmount'] ?? 0;

        // Call the function to add invoice payment
        addInvoicePayment(
            $invoiceid,
            $transId,
            $transAmount,
            0,
            $farasiparams['name']
        );

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
    echo json_encode(['error' => 'error', 'msg' => "<br><div class='alert alert-danger'>Error! Missing session data.</div>"]);
}
?>
