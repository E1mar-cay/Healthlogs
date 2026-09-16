<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pregnancy_id = (int)($_POST['pregnancy_id'] ?? 0);
$visit_datetime = $_POST['visit_datetime'] ?? date('Y-m-d H:i:s');
$mother = $_POST['mother_condition'] ?? null;
$baby = $_POST['baby_condition'] ?? null;
$notes = $_POST['notes'] ?? null;
$next_appointment_date = trim($_POST['next_appointment_date'] ?? '');
$next_appointment_time = trim($_POST['next_appointment_time'] ?? '');

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE postnatal_visits SET pregnancy_id = ?, visit_datetime = ?, mother_condition = ?, baby_condition = ?, notes = ? WHERE id = ?");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes, $id]);
        $_SESSION['success_message'] = 'Postnatal visit updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO postnatal_visits (pregnancy_id, visit_datetime, mother_condition, baby_condition, notes) VALUES (?,?,?,?,?)");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes]);
        $_SESSION['success_message'] = 'Postnatal visit created successfully';
    }

    if ($next_appointment_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment_date)) {
        $patientStmt = $pdo->prepare("SELECT pr.patient_id, p.first_name, p.last_name FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id WHERE pr.id = ?");
        $patientStmt->execute([$pregnancy_id]);
        $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
        if ($patient) {
            $timeText = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $next_appointment_time) ? ' at ' . date('g:i A', strtotime($next_appointment_time)) : '';
            $message = "Postnatal follow-up reminder for {$patient['first_name']} {$patient['last_name']} on " . date('M d, Y', strtotime($next_appointment_date)) . $timeText . ".";
            $reminderStmt = $pdo->prepare("SELECT id FROM reminders WHERE patient_id = ? AND reminder_type = 'postnatal' AND due_date = ? LIMIT 1");
            $reminderStmt->execute([$patient['patient_id'], $next_appointment_date]);
            $reminderId = $reminderStmt->fetchColumn();
            if ($reminderId) {
                $updateReminder = $pdo->prepare("UPDATE reminders SET message = ?, status = 'pending', sent_at = NULL WHERE id = ?");
                $updateReminder->execute([$message, $reminderId]);
            } else {
                $insertReminder = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, 'postnatal', ?, ?, 'pending')");
                $insertReminder->execute([$patient['patient_id'], $next_appointment_date, $message]);
            }
        }
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Postnatal save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the postnatal visit. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/maternal/postnatal/form_embed.php?id=$id" : "/HealthLogs/public/maternal/postnatal/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/maternal/postnatal/form_embed.php" : "/HealthLogs/public/maternal/postnatal/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/maternal/postnatal/index.php');
exit;
