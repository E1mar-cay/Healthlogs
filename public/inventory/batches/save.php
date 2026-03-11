<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$batch_no = $_POST['batch_no'] ?? '';
$expiry_date = $_POST['expiry_date'] ?? date('Y-m-d');
$received_date = $_POST['received_date'] ?? date('Y-m-d');
$qty = (int)($_POST['quantity_received'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare("UPDATE medicine_batches SET medicine_id = ?, batch_no = ?, expiry_date = ?, received_date = ?, quantity_received = ? WHERE id = ?");
    $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?,?,?,?,?)");
    $stmt->execute([$medicine_id, $batch_no, $expiry_date, $received_date, $qty]);
}

header('Location: /HealthLogs/public/inventory/batches/index.php');
exit;
