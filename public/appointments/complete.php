<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$notes = trim($_POST['clinical_notes'] ?? '');

if ($id <= 0) {
    flash('error', 'Appointment ID is required.');
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
        status = 'completed',
        completed_at = NOW(),
        clinical_notes = ?
        WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        $notes ?: 'Encounter completed successfully per clinical protocol.',
        $id
    ]);

    // Mark reminder as sent/completed
    $remUpdate = $pdo->prepare("UPDATE reminders SET status = 'sent' WHERE patient_id = ? AND due_date = ?");
    $remUpdate->execute([$apt['patient_id'], $apt['appointment_date']]);

    // Audit Log
    ActivityLogger::logAppointment('complete', "Completed appointment encounter {$apt['appointment_code']} for {$patientName} ({$apt['service_name']})", (string)$id, [
        'appointment_id' => $id,
        'appointment_code' => $apt['appointment_code'],
        'patient_name' => $patientName,
        'service_name' => $apt['service_name'],
        'clinical_notes' => $notes,
        'completed_at' => date('Y-m-d H:i:s')
    ]);

    flash('success', "Appointment {$apt['appointment_code']} for {$patientName} marked as completed.");
} catch (Throwable $e) {
    flash('error', 'Failed to complete appointment: ' . $e->getMessage());
}

header('Location: /HealthLogs/public/appointments/index.php');
exit;
