<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Transaction not found';
    header('Location: /HealthLogs/public/inventory/transactions/index.php');
    exit;
}

try {
    $txStmt = $pdo->prepare("SELECT t.*, m.name AS medicine_name, m.unit FROM medicine_transactions t JOIN medicines m ON m.id = t.medicine_id WHERE t.id = ?");
    $txStmt->execute([$id]);
    $tx = $txStmt->fetch(PDO::FETCH_ASSOC);

    $medId = (int)($tx['medicine_id'] ?? 0);
    $medName = $tx['medicine_name'] ?? 'Unknown Medicine';
    $unit = $tx['unit'] ?? 'units';
    $oldStock = ActivityLogger::getMedicineStock($medId);

    $stmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE id = ?");
    $stmt->execute([$id]);

    $newStock = ActivityLogger::getMedicineStock($medId);
    $qty = (int)($tx['quantity'] ?? 0);

    ActivityLogger::logInventory('transaction_delete', "Deleted inventory transaction #{$id} for {$medName} (Reversal: " . (-$qty >= 0 ? "+".(-$qty) : (-$qty)) . " {$unit})", 'medicine_transactions', (string)$id, [
        'transaction_id' => $id,
        'medicine_id' => $medId,
        'medicine_name' => $medName,
        'unit' => $unit,
        'deleted_transaction' => $tx,
        'old_stock' => $oldStock,
        'new_stock' => $newStock
    ]);

    $_SESSION['success_message'] = 'Transaction deleted successfully';
} catch (Throwable $e) {
    error_log("Transaction delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the transaction. Please try again.';
}

header('Location: /HealthLogs/public/inventory/transactions/index.php');
exit;
