<?php
// public/test_db.php - Diagnostic database connection test endpoint
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

try {
    // Check tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check users
    $userCount = 0;
    if (in_array('users', $tables, true)) {
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Database successfully connected!',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'database' => getenv('DB_NAME') ?: 'healthlogs',
        'total_tables' => count($tables),
        'tables' => $tables,
        'users_in_db' => $userCount,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection or query failed',
        'error_details' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
