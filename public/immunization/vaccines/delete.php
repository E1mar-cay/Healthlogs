<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: /HealthLogs/public/immunization/vaccines/index.php');
exit;
