<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Medicine not found';
    header('Location: /HealthLogs/public/inventory/medicines/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Medicine deleted successfully';
} catch (Throwable $e) {
    error_log("Medicine delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the medicine. Please try again.';
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
