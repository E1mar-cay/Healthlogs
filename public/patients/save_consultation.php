<?php
require __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

if (!can_manage_clinical_records()) {
    flash('error', 'You do not have permission to record clinical consultations.');
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

$patientId = (int)($_POST['patient_id'] ?? 0);
if ($patientId <= 0) {
    flash('error', 'Invalid patient selected.');
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    flash('error', 'Patient record not found.');
    header('Location: /HealthLogs/public/patients/index.php');
    exit;
}

$visitDatetime = trim((string)($_POST['visit_datetime'] ?? ''));
if ($visitDatetime === '') {
    $visitDatetime = date('Y-m-d H:i:s');
} else {
    $visitDatetime = str_replace('T', ' ', $visitDatetime);
    if (strlen($visitDatetime) === 16) {
        $visitDatetime .= ':00';
    }
}

$visitType = trim((string)($_POST['visit_type'] ?? 'general'));
$validVisitTypes = ['general', 'prenatal', 'postnatal', 'immunization', 'family_planning', 'ncd'];
if (!in_array($visitType, $validVisitTypes, true)) {
    $visitType = 'general';
}

$reason = trim((string)($_POST['reason'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

$conditionName = trim((string)($_POST['condition_name'] ?? ''));
$conditionStatus = trim((string)($_POST['condition_status'] ?? 'active'));
if (!in_array($conditionStatus, ['active', 'resolved', 'chronic'], true)) {
    $conditionStatus = 'active';
}
$diagnosedOn = trim((string)($_POST['diagnosed_on'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $diagnosedOn)) {
    $diagnosedOn = date('Y-m-d');
}
$conditionNotes = trim((string)($_POST['condition_notes'] ?? ''));

$allergen = trim((string)($_POST['allergen'] ?? ''));
$reaction = trim((string)($_POST['reaction'] ?? ''));

$dispenseMedicineId = (int)($_POST['dispense_medicine_id'] ?? 0);
$dispenseQuantity = (int)($_POST['dispense_quantity'] ?? 0);

$nextVisitDate = trim((string)($_POST['next_visit_date'] ?? ''));

if ($reason === '' && $conditionName === '' && $allergen === '') {
    flash('error', 'Please enter a consultation reason, diagnosis, or allergy to record.');
    header("Location: /HealthLogs/public/patients/view.php?id={$patientId}");
    exit;
}

try {
    $pdo->beginTransaction();

    $summaryParts = [];

    // 1. Record general / walk-in consultation visit if reason or notes or medicine provided
    $visitId = 0;
    if ($reason !== '' || $notes !== '' || $dispenseMedicineId > 0) {
        $effectiveReason = $reason !== '' ? $reason : ($conditionName !== '' ? "Diagnosis: {$conditionName}" : 'General Consultation');
        $vStmt = $pdo->prepare("
            INSERT INTO visits (patient_id, visit_datetime, visit_type, reason, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $vStmt->execute([
            $patientId,
            $visitDatetime,
            $visitType,
            $effectiveReason,
            $notes !== '' ? $notes : null,
            $_SESSION['user_id'] ?? null,
        ]);
        $visitId = (int)$pdo->lastInsertId();
        $summaryParts[] = 'consultation logged';
    }

    // 2. Record diagnosed medical condition (if provided)
    if ($conditionName !== '') {
        $cStmt = $pdo->prepare("
            INSERT INTO patient_conditions (patient_id, condition_name, status, diagnosed_on, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $cStmt->execute([
            $patientId,
            $conditionName,
            $conditionStatus,
            $diagnosedOn,
            $conditionNotes !== '' ? $conditionNotes : ($notes !== '' ? $notes : null),
        ]);
        $summaryParts[] = "diagnosis ({$conditionName}) recorded";
    }

    // 3. Record patient allergy (if provided)
    if ($allergen !== '') {
        $aStmt = $pdo->prepare("
            INSERT INTO patient_allergies (patient_id, allergen, reaction, noted_on)
            VALUES (?, ?, ?, ?)
        ");
        $aStmt->execute([
            $patientId,
            $allergen,
            $reaction !== '' ? $reaction : null,
            date('Y-m-d'),
        ]);
        $summaryParts[] = "allergy ({$allergen}) added";
    }

    $pdo->commit();

    // 4. Optional FEFO Pharmacy Dispensing
    if ($dispenseMedicineId > 0 && $dispenseQuantity > 0) {
        $medStmt = $pdo->prepare("SELECT name FROM medicines WHERE id = ?");
        $medStmt->execute([$dispenseMedicineId]);
        $medName = (string)$medStmt->fetchColumn();
        if ($medName !== '') {
            $refNo = $visitId > 0 ? "CONSULT-{$visitId}" : "PT-{$patientId}-" . date('Ymd');
            $dispensedOk = ClinicalWorkflow::dispenseMedicineFEFO(
                $pdo,
                $dispenseMedicineId,
                $dispenseQuantity,
                $refNo,
                "Dispensed during consultation for {$patient['last_name']}, {$patient['first_name']}"
            );
            if ($dispensedOk) {
                $summaryParts[] = "{$dispenseQuantity} unit(s) of {$medName} dispensed via FEFO";
                if ($visitId > 0) {
                    $appendNote = ($notes !== '' ? $notes . ' | ' : '') . "Dispensed: {$dispenseQuantity}x {$medName}";
                    $uVisit = $pdo->prepare("UPDATE visits SET notes = ? WHERE id = ?");
                    $uVisit->execute([$appendNote, $visitId]);
                }
            }
        }
    }

    // 5. Optional Follow-up Appointment & Reminder sync
    if ($nextVisitDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextVisitDate)) {
        ClinicalWorkflow::syncFollowUpAppointment(
            $pdo,
            $patientId,
            $nextVisitDate,
            'GEN_CONSULTATION',
            'general',
            'General Consultation Follow-up' . ($conditionName !== '' ? " ({$conditionName})" : ''),
            'Nurse Elena Mendoza, RN'
        );
        $summaryParts[] = 'follow-up scheduled for ' . date('M d, Y', strtotime($nextVisitDate));
    }

    ActivityLogger::log(
        'create',
        'patient',
        "Recorded clinical consultation for {$patient['last_name']}, {$patient['first_name']} (" . implode(', ', $summaryParts) . ")",
        (string)$patientId,
        [
            'patient_id' => $patientId,
            'visit_id' => $visitId,
            'condition_name' => $conditionName,
            'allergen' => $allergen,
            'dispense_medicine_id' => $dispenseMedicineId,
            'dispense_quantity' => $dispenseQuantity,
            'next_visit_date' => $nextVisitDate,
        ]
    );

    flash('success', 'Clinical record updated: ' . ucfirst(implode(', ', $summaryParts)) . '.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Consultation save error: " . $e->getMessage());
    flash('error', 'Failed to save clinical consultation: ' . $e->getMessage());
}

header("Location: /HealthLogs/public/patients/view.php?id={$patientId}#service-records");
exit;

