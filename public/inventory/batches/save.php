<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$batch_no = trim((string)($_POST['batch_no'] ?? ''));
$expiry_date = $_POST['expiry_date'] ?? date('Y-m-d');
$received_date = $_POST['received_date'] ?? date('Y-m-d');
$qty = (int)($_POST['quantity_received'] ?? 0);
$userId = $_SESSION['user_id'] ?? null;

if (!preg_match('/^\d{3}$/', $batch_no)) {
    $batch_no = '001';
}

$med = ActivityLogger::getMedicineInfo($medicine_id);
$medName = $med['name'] ?? "Medicine #{$medicine_id}";
$unit = $med['unit'] ?? 'units';
$oldStock = ActivityLogger::getMedicineStock($medicine_id);

$pdo->beginTransaction();

try {
    if ($id) {
        $oldBatch = ActivityLogger::getBatchInfo($id);

        $stmt = $pdo->prepare("UPDATE medicine_batches SET medicine_id = ?, batch_no = ?, expiry_date = ?, received_date = ?, quantity_received = ? WHERE id = ?");
        $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty, $id]);

        $txStmt = $pdo->prepare("SELECT id, quantity FROM medicine_transactions WHERE batch_id = ? AND transaction_type = 'received' ORDER BY id ASC LIMIT 1");
        $txStmt->execute([$id]);
        $existingTx = $txStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTx) {
            $updateTx = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, transaction_datetime = ?, quantity = ?, reference = ?, recorded_by = ? WHERE id = ?");
            $updateTx->execute([$medicine_id, $received_date . ' 09:00:00', $qty, 'BATCH-' . $batch_no, $userId, $existingTx['id']]);
        } else {
            $insertTx = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
            $insertTx->execute([$medicine_id, $id, 'received', $qty, $received_date . ' 09:00:00', 'BATCH-' . $batch_no, 'Opening stock from batch entry', $userId]);
        }

        $newStock = ActivityLogger::getMedicineStock($medicine_id);

        ActivityLogger::logInventory('stock_update', "Updated batch {$batch_no} for {$medName} (Qty: {$qty} {$unit})", 'medicine_batches', (string)$id, [
            'batch_id' => $id,
            'batch_no' => $batch_no,
            'medicine_id' => $medicine_id,
            'medicine_name' => $medName,
            'expiry_date' => $expiry_date,
            'received_date' => $received_date,
            'quantity_received' => $qty,
            'old_batch' => $oldBatch,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'unit' => $unit
        ]);

        $_SESSION['success_message'] = 'Batch updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?,?,?,?,?)");
        $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty]);
        $batchId = (int)$pdo->lastInsertId();

        $insertTx = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
        $insertTx->execute([$medicine_id, $batchId, 'received', $qty, $received_date . ' 09:00:00', 'BATCH-' . $batch_no, 'Opening stock from batch entry', $userId]);
        $txId = (int)$pdo->lastInsertId();

        $newStock = $oldStock + $qty;

        ActivityLogger::logInventory('stock_receive', "Received batch {$batch_no} (+{$qty} {$unit}) for {$medName}", 'medicine_batches', (string)$batchId, [
            'batch_id' => $batchId,
            'batch_no' => $batch_no,
            'transaction_id' => $txId,
            'medicine_id' => $medicine_id,
            'medicine_name' => $medName,
            'expiry_date' => $expiry_date,
            'received_date' => $received_date,
            'quantity' => $qty,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'reference' => 'BATCH-' . $batch_no,
            'unit' => $unit
        ]);

        $_SESSION['success_message'] = 'Batch created successfully';
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Batch save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the batch. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/inventory/batches/form_embed.php?id=$id" : "/HealthLogs/public/inventory/batches/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/inventory/batches/form_embed.php" : "/HealthLogs/public/inventory/batches/form.php");
    header("Location: $redirectUrl");
    exit;
}

unset($_SESSION['old_input']);
header('Location: /HealthLogs/public/inventory/batches/index.php');
exit;
