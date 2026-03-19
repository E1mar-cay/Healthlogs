<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$batch_id = $_POST['batch_id'] !== '' ? (int)$_POST['batch_id'] : null;
$type = $_POST['transaction_type'] ?? 'received';
$rawQuantity = abs((int)($_POST['quantity'] ?? 0));
$adjustmentMode = $_POST['adjustment_mode'] ?? 'increase';
$dt = $_POST['transaction_datetime'] ?? date('Y-m-d H:i:s');
$reference = $_POST['reference'] ?? null;
$notes = $_POST['notes'] ?? null;
$normalizedDateTime = str_replace('T', ' ', $dt);

$outgoingTypes = ['dispensed', 'expired'];
$incomingTypes = ['received', 'returned'];
$quantity = $rawQuantity;

if (in_array($type, $outgoingTypes, true)) {
    $quantity = -$rawQuantity;
} elseif (in_array($type, $incomingTypes, true)) {
    $quantity = $rawQuantity;
} elseif ($type === 'adjustment') {
    $quantity = $adjustmentMode === 'decrease' ? -$rawQuantity : $rawQuantity;
}

if ($id) {
    $stmt = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, batch_id = ?, transaction_type = ?, quantity = ?, transaction_datetime = ?, reference = ?, notes = ? WHERE id = ?");
    $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes]);
}

header('Location: /HealthLogs/public/inventory/transactions/index.php');
exit;
