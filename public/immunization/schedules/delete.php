<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Schedule not found';
    header('Location: /HealthLogs/public/immunization/schedules/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM immunization_schedule WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Schedule deleted successfully';
} catch (Throwable $e) {
    error_log("Immunization schedule delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the schedule. Please try again.';
}

header('Location: /HealthLogs/public/immunization/schedules/index.php');
exit;
