<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
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

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, batch_id = ?, transaction_type = ?, quantity = ?, transaction_datetime = ?, reference = ?, notes = ? WHERE id = ?");
        $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes, $id]);
        $_SESSION['success_message'] = 'Transaction updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes]);
        $_SESSION['success_message'] = 'Transaction created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Transaction save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the transaction. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php?id=$id" : "/HealthLogs/public/inventory/transactions/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php" : "/HealthLogs/public/inventory/transactions/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/inventory/transactions/index.php');
exit;
