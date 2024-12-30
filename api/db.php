<?php
function getDbConnection()
{
    // Load configuration from config.php
    $config = include('config.php');
    $db = $config['db'];

    // Create a new PDO instance
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};dbname={$db['dbname']}",
            $db['user'],
            $db['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Log and rethrow the error for further debugging
        error_log('Database connection error: ' . $e->getMessage());
        throw new Exception('Failed to connect to the database. Please check the configuration.');
    }
}
?>
