<?php
/**
 * WHMCS Sample Merchant Gateway Module
 *
 * This sample file demonstrates how a merchant gateway module supporting
 * 3D Secure Authentication, Captures and Refunds can be structured.
 *
 * If your merchant gateway does not support 3D Secure Authentication, you can
 * simply omit that function and the callback file from your own module.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "merchantgateway" and therefore all functions
 * begin "merchantgateway_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function farasi_mpesa_MetaData()
{
    return array(
        'DisplayName' => 'Lipa na MPESA',
        'APIVersion' => '1.2', // Use API Version 1.2
    );
}


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

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];
    $billreference = $params['invoiceid'];


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
    
    session_start();
    
    $_SESSION["invoiceId"] = $invoiceId;
    
    $_SESSION["amount"] = $amount;
    
    
    $currentlink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $addfundslink=str_replace("http://", "https://", str_replace('//clientarea','/clientarea', ''.$systemUrl.'/clientarea.php?action=addfunds'));
    
    $invoicelinkredirect=str_replace("http://", "https://", str_replace('//viewinvoice', '/viewinvoice', ''.$systemUrl.'/viewinvoice.php?id='.$invoiceId.''));
    
    
    if($currentlink==$addfundslink)
    {
        header('Location: ' . $invoicelinkredirect);
        exit();
    };
    
    if($autovalidatepayments == 'on' and $shortCodeType == 'Paybill')
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
                                const sdsd = JSON.parse(this.responseText);
                                if(sdsd.success)
                                {
                                    document.getElementById("mpesaprocessmsg").innerHTML=sdsd.msg;
                                    window.location.replace("'.$invoicelinkredirect.'");
                                }
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
    

    
    // perform API call to capture payment and interpret result

    if ($_POST['verifypayment']) {
        if ($shortCodeType == 'Till') {
            $transactionRef = $_POST['transactionRef'] ?? '';
    
            if (empty($transactionRef)) {
                $returnData = "<div class='alert alert-danger'><strong>Error! </strong> Transaction reference is required.</div>";
            } else {
                $url = 'https://www.farasi.co.ke/pay/request.php?trans=' . urlencode($transactionRef); // Replace with your server URL
    
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
    
                $obj = json_decode($response, true);
    
                if ($obj['success']) {
                    $transaction = $obj['data'];
    
                    $transId = $transaction['TransID'] ?? 'Unknown';
                    $transAmount = $transaction['TransAmount'] ?? 0;
    
                    addInvoicePayment(
                        $invoiceId,
                        $transId,
                        $transAmount,
                        0,
                        $moduleDisplayName
                    );
    
                    $returnData = "<div class='alert alert-success'><strong>Success! You have paid (Kshs " . $transAmount . ")</strong></div>";
                    header("Refresh:0");
                } else {
                    $returnData = "<div class='alert alert-danger'><strong>Error! </strong> " . $obj['message'] . "</div>";
                }
            }
        } else {
            $transactionRef = $_POST['transactionRef'] ?? '';
    
            if (empty($transactionRef)) {
                $returnData = "<div class='alert alert-danger'><strong>Error! </strong> Transaction reference is required.</div>";
            } else {
                $url = 'https://www.farasi.co.ke/pay/request.php?trans=' . urlencode($transactionRef); // Replace with your server URL
    
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
    
                $obj = json_decode($response, true);
    
                if ($obj['success']) {
                    $transaction = $obj['data'];
    
                    $transId = $transaction['TransID'] ?? 'Unknown';
                    $transAmount = $transaction['TransAmount'] ?? 0;
    
                    addInvoicePayment(
                        $invoiceId,
                        $transId,
                        $transAmount,
                        0,
                        $moduleDisplayName
                    );
    
                    $returnData = "<div class='alert alert-success'><strong>Success! You have paid (Kshs " . $transAmount . ")</strong></div>";
                    header("Refresh:0");
                } else {
                    $returnData = "<div class='alert alert-danger'><strong>Error! </strong> " . $obj['message'] . "</div>";
                }
            }
        }
    }

    
    
    else if ($_POST['sendstkpush']) {
        // Define the URL to your  server
        $url = 'https://api.farasi.co.ke/stkpush.php'; // Replace with your server URL
        $amount = intval($amount);
        $phone = $_POST['phone'];
        $invoiceId = $params['invoiceid'];

        // Prepare data to be sent in POST request
        $postData = json_encode([
            'phone' => $phone,
            'amount' => $amount,
            'invoiceid' => $invoiceId
        ]);

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute cURL request
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode the response
        $obj = json_decode($response, true);

        // Process the response
        if ($obj['status'] == 'success') {
            $returnData = "<div class='alert alert-success'><strong>Success! Payment Request has been sent to " . htmlspecialchars($phone) . ". Check your phone and enter PIN </strong></div>";
        } else {
            $errorMessage = isset($obj['message']) ? $obj['message'] : 'An unknown error occurred.';
            $returnData = "<div class='alert alert-danger'><strong> Error! </strong> " . htmlspecialchars($errorMessage) . "</div>";
        }
    } else {
        $returnData = "";
    }

    
    $kfdkd=array('{phone}', '{shortcode}', '{amount}', '{accountno}', '{currencycode}');
    $kfdkddfsdf=array("0$phone", $shortCode, $amount, $params['invoiceid'], $currencyCode);
    
        $refdsds = str_replace($kfdkd, $kfdkddfsdf, $paymentdiscriptionpaybill);
        
        if($mpesalogo!=='')
        {
            if(getimagesize($mpesalogo)){$mpesalogo=$mpesalogo;}else{$mpesalogo="https://pay.farasi.co.ke/mpesalogo.png";} //check valid image
        }
        else
        {
            $mpesalogo="https://pay.farasi.co.ke/mpesalogo.png";
        }
    
    
        $inst= "<img src=".$mpesalogo." alt='' style='width:200px;'><br>
        <mpesaprocess> <div id='mpesaprocessmsg'></div>
            
            $refdsds
            
            <form method='post' action=".$invoiceurl.">
           
            <span class='inline-form-element'>
               $paymentverifybtn
            </span>
           
           </form>
            
           <form method='post' action=".$invoiceurl.">
           
              <h6><b>SEND STK PUSH</b></h6>  
                <span class='inline-form-element'>
                    <input type='text' name='phone' value='0$phone' class='btn btn-block btn-lg' placeholder='e.g 0700000000' required>
                </span>
          
                <span class='inline-form-element'>
                   <button type='submit' name='sendstkpush' value='sendstkpush'  class='btn btn-block btn-lg btn-primary'>Send STK Push</button>
                </span>
            </form>";
        
        $kfdkd=array('{phone}', '{shortcode}', '{amount}', '{currencycode}');
        $kfdkddfsdf=array("0$phone", $shortCode, $amount, $currencyCode); 
        
        $refdsds = str_replace($kfdkd, $kfdkddfsdf, $paymentdiscriptiontill);
        
        $inst2= "<img src=".$mpesalogo." alt='' style='width:200px;'><br>
        <mpesaprocess> <div id='mpesaprocessmsg'></div>
        
            $refdsds
            
            <form method='post' action=".$invoiceurl.">
            <!--<input type='number' name='amountmpesa' class='form-control input-lg' placeholder='e.g 100'>
            <input type='text' name='paymentphone' class='form-control input-lg' placeholder='e.g 0700000000'>-->
            
            
            <div class='form-group'>
               <input type='text' name='trasactioncode' class='btn btn-lg' placeholder='QDA75TKUCV' style='background-color:white' required><hr>
               $paymentverifybtn
            </div>
           
           </form>
           
           <form method='post' action=".$invoiceurl.">
           
              <h6><b>SEND STK PUSH</b></h6>  
                <span class='inline-form-element'>
                    <input type='text' name='phone' value='0$phone' class='btn btn-block btn-lg' placeholder='e.g 0700000000' required  style='background-color:white'>
                </span>
                
                <span class='inline-form-element'>
                   <button type='submit' name='sendstkpush' value='sendstkpush'  class='btn btn-block btn-lg btn-primary'>Send STK Push</button>
                </span>
            </form>";
            

        if($shortCodeType=='Till'){
            $instructions=$inst2;
        }
        else
        {
            $instructions=$inst;
        }
            
        
                 
    $returnData.=$instructions;

    return $returnData;
}




/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
/*function farasi_mpesa_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

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
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fee' => $feeAmount,
    );
}*/