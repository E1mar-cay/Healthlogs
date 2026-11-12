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
        SELECT a.*, p.first_name, p.last_name, hs.service_name, hs.service_code, hs.category
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

    // Record general/appointment visit in visits table if not already logged today for this patient
    $vCheck = $pdo->prepare("SELECT id FROM visits WHERE patient_id = ? AND DATE(visit_datetime) = CURDATE() AND reason = ? LIMIT 1");
    $vCheck->execute([$apt['patient_id'], $apt['service_name']]);
    if (!$vCheck->fetchColumn()) {
        $vIns = $pdo->prepare("INSERT INTO visits (patient_id, visit_datetime, visit_type, reason, notes, recorded_by) VALUES (?, NOW(), 'general', ?, ?, ?)");
        $vIns->execute([
            $apt['patient_id'],
            $apt['service_name'],
            $notes ?: "Completed appointment {$apt['appointment_code']}",
            $_SESSION['user_id'] ?? null
        ]);
    }

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

    if (!empty($_POST['open_program'])) {
        $patientId = (int)$apt['patient_id'];
        $category = (string)($apt['category'] ?? '');
        $serviceCode = (string)($apt['service_code'] ?? '');

        if ($category === 'maternal' || in_array($serviceCode, ['PRENATAL_CARE', 'POSTNATAL_CARE'], true)) {
            $pregStmt = $pdo->prepare("SELECT id FROM pregnancies WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $pregStmt->execute([$patientId]);
            $pregId = (int)$pregStmt->fetchColumn();
            if ($pregId > 0) {
                $anchor = $serviceCode === 'POSTNATAL_CARE' ? '#postnatal-section' : '#prenatal-section';
                header("Location: /HealthLogs/public/maternal/tcl.php?pregnancy_id={$pregId}{$anchor}");
                exit;
            }
            header("Location: /HealthLogs/public/maternal/pregnancies/index.php?patient_id={$patientId}");
            exit;
        }

        if ($category === 'immunization' || $serviceCode === 'EPI_VACCINE') {
            header("Location: /HealthLogs/public/immunization/tcl.php?q=" . urlencode($apt['last_name']));
            exit;
        }

        if ($category === 'family_planning' || $serviceCode === 'FAMILY_PLANNING') {
            $fpStmt = $pdo->prepare("SELECT id FROM fp_records WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $fpStmt->execute([$patientId]);
            $fpId = (int)$fpStmt->fetchColumn();
            if ($fpId > 0) {
                header("Location: /HealthLogs/public/family_planning/visits/index.php?fp_record_id={$fpId}");
                exit;
            }
            header("Location: /HealthLogs/public/family_planning/records/index.php?q=" . urlencode($apt['last_name']));
            exit;
        }

        if ($category === 'ncd' || $serviceCode === 'NCD_CHECKUP') {
            $ncdStmt = $pdo->prepare("SELECT id FROM ncd_records WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
            $ncdStmt->execute([$patientId]);
            $ncdId = (int)$ncdStmt->fetchColumn();
            if ($ncdId > 0) {
                header("Location: /HealthLogs/public/ncd/visits/index.php?ncd_record_id={$ncdId}");
                exit;
            }
            header("Location: /HealthLogs/public/ncd/records/index.php?q=" . urlencode($apt['last_name']));
            exit;
        }

        header("Location: /HealthLogs/public/patients/view.php?id={$patientId}#service-records");
        exit;
    }
} catch (Throwable $e) {
    flash('error', 'Failed to complete appointment: ' . $e->getMessage());
}

header('Location: /HealthLogs/public/appointments/index.php');
exit;
