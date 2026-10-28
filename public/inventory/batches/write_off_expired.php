<?php
require __DIR__ . '/../../partials/bootstrap.php';
require_once __DIR__ . '/../../../app/Core/ActivityLogger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/inventory/batches/index.php');
    exit;
}

if (!can_manage_clinical_records()) {
    $_SESSION['error_message'] = 'Unauthorized access.';
    header('Location: /HealthLogs/public/inventory/batches/index.php');
    exit;
}

$batchId = (int)($_POST['batch_id'] ?? 0);
if ($batchId <= 0) {
    $_SESSION['error_message'] = 'Invalid batch selected.';
    header('Location: /HealthLogs/public/inventory/batches/index.php');
    exit;
}

try {
    $batchStmt = $pdo->prepare("
        SELECT b.*, m.name AS medicine_name, m.unit,
               COALESCE(SUM(t.quantity), 0) AS on_hand
        FROM medicine_batches b
        JOIN medicines m ON m.id = b.medicine_id
        LEFT JOIN medicine_transactions t ON t.batch_id = b.id
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $batchStmt->execute([$batchId]);
    $batch = $batchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        $_SESSION['error_message'] = 'Batch not found.';
        header('Location: /HealthLogs/public/inventory/batches/index.php');
        exit;
    }

    $isExpired = strtotime($batch['expiry_date']) < strtotime(date('Y-m-d'));
    if (!$isExpired) {
        $_SESSION['error_message'] = 'Only batches that have passed their expiration date can be written off as expired.';
        header('Location: /HealthLogs/public/inventory/batches/index.php');
        exit;
    }

    $onHand = (int)$batch['on_hand'];
    if ($onHand <= 0) {
        $_SESSION['error_message'] = 'This batch has zero stock remaining.';
        header('Location: /HealthLogs/public/inventory/batches/index.php');
        exit;
    }

    // Insert negative transaction to completely write off expired stock
    $deductQty = -$onHand;
    $ref = 'EXP-DISPOSAL-' . date('Ymd');
    $notes = 'Formal quarantine, write-off, and disposal of expired medicine batch.';
    $userId = $_SESSION['user_id'] ?? null;

    $insTx = $pdo->prepare("
        INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by)
        VALUES (?, ?, 'expired', ?, NOW(), ?, ?, ?)
    ");
    $insTx->execute([$batch['medicine_id'], $batchId, $deductQty, $ref, $notes, $userId]);
    $txId = (int)$pdo->lastInsertId();

    // Audit log
    if (class_exists('ActivityLogger')) {
        ActivityLogger::init($pdo);
        ActivityLogger::logInventory('stock_disposal', "Disposed & written off {$onHand} {$batch['unit']} of expired {$batch['medicine_name']} (Batch {$batch['batch_no']})", 'medicine_batches', (string)$batchId, [
            'batch_id' => $batchId,
            'batch_no' => $batch['batch_no'],
            'medicine_id' => $batch['medicine_id'],
            'quantity_disposed' => $onHand,
            'transaction_id' => $txId
        ]);
    }

    $_SESSION['success_message'] = "Expired batch '{$batch['batch_no']}' ({$onHand} {$batch['unit']}) has been successfully written off and removed from physical inventory.";
} catch (Throwable $e) {
    $_SESSION['error_message'] = 'Failed to write off expired batch: ' . $e->getMessage();
}

$redirect = $_SERVER['HTTP_REFERER'] ?? '/HealthLogs/public/inventory/batches/index.php';
header("Location: $redirect");
exit;
