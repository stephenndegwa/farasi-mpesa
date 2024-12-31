<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $transactionRef = $_GET['transactionRef'] ?? '';

    if (empty($transactionRef)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction reference is required.']);
        exit;
    }

    try {
        $pdo = getDbConnection();

        $sql = "SELECT * FROM transactions WHERE BillRefNumber = :transactionRef";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':transactionRef', $transactionRef, PDO::PARAM_STR);
        $stmt->execute();

        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Transaction retrieved successfully.',
            'data' => $transaction
        ]);
    } catch (Exception $e) {
        error_log('Error fetching transaction: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
