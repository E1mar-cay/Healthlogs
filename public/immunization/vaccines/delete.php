<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Vaccine not found';
    header('Location: /HealthLogs/public/immunization/vaccines/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM vaccines WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Vaccine deleted successfully';
} catch (Throwable $e) {
    error_log("Vaccine delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the vaccine. Please try again.';
}

header('Location: /HealthLogs/public/immunization/vaccines/index.php');
exit;
