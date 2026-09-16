<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);
$scheduled_date = $_POST['scheduled_date'] ?? date('Y-m-d');
$scheduled_time = trim($_POST['scheduled_time'] ?? '');
$status = $_POST['status'] ?? 'scheduled';

try {
    $previousSchedule = null;
    if ($id) {
        $previousStmt = $pdo->prepare("SELECT patient_id, scheduled_date FROM immunization_schedule WHERE id = ?");
        $previousStmt->execute([$id]);
        $previousSchedule = $previousStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $vaccineStmt = $pdo->prepare("SELECT name FROM vaccines WHERE id = ?");
    $vaccineStmt->execute([$vaccine_id]);
    $vaccineName = (string)$vaccineStmt->fetchColumn();

    $patientStmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE id = ?");
    $patientStmt->execute([$patient_id]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient || $vaccineName === '') {
        throw new RuntimeException('The selected patient or vaccine could not be found.');
    }

    $pdo->beginTransaction();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE immunization_schedule SET patient_id = ?, vaccine_id = ?, dose_no = ?, scheduled_date = ?, status = ? WHERE id = ?");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status, $id]);
        $_SESSION['success_message'] = 'Schedule updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?,?,?,?,?)");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status]);
        $_SESSION['success_message'] = 'Schedule created successfully';
    }

    $timeText = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $scheduled_time) ? ' at ' . date('g:i A', strtotime($scheduled_time)) : '';
    $reminderMessage = "Reminder: {$vaccineName} dose {$dose_no} scheduled for {$patient['first_name']} {$patient['last_name']} on " . date('M d, Y', strtotime($scheduled_date)) . $timeText . ".";

    if ($status === 'scheduled') {
        $reminderStmt = $pdo->prepare("SELECT id FROM reminders WHERE patient_id = ? AND reminder_type = 'immunization' AND due_date = ? LIMIT 1");
        $reminderStmt->execute([$patient_id, $scheduled_date]);
        $reminderId = $reminderStmt->fetchColumn();

        if ($reminderId) {
            $updateReminder = $pdo->prepare("UPDATE reminders SET message = ?, status = 'pending', sent_at = NULL WHERE id = ?");
            $updateReminder->execute([$reminderMessage, $reminderId]);
        } else {
            $insertReminder = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, 'immunization', ?, ?, 'pending')");
            $insertReminder->execute([$patient_id, $scheduled_date, $reminderMessage]);
        }
    }

    $pairsToReconcile = [];
    if ($previousSchedule) {
        $pairsToReconcile[] = [(int)$previousSchedule['patient_id'], $previousSchedule['scheduled_date']];
    }
    if ($status !== 'scheduled') {
        $pairsToReconcile[] = [$patient_id, $scheduled_date];
    }

    foreach ($pairsToReconcile as [$reconcilePatientId, $reconcileDate]) {
        if ($reconcilePatientId === $patient_id && $reconcileDate === $scheduled_date && $status === 'scheduled') {
            continue;
        }

        $scheduledCount = $pdo->prepare("SELECT COUNT(*) FROM immunization_schedule WHERE patient_id = ? AND scheduled_date = ? AND status = 'scheduled'");
        $scheduledCount->execute([$reconcilePatientId, $reconcileDate]);
        if ((int)$scheduledCount->fetchColumn() === 0) {
            $cancelReminder = $pdo->prepare("UPDATE reminders SET status = 'cancelled' WHERE patient_id = ? AND reminder_type = 'immunization' AND due_date = ? AND status IN ('pending', 'failed')");
            $cancelReminder->execute([$reconcilePatientId, $reconcileDate]);
        }
    }

    $pdo->commit();
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Immunization schedule save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the schedule. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/immunization/schedules/form_embed.php?id=$id" : "/HealthLogs/public/immunization/schedules/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/immunization/schedules/form_embed.php" : "/HealthLogs/public/immunization/schedules/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/immunization/schedules/index.php');
exit;
