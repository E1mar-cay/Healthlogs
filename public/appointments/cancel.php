<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['cancellation_reason'] ?? '');

if ($id <= 0 || empty($reason)) {
    flash('error', 'Please provide the appointment ID and a valid reason for cancellation.');
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

    $patientName = trim($apt['last_name'] . ', ' . $apt['first_name']);

    $updateSql = "UPDATE patient_appointments SET
        status = 'cancelled',
        cancellation_reason = ?
        WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        $reason,
        $id
    ]);

    // Cancel reminder
    $remUpdate = $pdo->prepare("UPDATE reminders SET status = 'cancelled' WHERE patient_id = ? AND due_date = ?");
    $remUpdate->execute([$apt['patient_id'], $apt['appointment_date']]);

    // Audit Log
    ActivityLogger::logAppointment('cancel', "Cancelled appointment {$apt['appointment_code']} for {$patientName} (Reason: {$reason})", (string)$id, [
        'appointment_id' => $id,
        'appointment_code' => $apt['appointment_code'],
        'patient_name' => $patientName,
        'service_name' => $apt['service_name'],
        'cancellation_reason' => $reason
    ]);

    flash('success', "Appointment {$apt['appointment_code']} has been cancelled.");
} catch (Throwable $e) {
    flash('error', 'Failed to cancel appointment: ' . $e->getMessage());
}

header('Location: /HealthLogs/public/appointments/index.php');
exit;
