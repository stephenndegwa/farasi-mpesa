<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$farasifilename = basename(__FILE__, '.php');

if (isset($_SESSION["amount"])) {
    $farasiparams = getGatewayVariables($farasifilename);

    $lisence = $farasiparams['lisence'];
    $shortCode = $farasiparams['shortCode'];
    $consumerKey = $farasiparams['consumerKey'];
    $consumerSecret = $farasiparams['consumerSecret'];
    $lipaNaMpesaPasskey = $farasiparams['lipaNaMpesaPasskey'];
    $paymentdiscriptionpaybill = $farasiparams['paymentdiscriptionpaybill'];
    $paymentdiscriptiontill = $farasiparams['paymentdiscriptiontill'];
    $shortCodeType = $farasiparams['shortCodeType'];
    $mpesaApiVersion = $farasiparams['mpesaApiVersion'];
    $moduleDisplayName = $farasiparams['name'];

    $amount = $_SESSION["amount"];
    $invoiceid = $_SESSION["invoiceId"];
    $billreference = $_SESSION["invoiceId"];

    // Construct the URL
    $transactionRef = $billreference;
    $url = 'https://www.farasi.co.ke/pay/request.php?trans=' . urlencode($transactionRef); // Replace with your server URL

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $obj = json_decode($response, true);

    if (isset($obj['success']) && $obj['success']) {
        $transaction = $obj['data'];

        $transId = $transaction['TransID'] ?? 'Unknown';
        $transAmount = $transaction['TransAmount'] ?? 0;

        // Call the function to add invoice payment
        addInvoicePayment(
            $invoiceid,
            $transId,
            $transAmount,
            0,
            $moduleDisplayName
        );

        // Return success message as JSON
        echo json_encode(array(
            'success' => 'success',
            'msg' => "<br><div class='alert alert-success'>Success! You have paid (Kshs " . $amount . ")</div>"
        ));
    } else {
        // Return error message as JSON
        echo json_encode(array('error' => 'error', 'msg' => "<br><div class='alert alert-danger'>Error! Transaction failed.</div>"));
    }
}
?>
