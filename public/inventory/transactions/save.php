<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$batch_id = $_POST['batch_id'] !== '' ? (int)$_POST['batch_id'] : null;
$type = $_POST['transaction_type'] ?? 'received';
$quantity = (int)($_POST['quantity'] ?? 0);
$dt = $_POST['transaction_datetime'] ?? date('Y-m-d H:i:s');
$reference = $_POST['reference'] ?? null;
$notes = $_POST['notes'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, batch_id = ?, transaction_type = ?, quantity = ?, transaction_datetime = ?, reference = ?, notes = ? WHERE id = ?");
    $stmt->execute([$medicine_id, $batch_id, $type, $quantity, str_replace('T', ' ', $dt), $reference, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$medicine_id, $batch_id, $type, $quantity, str_replace('T', ' ', $dt), $reference, $notes]);
}

header('Location: /HealthLogs/public/inventory/transactions/index.php');
exit;
