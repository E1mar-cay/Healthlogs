<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Reminder not found';
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM reminders WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Reminder deleted successfully';
} catch (Exception $e) {
    error_log("Reminder delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the reminder. Please try again.';
}

header('Location: /HealthLogs/public/reminders.php');
exit;
