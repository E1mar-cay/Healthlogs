<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Transaction not found';
    header('Location: /HealthLogs/public/inventory/transactions/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Transaction deleted successfully';
} catch (Throwable $e) {
    error_log("Transaction delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the transaction. Please try again.';
}

header('Location: /HealthLogs/public/inventory/transactions/index.php');
exit;
