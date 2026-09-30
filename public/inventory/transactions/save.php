<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$batch_id = (!empty($_POST['batch_id'])) ? (int)$_POST['batch_id'] : null;
$type = $_POST['transaction_type'] ?? 'received';
$rawQuantity = abs((int)($_POST['quantity'] ?? 0));
$adjustmentMode = $_POST['adjustment_mode'] ?? 'increase';
$dt = $_POST['transaction_datetime'] ?? date('Y-m-d H:i:s');
$reference = !empty($_POST['reference']) ? trim($_POST['reference']) : null;
$notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
$normalizedDateTime = str_replace('T', ' ', $dt);
$userId = $_SESSION['user_id'] ?? null;

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

$med = ActivityLogger::getMedicineInfo($medicine_id);
$medName = $med['name'] ?? "Medicine #{$medicine_id}";
$unit = $med['unit'] ?? 'units';
$batchInfo = $batch_id ? ActivityLogger::getBatchInfo($batch_id) : null;
$batchNo = $batchInfo['batch_no'] ?? null;
$oldStock = ActivityLogger::getMedicineStock($medicine_id);

// ENFORCE: Prevent expired medicines from being issued/dispensed
if ($type === 'dispensed') {
    if ($batch_id) {
        $batchCheckStmt = $pdo->prepare("SELECT batch_no, expiry_date FROM medicine_batches WHERE id = ?");
        $batchCheckStmt->execute([$batch_id]);
        $batchRow = $batchCheckStmt->fetch(PDO::FETCH_ASSOC);

        if ($batchRow && strtotime($batchRow['expiry_date']) < strtotime(date('Y-m-d'))) {
            $_SESSION['error_message'] = "Issuance Blocked: Batch '{$batchRow['batch_no']}' has expired on {$batchRow['expiry_date']}. Expired medicines are barred from issuance.";
            $_SESSION['old_input'] = $_POST;
            $redirectUrl = $isEdit
                ? ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php?id=$id" : "/HealthLogs/public/inventory/transactions/form.php?id=$id")
                : ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php" : "/HealthLogs/public/inventory/transactions/form.php");
            header("Location: $redirectUrl");
            exit;
        }
    }

    // Verify non-expired available stock
    $availableNonExpired = ActivityLogger::getAvailableNonExpiredStock($medicine_id);
    if ($rawQuantity > $availableNonExpired) {
        $_SESSION['error_message'] = "Issuance Blocked: Requested quantity ({$rawQuantity}) exceeds available non-expired stock ({$availableNonExpired}). Expired batches are excluded from available inventory.";
        $_SESSION['old_input'] = $_POST;
        $redirectUrl = $isEdit
            ? ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php?id=$id" : "/HealthLogs/public/inventory/transactions/form.php?id=$id")
            : ($isEmbed ? "/HealthLogs/public/inventory/transactions/form_embed.php" : "/HealthLogs/public/inventory/transactions/form.php");
        header("Location: $redirectUrl");
        exit;
    }
}

try {
    if ($id) {
        // Fetch old transaction for delta tracking
        $oldTxStmt = $pdo->prepare("SELECT * FROM medicine_transactions WHERE id = ?");
        $oldTxStmt->execute([$id]);
        $oldTx = $oldTxStmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE medicine_transactions SET medicine_id = ?, batch_id = ?, transaction_type = ?, quantity = ?, transaction_datetime = ?, reference = ?, notes = ?, recorded_by = ? WHERE id = ?");
        $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes, $userId, $id]);

        $newStock = ActivityLogger::getMedicineStock($medicine_id);

        $desc = "Updated transaction #{$id} for {$medName} (" . ($quantity >= 0 ? "+{$quantity}" : "{$quantity}") . " {$unit})";
        if ($reference) {
            $desc .= " [Ref: {$reference}]";
        }

        ActivityLogger::logInventory('stock_update', $desc, 'medicine_transactions', (string)$id, [
            'transaction_id' => $id,
            'medicine_id' => $medicine_id,
            'medicine_name' => $medName,
            'unit' => $unit,
            'batch_id' => $batch_id,
            'batch_no' => $batchNo,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'abs_quantity' => $rawQuantity,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'old_transaction' => $oldTx,
            'reference' => $reference,
            'notes' => $notes,
            'transaction_datetime' => $normalizedDateTime,
            'recorded_by' => $userId
        ]);

        $_SESSION['success_message'] = 'Transaction updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$medicine_id, $batch_id, $type, $quantity, $normalizedDateTime, $reference, $notes, $userId]);
        $newTxId = (int)$pdo->lastInsertId();

        $newStock = $oldStock + $quantity;

        // Determine specific audit action and description
        $action = 'stock_receive';
        $actionLabel = 'Stock received';
        if ($type === 'dispensed') {
            $action = 'issuance';
            $actionLabel = 'Stock released/issued';
        } elseif ($type === 'returned') {
            $action = 'return';
            $actionLabel = 'Stock returned';
        } elseif ($type === 'adjustment') {
            $action = 'adjustment';
            $actionLabel = 'Inventory adjustment';
        } elseif ($type === 'expired') {
            $action = 'adjustment';
            $actionLabel = 'Expired stock written off';
        }

        $desc = "{$actionLabel}: " . abs($quantity) . " {$unit} of {$medName}";
        if ($reference) {
            $desc .= " (Ref: {$reference})";
        }
        if ($batchNo) {
            $desc .= " [Batch {$batchNo}]";
        }

        ActivityLogger::logInventory($action, $desc, 'medicine_transactions', (string)$newTxId, [
            'transaction_id' => $newTxId,
            'medicine_id' => $medicine_id,
            'medicine_name' => $medName,
            'unit' => $unit,
            'batch_id' => $batch_id,
            'batch_no' => $batchNo,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'abs_quantity' => $rawQuantity,
            'old_stock' => $oldStock,
            'new_stock' => $newStock,
            'reference' => $reference,
            'notes' => $notes,
            'transaction_datetime' => $normalizedDateTime,
            'recorded_by' => $userId
        ]);

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
