<?php
$responseMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $consumerKey = $_POST['consumerKey'];
    $consumerSecret = $_POST['consumerSecret'];
    $shortCode = $_POST['shortCode'];
    $responseUrl = $_POST['responseUrl'];
    $confirmationUrl = $_POST['confirmationUrl'];

    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $curl_response = curl_exec($curl);

    $result = json_decode($curl_response);

    $access_token = $result->access_token;

    $registerUrl = 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $registerUrl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token));

    $curl_post_data = array(
        'ShortCode' => $shortCode,
        'ResponseType' => 'Completed',
        'ConfirmationURL' => $confirmationUrl,
        'ValidationURL' => $responseUrl
    );

    $data_string = json_encode($curl_post_data);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

    $curl_response = curl_exec($curl);

    $responseMessage = $curl_response;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register M-Pesa URL</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto mt-10 bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6">Register M-Pesa URL</h2>
        <form method="POST" action="registerurls.php" class="space-y-4">
            <div>
                <label for="consumerKey" class="block text-sm font-medium text-gray-700">Consumer Key:</label>
                <input type="text" id="consumerKey" name="consumerKey" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <label for="consumerSecret" class="block text-sm font-medium text-gray-700">Consumer Secret:</label>
                <input type="text" id="consumerSecret" name="consumerSecret" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <label for="shortCode" class="block text-sm font-medium text-gray-700">Short Code:</label>
                <input type="text" id="shortCode" name="shortCode" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <label for="responseUrl" class="block text-sm font-medium text-gray-700">Response URL:</label>
                <input type="url" id="responseUrl" name="responseUrl" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <label for="confirmationUrl" class="block text-sm font-medium text-gray-700">Confirmation URL:</label>
                <input type="url" id="confirmationUrl" name="confirmationUrl" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <input type="submit" value="Register URL" class="w-full bg-indigo-600 text-white p-2 rounded-md shadow-md hover:bg-indigo-700">
            </div>
        </form>
        <?php if ($responseMessage): ?>
            <div class="mt-6 bg-gray-100 p-4 rounded-lg shadow-md">
                <h3 class="text-lg font-bold">Response:</h3>
                <pre class="text-sm text-gray-700"><?php echo htmlspecialchars($responseMessage, ENT_QUOTES, 'UTF-8'); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>