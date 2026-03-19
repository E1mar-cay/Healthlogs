<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM medicine_transactions WHERE batch_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM medicine_batches WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

header('Location: /HealthLogs/public/inventory/batches/index.php');
exit;
