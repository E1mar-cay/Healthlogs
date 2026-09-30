<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Batch not found';
    header('Location: /HealthLogs/public/inventory/batches/index.php');
    exit;
}

try {
    $batchInfo = ActivityLogger::getBatchInfo($id);
    $batchNo = $batchInfo['batch_no'] ?? "ID #{$id}";
    $medName = $batchInfo['medicine_name'] ?? 'Unknown Medicine';
    $medId = (int)($batchInfo['medicine_id'] ?? 0);
    $oldStock = ActivityLogger::getMedicineStock($medId);

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE batch_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id = ?");
    $stmt->execute([$id]);
    $pdo->commit();

    $newStock = ActivityLogger::getMedicineStock($medId);

    ActivityLogger::logInventory('batch_delete', "Deleted batch {$batchNo} of {$medName}", 'medicine_batches', (string)$id, [
        'batch_id' => $id,
        'batch_no' => $batchNo,
        'medicine_id' => $medId,
        'medicine_name' => $medName,
        'deleted_batch' => $batchInfo,
        'old_stock' => $oldStock,
        'new_stock' => $newStock
    ]);

    $_SESSION['success_message'] = 'Batch deleted successfully';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Batch delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the batch. Please try again.';
}

header('Location: /HealthLogs/public/inventory/batches/index.php');
exit;
