<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Postnatal visit not found';
    header('Location: /HealthLogs/public/maternal/postnatal/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM postnatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Postnatal visit deleted successfully';
} catch (Throwable $e) {
    error_log("Postnatal delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the postnatal visit. Please try again.';
}

header('Location: /HealthLogs/public/maternal/postnatal/index.php');
exit;
