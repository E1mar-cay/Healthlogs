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

if (!preg_match('/^\d{3}$/', $batch_no)) {
    $batch_no = '001';
}

$pdo->beginTransaction();

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE medicine_batches SET medicine_id = ?, batch_no = ?, expiry_date = ?, received_date = ?, quantity_received = ? WHERE id = ?");
        $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty, $id]);

        $txStmt = $pdo->prepare("SELECT id FROM medicine_transactions WHERE batch_id = ? AND transaction_type = 'received' ORDER BY id ASC LIMIT 1");
        $txStmt->execute([$id]);
        $transactionId = $txStmt->fetchColumn();

        if ($transactionId) {
            $updateTx = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, transaction_datetime = ?, quantity = ?, reference = ? WHERE id = ?");
            $updateTx->execute([$medicine_id, $received_date . ' 09:00:00', $qty, 'BATCH-' . $batch_no, $transactionId]);
        } else {
            $insertTx = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
            $insertTx->execute([$medicine_id, $id, 'received', $qty, $received_date . ' 09:00:00', 'BATCH-' . $batch_no, 'Opening stock from batch entry']);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?,?,?,?,?)");
        $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty]);
        $batchId = (int)$pdo->lastInsertId();

        $insertTx = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
        $insertTx->execute([$medicine_id, $batchId, 'received', $qty, $received_date . ' 09:00:00', 'BATCH-' . $batch_no, 'Opening stock from batch entry']);
    }

    $pdo->commit();
    $_SESSION['success_message'] = $id ? 'Batch updated successfully' : 'Batch created successfully';
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
