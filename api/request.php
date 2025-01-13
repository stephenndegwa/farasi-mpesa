<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $transactionRef = $_GET['transactionRef'] ?? null;
        $transID = $_GET['TransID'] ?? null;

        if (empty($transactionRef) && empty($transID)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Either transactionRef or TransID is required.']);
            exit;
        }

        // Build query based on available parameters
        $conditions = [];
        $params = [];

        if ($transactionRef) {
            $conditions[] = 'BillRefNumber = :transactionRef';
            $params[':transactionRef'] = $transactionRef;
        }

        if ($transID) {
            $conditions[] = 'TransID = :TransID';
            $params[':TransID'] = $transID;
        }

        $sql = "SELECT * FROM transactions WHERE " . implode(' AND ', $conditions);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($transactions)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No transactions found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Transactions retrieved successfully.',
            'data' => $transactions
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }

} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
?>
