<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE reminders SET status = 'pending' WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = "Reminder marked as Pending. It will now be sent by the scheduler!";
}

header('Location: /HealthLogs/public/reminders.php');
exit;
