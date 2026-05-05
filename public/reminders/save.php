<?php
require __DIR__ . '/../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$type = $_POST['reminder_type'] ?? 'immunization';
$due = $_POST['due_date'] ?? date('Y-m-d');
$message = trim($_POST['message'] ?? '');
$status = $_POST['status'] ?? 'pending';

$rules = [
    'patient_id' => 'required|numeric',
    'reminder_type' => 'required|max:50',
    'due_date' => 'required',
    'message' => 'required|max:1000',
    'status' => 'required|max:50',
];

$validator = new Validator($_POST, $rules);

if ($validator->hasErrors()) {
    $_SESSION['validation_errors'] = $validator->getErrors();
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/reminders/form_embed.php?id=$id" : "/HealthLogs/public/reminders/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/reminders/form_embed.php" : "/HealthLogs/public/reminders/form.php");
    header("Location: $redirectUrl");
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE reminders SET patient_id = ?, reminder_type = ?, due_date = ?, message = ?, status = ? WHERE id = ?");
        $stmt->execute([$patient_id, $type, $due, $message, $status, $id]);
        $_SESSION['success_message'] = 'Reminder updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?,?,?,?,?)");
        $stmt->execute([$patient_id, $type, $due, $message, $status]);
        $_SESSION['success_message'] = 'Reminder created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Exception $e) {
    error_log("Reminder save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the reminder. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/reminders/form_embed.php?id=$id" : "/HealthLogs/public/reminders/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/reminders/form_embed.php" : "/HealthLogs/public/reminders/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/reminders.php');
exit;
