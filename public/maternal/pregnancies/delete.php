<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Pregnancy record not found';
    header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM pregnancies WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Pregnancy deleted successfully';
} catch (Throwable $e) {
    error_log("Pregnancy delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the pregnancy. Please try again.';
}

header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
exit;
