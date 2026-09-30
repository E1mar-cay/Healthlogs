<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$newDate = trim($_POST['appointment_date'] ?? '');
$newTime = trim($_POST['appointment_time'] ?? '08:30');
$reason = trim($_POST['reschedule_reason'] ?? '');

if ($id <= 0 || empty($newDate) || empty($reason)) {
    flash('error', 'Please provide the appointment ID, new date, and a valid rescheduling reason.');
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

try {
    $curStmt = $pdo->prepare("
        SELECT a.*, p.first_name, p.last_name, hs.service_name
        FROM patient_appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN health_services hs ON hs.id = a.service_id
        WHERE a.id = ?
    ");
    $curStmt->execute([$id]);
    $apt = $curStmt->fetch(PDO::FETCH_ASSOC);

    if (!$apt) {
        flash('error', 'Appointment not found.');
        header('Location: /HealthLogs/public/appointments/index.php');
        exit;
    }

    $prevDate = $apt['appointment_date'];
    $patientName = trim($apt['last_name'] . ', ' . $apt['first_name']);

    $updateSql = "UPDATE patient_appointments SET
        appointment_date = ?,
        appointment_time = ?,
        previous_appointment_date = ?,
        reschedule_reason = ?,
        status = 'rescheduled'
        WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        $newDate,
        $newTime . ':00',
        $prevDate,
        $reason,
        $id
    ]);

    // Update reminder due date
    $remUpdate = $pdo->prepare("UPDATE reminders SET due_date = ?, status = 'pending' WHERE patient_id = ? AND due_date = ?");
    $remUpdate->execute([$newDate, $apt['patient_id'], $prevDate]);

    // Audit Log
    ActivityLogger::logAppointment('reschedule', "Rescheduled appointment {$apt['appointment_code']} for {$patientName} from {$prevDate} to {$newDate} (Reason: {$reason})", (string)$id, [
        'appointment_id' => $id,
        'appointment_code' => $apt['appointment_code'],
        'patient_name' => $patientName,
        'previous_date' => $prevDate,
        'new_date' => $newDate,
        'new_time' => $newTime,
        'reschedule_reason' => $reason
    ]);

    flash('success', "Appointment {$apt['appointment_code']} successfully rescheduled to {$newDate} at {$newTime}.");
} catch (Throwable $e) {
    flash('error', 'Failed to reschedule appointment: ' . $e->getMessage());
}

header('Location: /HealthLogs/public/appointments/index.php');
exit;
