<?php
require __DIR__ . '/../../partials/bootstrap.php';

$medicine_id = isset($_POST['medicine_id']) ? (int)$_POST['medicine_id'] : 0;

if (!$medicine_id) {
    $_SESSION['error_message'] = 'Medicine not found';
    header('Location: /HealthLogs/public/inventory/medicines/index.php');
    exit;
}

try {
    // Get all expired batches for this medicine
    $stmt = $pdo->prepare("
        SELECT mb.id FROM medicine_batches mb
        WHERE mb.medicine_id = ? AND mb.expiry_date < CURDATE()
    ");
    $stmt->execute([$medicine_id]);
    $expiredBatches = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($expiredBatches)) {
        $_SESSION['info_message'] = 'No expired batches found for this medicine';
        header('Location: /HealthLogs/public/inventory/medicines/index.php');
        exit;
    }

    // Delete transactions for expired batches
    $batchPlaceholders = implode(',', array_fill(0, count($expiredBatches), '?'));
    $deleteTransStmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE batch_id IN ($batchPlaceholders)");
    $deleteTransStmt->execute($expiredBatches);

    // Delete expired batches
    $deleteBatchStmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id IN ($batchPlaceholders)");
    $deleteBatchStmt->execute($expiredBatches);

    $_SESSION['success_message'] = 'Expired batches removed successfully from inventory';
} catch (Throwable $e) {
    error_log("Remove expired error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while removing expired batches. Please try again.';
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
