<?php
$responseMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shortCode = $_POST['shortCode'] ?? '';
    $confirmationUrl = $_POST['confirmationUrl'] ?? '';
    $validationUrl = $_POST['validationUrl'] ?? '';
    $consumerKey = $_POST['consumerKey'] ?? '';
    $consumerSecret = $_POST['consumerSecret'] ?? '';

    try {
        $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $auth = base64_encode($consumerKey . ':' . $consumerSecret);
        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Basic $auth\r\n",
                'method' => 'GET'
            ]
        ]);
        $response = file_get_contents($url, false, $context);
        $accessToken = json_decode($response, true)['access_token'];

        $registerData = [
            'ShortCode' => $shortCode,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ];

        $options = [
            'http' => [
                'header' => "Authorization: Bearer $accessToken\r\nContent-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($registerData)
            ]
        ];

        $context = stream_context_create($options);
        $registerResponse = file_get_contents('https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl', false, $context);
        $result = json_decode($registerResponse, true);

        $responseMessage = json_encode(['success' => true, 'response' => $result]);
    } catch (Exception $e) {
        $responseMessage = json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Register M-Pesa URLs</title>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form action="registerurls.php" method="POST" class="bg-white p-6 rounded-lg shadow-md w-96">
        <h2 class="text-lg font-bold mb-4">Register M-Pesa URLs</h2>
        <label class="block mb-2">Short Code</label>
        <input type="text" name="shortCode" class="w-full mb-4 px-3 py-2 border rounded-lg" required>

        <label class="block mb-2">Confirmation URL</label>
        <input type="url" name="confirmationUrl" class="w-full mb-4 px-3 py-2 border rounded-lg" required>

        <label class="block mb-2">Validation URL</label>
        <input type="url" name="validationUrl" class="w-full mb-4 px-3 py-2 border rounded-lg" required>

        <label class="block mb-2">Consumer Key</label>
        <input type="text" name="consumerKey" class="w-full mb-4 px-3 py-2 border rounded-lg" required>

        <label class="block mb-2">Consumer Secret</label>
        <input type="text" name="consumerSecret" class="w-full mb-4 px-3 py-2 border rounded-lg" required>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Register</button>
    </form>
    <?php if ($responseMessage): ?>
        <div class="mt-4 p-4 bg-gray-200 rounded-lg">
            <pre><?php echo $responseMessage; ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>