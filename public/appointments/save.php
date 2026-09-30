<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

$patientId = (int)($_POST['patient_id'] ?? 0);
$serviceId = (int)($_POST['service_id'] ?? 0);
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$appointmentTime = trim($_POST['appointment_time'] ?? '08:30');
$assignedPersonnel = trim($_POST['assigned_personnel'] ?? 'Barangay Health Worker');
$reason = trim($_POST['reason'] ?? '');
$userId = $_SESSION['user_id'] ?? null;

if ($patientId <= 0 || $serviceId <= 0 || empty($appointmentDate)) {
    flash('error', 'Please fill in all required appointment details (Patient, Service, and Date).');
    header('Location: /HealthLogs/public/appointments/index.php');
    exit;
}

try {
    // Generate unique Appointment Code (APT-YYYY-XXXX)
    $year = date('Y');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM patient_appointments WHERE appointment_code LIKE 'APT-{$year}-%'")->fetchColumn();
    $code = sprintf('APT-%s-%04d', $year, $count + 1);

    // Fetch patient & service names for logging
    $pStmt = $pdo->prepare("SELECT first_name, last_name, contact_no FROM patients WHERE id = ?");
    $pStmt->execute([$patientId]);
    $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);
    $patientName = $pRow ? trim($pRow['last_name'] . ', ' . $pRow['first_name']) : "Patient #{$patientId}";

    $sStmt = $pdo->prepare("SELECT service_name, service_code, category FROM health_services WHERE id = ?");
    $sStmt->execute([$serviceId]);
    $sRow = $sStmt->fetch(PDO::FETCH_ASSOC);
    $serviceName = $sRow['service_name'] ?? 'Healthcare Service';

    $insertSql = "INSERT INTO patient_appointments 
        (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
        VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        $code,
        $patientId,
        $serviceId,
        $appointmentDate,
        $appointmentTime . ':00',
        $reason,
        $assignedPersonnel,
        $userId
    ]);
    $appointmentId = (int)$pdo->lastInsertId();

    // Sync into reminders table for SMS alerts
    $remType = match($sRow['category'] ?? 'general_consultation') {
        'immunization' => 'immunization',
        'maternal' => 'prenatal',
        'family_planning' => 'family_planning',
        default => 'general'
    };
    $remMsg = "HealthLogs Reminder: You have an appointment for {$serviceName} on {$appointmentDate} at {$appointmentTime}. Please visit Brgy. Tangcul Health Center.";

    $remStmt = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?, ?, ?, ?, 'pending')");
    $remStmt->execute([$patientId, $remType, $appointmentDate, $remMsg]);

    // Log Activity
    ActivityLogger::logAppointment('schedule', "Scheduled appointment {$code} for {$patientName} ({$serviceName} on {$appointmentDate})", (string)$appointmentId, [
        'appointment_code' => $code,
        'patient_id' => $patientId,
        'patient_name' => $patientName,
        'service_id' => $serviceId,
        'service_name' => $serviceName,
        'appointment_date' => $appointmentDate,
        'appointment_time' => $appointmentTime,
        'assigned_personnel' => $assignedPersonnel,
        'reason' => $reason
    ]);

    flash('success', "Appointment {$code} successfully booked for {$patientName}!");
} catch (Throwable $e) {
    flash('error', 'Failed to schedule appointment: ' . $e->getMessage());
}

header('Location: /HealthLogs/public/appointments/index.php');
exit;
