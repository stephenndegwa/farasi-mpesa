<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $transactionRef = $_GET['transactionRef'] ?? '';

    if (empty($transactionRef)) {
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
            echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transaction retrieved successfully.',
            'data' => $transaction
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred.', 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>