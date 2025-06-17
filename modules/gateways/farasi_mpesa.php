<?php
/**
 * Farasi M-Pesa Payment Gateway Module for WHMCS
 *
 * This module integrates with the M-Pesa API to provide payment options
 * including STK Push and transaction verification.
 *
 * @copyright Copyright (c) Farasi 2025
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * @return array
 */
function farasi_mpesa_MetaData()
{
    return array(
        'DisplayName' => 'Lipa na MPESA',
        'APIVersion' => '1.2', // Use API Version 1.2
    );
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function farasi_mpesa_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Lipa na MPESA',
        ),
        
        'lisence' => array(
            'FriendlyName' => 'Your License',
            'Type' => 'text',
            'Size' => '300',
            'Default' => '',
            'Description' => 'Enter Your License (Get it from farasi.co.ke)',
        ),
         'requesturl' => array(
            'FriendlyName' => 'API Base URL',
            'Type' => 'text',
            'Size' => '5000',
            'Default' => '',
            'Description' => 'Enter your API base URL (e.g., https://example.com). The system will automatically append /api/transactions for queries and /api/stkpush for STK Push.',
        ),

        'callback_enabled' => array(
            'FriendlyName' => 'Enable M-Pesa Callback Processing',
            'Type' => 'yesno',
            'Description' => 'Automatically process M-Pesa callbacks for instant payment confirmation',
            'Default' => 'yes',
        ),

        'shortCodeType' => array(
            'FriendlyName' => 'Short Code Type',
            'Type' => 'radio',
            'Options' => 'Paybill,Till',
            'Default' => 'Paybill',
            'Description' => 'Choose Short Code Type',
        ),
        
        'shortCode' => array(
            'FriendlyName' => 'Short Code',
            'Type' => 'text',
            'Size' => '6',
            'Default' => '',
            'Description' => 'Enter Short Code',
        ),
        
        'storeNumber' => array(
            'FriendlyName' => 'Store Number',
            'Type' => 'text',
            'Size' => '6',
            'Default' => '',
            'Description' => 'Store Number (Applicable for till numbers)',
        ),
        
        'consumerKey' => array(
            'FriendlyName' => 'Consumer Key',
            'Type' => 'text',
            'Size' => '300',
            'Default' => '',
            'Description' => 'Enter Consumer Key',
        ),
        
        'consumerSecret' => array(
            'FriendlyName' => 'Consumer Secret',
            'Type' => 'text',
            'Size' => '300',
            'Default' => '',
            'Description' => 'Enter Consumer Secret',
        ),
        
        'lipaNaMpesaPasskey' => array(
            'FriendlyName' => 'Mpesa Passkey',
            'Type' => 'text',
            'Size' => '400',
            'Default' => '',
            'Description' => 'Enter Mpesa Passkey (Used in STK Push)',
        ),
        
        'mpesaApiVersion' => array(
            'FriendlyName' => 'Mpesa Api Version',
            'Type' => 'radio',
            'Options' => 'v1,v2',
            'Default' => 'v2',
            'Description' => 'Mpesa Api Version',
        ),
        
        'paymentdiscriptionpaybill' => array(
            'FriendlyName' => 'Payment Description for Paybill',
            'Type' => 'textarea',
            'Rows' => '6',
            'Cols' => '3',
            'Default' => "<h6> <b>Enter business no <strong style='color:red'>{shortcode}</strong></b></h6>  <h6> <b>Enter account no <strong style='color:red'>{accountno}</strong></b></h6> <h6> <b>Enter amount <strong style='color:red'>{amount}  {currencycode}</strong></b></h6>",
            'Description' => 'Describe your payment procedure to customer. You can use {phone}, {shortcode}, {amount}, {accountno}, {currencycode}.',
        ),
        
        'paymentdiscriptiontill' => array(
            'FriendlyName' => 'Payment Description for Till Numbers',
            'Type' => 'textarea',
            'Rows' => '6',
            'Cols' => '3',
            'Default' => "<h6> <b>Buy Goods and Services</b></h6>  <h6> <b>Enter Till Number <strong style='color:red'>{shortcode}</strong></b></h6> <h6> <b>Enter amount <strong style='color:red'>{amount}  {currencycode}</strong></b></h6>",
            'Description' => 'Describe your payment procedure to customer. You can use {phone}, {shortcode}, {amount}.',
        ),
        
        'mpesalogo' => array(
            'FriendlyName' => 'Custom Mpesa Logo Link',
            'Type' => 'text',
            'Size' => '400',
            'Default' => 'https://pay.farasi.co.ke/mpesalogo.png',
            'Description' => 'Custom Mpesa logo url',
        ),

        'autovalidatepayments' => array(
            'FriendlyName' => 'Auto Validate Payments',
            'Type' => 'yesno',
            'Description' => 'Tick to enable Auto Validate Payments',
            'Default'=>'no',
        ),
    );
}

/**
 * Payment link.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return string
 */
function farasi_mpesa_link($params)
{
    // Gateway Configuration Parameters
    $lisence = $params['lisence'];
    $shortCode = $params['shortCode'];
    $consumerKey = $params['consumerKey'];
    $consumerSecret = $params['consumerSecret'];
    $lipaNaMpesaPasskey = $params['lipaNaMpesaPasskey'];
    $paymentdiscriptionpaybill = $params['paymentdiscriptionpaybill'];
    $paymentdiscriptiontill = $params['paymentdiscriptiontill'];
    $mpesalogo = $params['mpesalogo'];
    
    $shortCodeType = $params['shortCodeType'];
    $mpesaApiVersion = $params['mpesaApiVersion'];
      $autovalidatepayments = $params['autovalidatepayments'];
    $callback_enabled = $params['callback_enabled'];
    $requesturl = $params['requesturl'];
    $stkurl = $requesturl; // Now we use the same base URL for all API calls

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];
    $billreference = $params['invoiceid'];

    // Create a helper function for logging to handle environments where logActivity function might not be available
    if (!function_exists('mpesaLogActivity')) {
        function mpesaLogActivity($message) {
            if (function_exists('logActivity')) {
                logActivity($message);
            } else {
                // Fallback logging to file
                $logDir = __DIR__ . '/logs';
                if (!file_exists($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $logFile = $logDir . '/mpesa-activity.log';
                $timestamp = date('Y-m-d H:i:s');
                $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;
                file_put_contents($logFile, $logEntry, FILE_APPEND);
            }
        }
    }

    // Define the invoice URL for form submission
    $systemUrl = $params['systemurl'];
    $invoiceurl = str_replace("http://", "https://", rtrim($systemUrl, '/') . '/viewinvoice.php?id=' . $invoiceId);

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];
      // Start session if not already started
    if (session_id() == '') {
        session_start();
    }
    
    // Store important values in session for use in auto-validation
    $_SESSION["invoiceId"] = $invoiceId;
    $_SESSION["amount"] = $amount;
    $_SESSION["requesturl"] = $requesturl;
    
    $currentlink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $addfundslink=str_replace("http://", "https://", str_replace('//clientarea','/clientarea', ''.$systemUrl.'/clientarea.php?action=addfunds'));
    
    $invoicelinkredirect=str_replace("http://", "https://", str_replace('//viewinvoice', '/viewinvoice', ''.$systemUrl.'/viewinvoice.php?id='.$invoiceId.''));
    
    if($currentlink==$addfundslink)
    {
        header('Location: ' . $invoicelinkredirect);
        exit();
    };
    
    if($autovalidatepayments == 'on' && $shortCodeType == 'Paybill')
    {
        $autovalidatelink=str_replace("http://", "https://", str_replace('//modules','/modules', ''.$systemUrl.'/modules/gateways/farasi_mpesa/farasi_mpesa.php'));
        
        $paymentverifybtn=' 
               <style>
                   .lds-ellipsis {
                      display: inline-block;
                      position: relative;
                      width: 80px;
                      height: 40px;
                    }
                    .lds-ellipsis div {
                      position: absolute;
                      top: 33px;
                      width: 13px;
                      height: 13px;
                      border-radius: 50%;
                      background: green;
                      animation-timing-function: cubic-bezier(0, 1, 1, 0);
                    }
                    .lds-ellipsis div:nth-child(1) {
                      left: 8px;
                      animation: lds-ellipsis1 0.6s infinite;
                    }
                    .lds-ellipsis div:nth-child(2) {
                      left: 8px;
                      animation: lds-ellipsis2 0.6s infinite;
                    }
                    .lds-ellipsis div:nth-child(3) {
                      left: 32px;
                      animation: lds-ellipsis2 0.6s infinite;
                    }
                    .lds-ellipsis div:nth-child(4) {
                      left: 56px;
                      animation: lds-ellipsis3 0.6s infinite;
                    }
                    @keyframes lds-ellipsis1 {
                      0% {
                        transform: scale(0);
                      }
                      100% {
                        transform: scale(1);
                      }
                    }
                    @keyframes lds-ellipsis3 {
                      0% {
                        transform: scale(1);
                      }
                      100% {
                        transform: scale(0);
                      }
                    }
                    @keyframes lds-ellipsis2 {
                      0% {
                        transform: translate(0, 0);
                      }
                      100% {
                        transform: translate(24px, 0);
                      }
                    }
                    
                </style>
                
                
                <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div> <br>Verifying payment.....
                

                <script type="text/javascript">
                    
                    setInterval(function () {
                            const xmlhttp = new XMLHttpRequest();
                            xmlhttp.onload = function() {
                                try {
                                    const response = JSON.parse(this.responseText);
                                    if(response.success) {
                                        document.getElementById("mpesaprocessmsg").innerHTML = response.msg;
                                        // Redirect to invoice page on success
                                        window.location.replace("'.$invoicelinkredirect.'");
                                    } else {
                                        document.getElementById("mpesaprocessmsg").innerHTML = response.msg || "<div class=\'alert alert-info\'>Still checking for payment...</div>";
                                    }
                                } catch(e) {
                                    console.error("Error parsing response:", e);
                                    document.getElementById("mpesaprocessmsg").innerHTML = "<div class=\'alert alert-warning\'>Error checking payment status</div>";
                                }
                            }
                            xmlhttp.onerror = function() {
                                document.getElementById("mpesaprocessmsg").innerHTML = "<div class=\'alert alert-warning\'>Connection error while checking payment</div>";
                            }
                            xmlhttp.open("POST", "'.$autovalidatelink.'");
                            xmlhttp.send();
                     
                    }, 2000);

                </script>
            ';
    }
    else
    {
        $paymentverifybtn="<button type='submit' name='verifypayment' value='verifypayment' class='btn btn-block btn-lg btn-primary'>Verify Payment</button>";
    }
    
    $returnData = '';

    // perform API call to capture payment and interpret result
    if (isset($_POST['verifypayment'])) {
        // Check if we have a transaction reference to verify
        // For Till payments - use the transaction code provided by the user
        // For Paybill payments - use the invoice number as reference
        $transactionRef = '';
        
        if ($shortCodeType == 'Till') {
            $transactionRef = $_POST['trasactioncode'] ?? '';        if (empty($transactionRef)) {
            $returnData = "<div class='alert alert-danger'><strong>Error! </strong> Transaction reference is required.</div>";
            mpesaLogActivity("Till Payment verification failed: No transaction reference provided for Invoice #{$invoiceId}");
            return $returnData;
        }
        } else {
            $transactionRef = $_POST['transactionRef'] ?? $billreference;
        }
    
    if (empty($transactionRef)) {
            $returnData = "<div class='alert alert-danger'><strong>Error! </strong> Transaction reference is required.</div>";
            mpesaLogActivity("Payment verification failed: No transaction reference provided for Invoice #{$invoiceId}");
            return $returnData;
        }
        
        // For both Paybill and Till, use the new API endpoint format
        $url = rtrim($requesturl, '/') . '/api/transactions?transactionRef=' . urlencode($transactionRef);
        mpesaLogActivity("Verifying payment for Invoice #{$invoiceId} with reference: {$transactionRef}");
        
        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log the API response
        mpesaLogActivity("Transaction API Response for Invoice #{$invoiceId}: HTTP {$httpCode}, Response: " . substr($response, 0, 500));
        
        // Handle connection errors
        if ($curlError) {
            $returnData = "<div class='alert alert-danger'><strong>Connection Error: </strong>" . htmlspecialchars($curlError) . "</div>";
            mpesaLogActivity("Transaction API Connection Error: {$curlError}");
        } else {
            // Decode JSON response
            $obj = json_decode($response, true);
            
            // Check if the response is valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $returnData = "<div class='alert alert-danger'><strong>Invalid Response: </strong>Received invalid response from payment server</div>";
                mpesaLogActivity("Transaction API Invalid Response: " . $response);
            } else {
                // Process the response based on the new API format
                if (isset($obj['success']) && $obj['success'] && !empty($obj['data'])) {
                    // Find the first valid transaction
                    $validTransaction = null;
                    foreach ($obj['data'] as $transaction) {
                        if (isset($transaction['TransID']) && !empty($transaction['TransID'])) {
                            $validTransaction = $transaction;
                            break;
                        }
                    }
                    
                    if ($validTransaction) {
                        $transId = $validTransaction['TransID'] ?? 'Unknown';
                        $transAmount = $validTransaction['TransAmount'] ?? 0;
                        $phoneNumber = $validTransaction['MSISDN'] ?? 'Unknown';
                        
                        try {
                            // Try to add the payment to the invoice
                            addInvoicePayment(
                                $invoiceId,
                                $transId,
                                $transAmount,
                                0,
                                $moduleDisplayName
                            );
                            
                            // Log successful payment
                            mpesaLogActivity("M-Pesa Payment Added: Invoice #{$invoiceId}, Transaction ID: {$transId}, Amount: {$transAmount}, Phone: {$phoneNumber}");
                            
                            $returnData = "<div class='alert alert-success'><strong>Success! </strong>Payment of Kshs {$transAmount} from {$phoneNumber} has been processed.</div>";
                            // Refresh the page to show updated invoice status
                            header("Refresh:2");
                        } catch (Exception $e) {
                            // This transaction might have been processed already
                            $errorMessage = $e->getMessage();
                            if (strpos($errorMessage, 'duplicate') !== false) {
                                $returnData = "<div class='alert alert-info'><strong>Notice: </strong>This transaction has already been applied to your invoice.</div>";
                            } else {
                                $returnData = "<div class='alert alert-warning'><strong>Payment Processing Issue: </strong>" . htmlspecialchars($errorMessage) . "</div>";
                            }
                            mpesaLogActivity("Payment processing exception: {$errorMessage}");
                        }
                    } else {
                        $returnData = "<div class='alert alert-warning'><strong>No Valid Transaction Found: </strong>Your payment might still be processing. Please try verifying again in a moment.</div>";
                    }
                } else {
                    // No transactions found or unsuccessful API response
                    $errorMessage = $obj['message'] ?? 'No payment found for this reference.';
                    $returnData = "<div class='alert alert-danger'><strong>Verification Failed: </strong>" . htmlspecialchars($errorMessage) . "</div>";
                }
            }
        }
    } 
    else if (isset($_POST['sendstkpush'])) {        // Define the URL to your server - using the updated API endpoint
        $url = rtrim($stkurl, '/') . '/api/stkpush';
        $amount = intval($amount);
        $phone = $_POST['phone'];
        $invoiceId = $params['invoiceid'];
        $description = "Payment for Invoice #{$invoiceId}";

        // Prepare data to be sent in POST request
        $postData = json_encode([
            'phone' => $phone,
            'amount' => $amount,
            'invoiceid' => $invoiceId,
            'description' => $description
        ]);
        
        // Log request for debugging
        mpesaLogActivity("Sending STK Push request for Invoice #{$invoiceId}. Phone: {$phone}, Amount: {$amount}");

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        // Set timeout to prevent long waits
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log the response
        mpesaLogActivity("STK Push API Response for Invoice #{$invoiceId}: HTTP {$httpCode}, Response: " . substr($response, 0, 500));

        // Handle connection errors
        if ($curlError) {
            $returnData = "<div class='alert alert-danger'><strong>Connection Error: </strong>" . htmlspecialchars($curlError) . "</div>";
            mpesaLogActivity("STK Push API Connection Error: {$curlError}");
        } else {
            // Decode the response
            $obj = json_decode($response, true);

            // Check if the response is a valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $returnData = "<div class='alert alert-danger'><strong>Invalid Response: </strong>Received invalid response from payment server</div>";
                mpesaLogActivity("STK Push API Invalid Response: " . $response);
            } else {
                // Process the response - using the new API response format
                if ($obj['success'] === true) {
                    // Store checkout_request_id in session for status checking
                    $_SESSION['checkout_request_id'] = $obj['data']['checkout_request_id'] ?? '';
                    
                    $returnData = "<div class='alert alert-success'><strong>Success! </strong>" . 
                        ($obj['data']['customer_message'] ?? 'Payment request has been sent to your phone. Check your phone and enter PIN') . 
                        "</div>";
                    
                    // Add JavaScript to check transaction status after a delay
                    if (!empty($_SESSION['checkout_request_id'])) {
                        $returnData .= '
                        <div id="stkStatusMessage"></div>
                        <script>
                            // Initial delay before first check
                            setTimeout(function() {
                                checkStkStatus("' . $_SESSION['checkout_request_id'] . '");
                            }, 8000);
                            
                            // Function to check STK push status
                            function checkStkStatus(checkoutRequestId) {
                                fetch("' . rtrim($requesturl, '/') . '/api/stkpush/status?checkoutRequestId=" + checkoutRequestId)
                                .then(response => response.json())
                                .then(data => {
                                    console.log("STK status response:", data);
                                    
                                    if (data.success && data.data) {
                                        if (data.data.transaction_status === "COMPLETED") {
                                            document.getElementById("stkStatusMessage").innerHTML = 
                                                "<div class=\"alert alert-success\"><strong>Payment Successful!</strong> Your payment has been received.</div>";
                                            // Reload the page after displaying success message
                                            setTimeout(function() { window.location.reload(); }, 3000);
                                        } else if (data.data.result_code !== 0) {
                                            // Payment failed
                                            document.getElementById("stkStatusMessage").innerHTML = 
                                                "<div class=\"alert alert-danger\"><strong>Payment Failed</strong> " + 
                                                (data.data.result_description || "The payment could not be completed.") + "</div>";
                                        } else {
                                            // Still pending or processing
                                            document.getElementById("stkStatusMessage").innerHTML = 
                                                "<div class=\"alert alert-info\"><strong>Payment Processing</strong> Please wait while we confirm your payment...</div>";
                                            // Check again after a few seconds
                                            setTimeout(function() {
                                                checkStkStatus(checkoutRequestId);
                                            }, 5000);
                                        }
                                    } else {
                                        document.getElementById("stkStatusMessage").innerHTML = 
                                            "<div class=\"alert alert-warning\"><strong>Payment Status Unknown</strong> " + 
                                            (data.message || "We couldn\'t verify the status of your payment.") + "</div>";
                                    }
                                })
                                .catch(error => {
                                    console.error("Error checking STK status:", error);
                                    document.getElementById("stkStatusMessage").innerHTML = 
                                        "<div class=\"alert alert-warning\"><strong>Connection Error</strong> Unable to check payment status.</div>";
                                });
                            }
                        </script>
                        ';
                    }
                } else {
                    $errorMessage = $obj['message'] ?? ($obj['data']['error'] ?? 'An unknown error occurred.');
                    $returnData = "<div class='alert alert-danger'><strong>Error: </strong>" . htmlspecialchars($errorMessage) . "</div>";
                }
            }
        }
    } 
    
    $kfdkd = array('{phone}', '{shortcode}', '{amount}', '{accountno}', '{currencycode}');
    $kfdkddfsdf = array("0$phone", $shortCode, $amount, $params['invoiceid'], $currencyCode);
    
    $refdsds = str_replace($kfdkd, $kfdkddfsdf, $paymentdiscriptionpaybill);
    
    if ($mpesalogo !== '') {
        if (getimagesize($mpesalogo)) {
            $mpesalogo = $mpesalogo;
        } else {
            $mpesalogo = "https://pay.farasi.co.ke/mpesalogo.png";
        } //check valid image
    } else {
        $mpesalogo = "https://pay.farasi.co.ke/mpesalogo.png";
    }

    $inst = "<img src=".$mpesalogo." alt='' style='width:200px;'><br>
    <mpesaprocess> <div id='mpesaprocessmsg'></div>
        
        $refdsds
        
        <form method='post' action='".$invoiceurl."'>
       
        <span class='inline-form-element'>
           $paymentverifybtn
        </span>
       
       </form>
        
       <form method='post' action='".$invoiceurl."'>
       
          <h6><b>SEND STK PUSH</b></h6>  
            <span class='inline-form-element'>
                <input type='text' name='phone' value='0$phone' class='btn btn-block btn-lg' placeholder='e.g 0700000000' required>
            </span>
      
            <span class='inline-form-element'>
               <button type='submit' name='sendstkpush' value='sendstkpush'  class='btn btn-block btn-lg btn-primary'>Send STK Push</button>
            </span>
        </form>";
    
    $kfdkd = array('{phone}', '{shortcode}', '{amount}', '{currencycode}');
    $kfdkddfsdf = array("0$phone", $shortCode, $amount, $currencyCode); 
    
    $refdsds = str_replace($kfdkd, $kfdkddfsdf, $paymentdiscriptiontill);
    
    $inst2 = "<img src=".$mpesalogo." alt='' style='width:200px;'><br>
    <mpesaprocess> <div id='mpesaprocessmsg'></div>
    
        $refdsds
        
        <form method='post' action='".$invoiceurl."'>
        <!--<input type='number' name='amountmpesa' class='form-control input-lg' placeholder='e.g 100'>
        <input type='text' name='paymentphone' class='form-control input-lg' placeholder='e.g 0700000000'>-->
        
        
        <div class='form-group'>
           <input type='text' name='trasactioncode' class='btn btn-lg' placeholder='QDA75TKUCV' style='background-color:white' required><hr>
           $paymentverifybtn
        </div>
       
       </form>
       
       <form method='post' action='".$invoiceurl."'>
       
          <h6><b>SEND STK PUSH</b></h6>  
            <span class='inline-form-element'>
                <input type='text' name='phone' value='0$phone' class='btn btn-block btn-lg' placeholder='e.g 0700000000' required  style='background-color:white'>
            </span>
            
            <span class='inline-form-element'>
               <button type='submit' name='sendstkpush' value='sendstkpush'  class='btn btn-block btn-lg btn-primary'>Send STK Push</button>
            </span>
        </form>";
        
    if ($shortCodeType == 'Till') {
        $instructions = $inst2;
    } else {
        $instructions = $inst;
    }
         
    $returnData .= $instructions;
    
    return $returnData;
}
