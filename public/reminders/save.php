<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$type = $_POST['reminder_type'] ?? 'immunization';
$due = $_POST['due_date'] ?? date('Y-m-d');
$message = $_POST['message'] ?? '';
$status = $_POST['status'] ?? 'pending';

if ($id) {
    $stmt = $pdo->prepare("UPDATE reminders SET patient_id = ?, reminder_type = ?, due_date = ?, message = ?, status = ? WHERE id = ?");
    $stmt->execute([$patient_id, $type, $due, $message, $status, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$patient_id, $type, $due, $message, $status]);
}

header('Location: /HealthLogs/public/reminders.php');
exit;
