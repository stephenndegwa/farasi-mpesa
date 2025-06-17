<?php
require_once 'db.php';

/**
 * Standard API Response
 * 
 * @param int $statusCode HTTP status code
 * @param bool $success Whether the request was successful
 * @param string $message Message to describe the result
 * @param array $data Optional data payload
 * @param array $meta Optional metadata (pagination, etc.)
 * @return string JSON encoded response
 */
function apiResponse($statusCode, $success, $message, $data = null, $meta = null) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    
    $response = [
        'status' => $statusCode,
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($meta !== null) {
        $response['meta'] = $meta;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $transactionRef = $_GET['transactionRef'] ?? null;
        $transID = $_GET['TransID'] ?? null;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;

        if (empty($transactionRef) && empty($transID)) {
            apiResponse(400, false, 'Either transactionRef or TransID is required.');
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

        // Count total matching records for pagination
        $countSql = "SELECT COUNT(*) FROM transactions WHERE " . implode(' AND ', $conditions);
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        // Add pagination to the main query
        $sql = "SELECT * FROM transactions WHERE " . implode(' AND ', $conditions) . 
               " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        // Bind the pagination parameters
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Bind the search parameters
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($transactions)) {
            apiResponse(404, false, 'No transactions found.');
        }

        $totalPages = ceil($totalRecords / $limit);
        
        $meta = [
            'pagination' => [
                'total' => $totalRecords,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => ($page < $totalPages)
            ]
        ];

        apiResponse(200, true, 'Transactions retrieved successfully.', $transactions, $meta);

    } else {
        apiResponse(405, false, 'Method not allowed. This endpoint only supports GET requests.');
    }

} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    apiResponse(500, false, 'An internal server error occurred.', null, ['error_details' => $e->getMessage()]);
}
?>
