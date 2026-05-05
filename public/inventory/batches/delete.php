<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Batch not found';
    header('Location: /HealthLogs/public/inventory/batches/index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE batch_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id = ?");
    $stmt->execute([$id]);
    $pdo->commit();
    $_SESSION['success_message'] = 'Batch deleted successfully';
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Batch delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the batch. Please try again.';
}

header('Location: /HealthLogs/public/inventory/batches/index.php');
exit;
