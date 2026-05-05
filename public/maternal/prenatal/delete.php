<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Prenatal visit not found';
    header('Location: /HealthLogs/public/maternal/prenatal/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM prenatal_visits WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Prenatal visit deleted successfully';
} catch (Throwable $e) {
    error_log("Prenatal delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the prenatal visit. Please try again.';
}

header('Location: /HealthLogs/public/maternal/prenatal/index.php');
exit;
