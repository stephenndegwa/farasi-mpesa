<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Helper function to log activity since WHMCS logActivity might not be accessible
function mpesaLogActivity($message) {
    // First try to use WHMCS's logActivity if it exists
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

// Get module name from filename
$farasifilename = basename(dirname(__FILE__));

// Start session if not already started
if (session_id() == '') {
    session_start();
}

if (isset($_SESSION['amount']) && isset($_SESSION['invoiceId'])) {
    // Get the gateway parameters from WHMCS
    $farasiparams = getGatewayVariables($farasifilename);

    $amount = $_SESSION['amount'];
    $invoiceid = $_SESSION['invoiceId'];
    $billreference = $_SESSION['invoiceId'];
    
    // Get requesturl from gateway parameters or session
    $requesturl = $farasiparams['requesturl'] ?? $_SESSION['requesturl'] ?? '';
    
    // Check if requesturl is set, otherwise we can't proceed
    if (empty($requesturl)) {
        echo json_encode([
            'error' => 'error',
            'msg' => "<br><div class='alert alert-danger'>Error: API URL not configured properly in gateway settings</div>"
        ]);
        mpesaLogActivity("Transaction verification failed: API URL not set for Invoice #{$invoiceid}");
        exit;
    }
    
    // Log the transaction check start
    mpesaLogActivity("Checking M-Pesa transaction for Invoice #{$invoiceid}");

    // Make sure the URL ends with a slash before adding the path
    $baseUrl = rtrim($requesturl, '/');
    
    // Construct the URL - using the new API endpoint format
    $url = "{$baseUrl}/api/transactions?transactionRef=" . urlencode($billreference);

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log the API response
    mpesaLogActivity("Transaction API Response for Invoice #{$invoiceid}: HTTP {$httpCode}, Response: " . substr($response, 0, 500));

    // Handle connection errors
    if ($curlError) {
        echo json_encode([
            'error' => 'error',
            'msg' => "<br><div class='alert alert-danger'>Connection Error: " . htmlspecialchars($curlError) . "</div>"
        ]);
        mpesaLogActivity("Transaction API Connection Error: {$curlError}");
        exit;
    }

    // Decode JSON response
    $obj = json_decode($response, true);

    // Check if the response is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'error' => 'error',
            'msg' => "<br><div class='alert alert-danger'>Invalid Response: Received invalid response from payment server</div>"
        ]);
        mpesaLogActivity("Transaction API Invalid Response: " . $response);
        exit;
    }

    // Process the response based on the new API format
    if (isset($obj['success']) && $obj['success'] && isset($obj['data']) && is_array($obj['data'])) {
        $transactionsProcessed = 0;
        $successfulTransactions = [];
        
        foreach ($obj['data'] as $transaction) {
            // Check if we have a valid TransID
            if (!isset($transaction['TransID']) || empty($transaction['TransID'])) {
                continue;
            }

            // Prepare transaction data
            $transId = $transaction['TransID'];
            $transAmount = $transaction['TransAmount'] ?? 0;
            $phoneNumber = $transaction['MSISDN'] ?? 'Unknown';
            
            // Check if the transaction has already been processed
            try {
                // Call WHMCS function to check if transaction already exists
                if (function_exists('checkCbTransID')) {
                    checkCbTransID($transId);
                }

                // Call WHMCS function to add payment to invoice
                if (function_exists('addInvoicePayment')) {
                    addInvoicePayment(
                        $invoiceid,
                        $transId,
                        $transAmount,
                        0,
                        $farasiparams['name']
                    );
                    
                    // Add to successful transactions
                    $successfulTransactions[] = [
                        'transId' => $transId,
                        'amount' => $transAmount,
                        'phone' => $phoneNumber
                    ];
                    
                    // Log successful payment
                    mpesaLogActivity("M-Pesa Payment Added: Invoice #{$invoiceid}, Transaction ID: {$transId}, Amount: {$transAmount}, Phone: {$phoneNumber}");
                    $transactionsProcessed++;
                } else {
                    mpesaLogActivity("Error: addInvoicePayment function not available");
                }
            } catch (Exception $e) {
                // Log if a duplicate transaction is found
                mpesaLogActivity("Transaction processing issue: {$transId}, Error: " . $e->getMessage());
                continue;
            }
        }

        if ($transactionsProcessed > 0) {
            // Return success message as JSON
            $successMessage = "Success! Payment";
            
            if (count($successfulTransactions) === 1) {
                $tx = $successfulTransactions[0];
                $successMessage .= " of Kshs {$tx['amount']} from {$tx['phone']}";
            } else {
                $successMessage .= "s totaling Kshs " . array_sum(array_column($successfulTransactions, 'amount'));
            }
            
            echo json_encode([
                'success' => true,
                'msg' => "<br><div class='alert alert-success'>{$successMessage} has been processed.</div>"
            ]);
        } else {
            // No new transactions were processed (might be duplicates)
            echo json_encode([
                'error' => 'error',
                'msg' => "<br><div class='alert alert-info'>No new transactions found. If you've already paid, the payment might still be processing or was previously applied.</div>"
            ]);
        }
    } else {
        // No transactions or unsuccessful API response
        $errorMessage = $obj['message'] ?? 'Transaction verification failed.';
        echo json_encode([
            'error' => 'error',
            'msg' => "<br><div class='alert alert-danger'>Error! {$errorMessage}</div>"
        ]);
    }
} else {
    // Missing required session data
    echo json_encode([
        'error' => 'error',
        'msg' => "<br><div class='alert alert-danger'>Error! Missing session data.</div>"
    ]);
    mpesaLogActivity("Auto-validation error: Missing session data");
}
?>
