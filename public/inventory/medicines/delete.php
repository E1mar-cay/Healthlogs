<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
