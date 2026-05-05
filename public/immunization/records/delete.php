<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Immunization record not found';
    header('Location: /HealthLogs/public/immunization/records/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM immunization_records WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Immunization record deleted successfully';
} catch (Throwable $e) {
    error_log("Immunization record delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the immunization record. Please try again.';
}

header('Location: /HealthLogs/public/immunization/records/index.php');
exit;
