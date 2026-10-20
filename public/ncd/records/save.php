<?php
require __DIR__ . '/../../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/ncd/records/index.php');
    exit;
}

$returnTo = trim($_POST['return_to'] ?? '/HealthLogs/public/ncd/records/index.php');
$patientId = (int)($_POST['patient_id'] ?? 0);
$ncdCode = trim($_POST['ncd_code'] ?? '');
$regDate = trim($_POST['registration_date'] ?? date('Y-m-d'));
$diagType = trim($_POST['diagnosis_type'] ?? 'hypertension');
$dateDiag = trim($_POST['date_diagnosed'] ?? date('Y-m-d'));
$riskLevel = trim($_POST['philpen_risk_level'] ?? 'low');
$isSmoker = trim($_POST['is_smoker'] ?? 'never');
$isAlcohol = trim($_POST['is_alcohol_drinker'] ?? 'never');
$maintenanceMeds = trim($_POST['maintenance_meds'] ?? '');
$targetBp = trim($_POST['target_bp'] ?? $_POST['actual_bp'] ?? '');
$targetFbs = trim($_POST['target_fbs'] ?? '');
$status = trim($_POST['status'] ?? 'active');
$remarks = trim($_POST['remarks'] ?? '');

if ($patientId <= 0 || empty($ncdCode) || empty($regDate) || empty($dateDiag)) {
    set_flash('error', 'Please fill in all required patient and clinical fields.');
    header("Location: {$returnTo}");
    exit;
}

// Check for existing active record
$chk = $pdo->prepare("SELECT id, ncd_code FROM ncd_records WHERE patient_id = ? AND status IN ('active', 'controlled', 'uncontrolled')");
$chk->execute([$patientId]);
if ($existing = $chk->fetch()) {
    set_flash('error', "This patient already has an active NCD profile ({$existing['ncd_code']}).");
    header("Location: {$returnTo}");
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO ncd_records (
            patient_id, ncd_code, registration_date, diagnosis_type, date_diagnosed,
            philpen_risk_level, is_smoker, is_alcohol_drinker, maintenance_meds,
            target_bp, target_fbs, status, remarks
        ) VALUES (
            :patient_id, :ncd_code, :registration_date, :diagnosis_type, :date_diagnosed,
            :philpen_risk_level, :is_smoker, :is_alcohol_drinker, :maintenance_meds,
            :target_bp, :target_fbs, :status, :remarks
        )
    ");
    $stmt->execute([
        'patient_id' => $patientId,
        'ncd_code' => $ncdCode,
        'registration_date' => $regDate,
        'diagnosis_type' => $diagType,
        'date_diagnosed' => $dateDiag,
        'philpen_risk_level' => $riskLevel,
        'is_smoker' => $isSmoker,
        'is_alcohol_drinker' => $isAlcohol,
        'maintenance_meds' => $maintenanceMeds ?: null,
        'target_bp' => $targetBp ?: null,
        'target_fbs' => $targetFbs ?: null,
        'status' => $status,
        'remarks' => $remarks ?: null,
    ]);

    $newId = (int)$pdo->lastInsertId();

    // If an actual BP or actual FBS was provided upon enrollment, automatically create initial baseline visit
    $sys = null;
    $dia = null;
    $bsVal = null;
    $bsType = 'none';

    if (!empty($targetBp) && preg_match('/^(\d{2,3})\s*[\/\-]\s*(\d{2,3})$/', $targetBp, $bpMatches)) {
        $sys = (int)$bpMatches[1];
        $dia = (int)$bpMatches[2];
    }
    if (!empty($targetFbs) && preg_match('/(\d+(?:\.\d+)?)/', $targetFbs, $fbsMatches)) {
        $bsVal = (float)$fbsMatches[1];
        $bsType = 'fbs';
    }

    if ($sys !== null || $bsVal !== null) {
        try {
            $vStmt = $pdo->prepare("
                INSERT INTO ncd_visits (
                    ncd_record_id, visit_date, bp_systolic, bp_diastolic, blood_sugar_mgdl, blood_sugar_type,
                    treatment_adherence, findings_complaints, management_plan, recorded_by
                ) VALUES (
                    :ncd_record_id, :visit_date, :bp_systolic, :bp_diastolic, :blood_sugar_mgdl, :blood_sugar_type,
                    'good', 'Baseline clinical vitals recorded upon program enrollment.', 'Initial clinical enrollment & PhilPEN risk assessment.', :recorded_by
                )
            ");
            $vStmt->execute([
                'ncd_record_id' => $newId,
                'visit_date' => $regDate,
                'bp_systolic' => $sys,
                'bp_diastolic' => $dia,
                'blood_sugar_mgdl' => $bsVal,
                'blood_sugar_type' => $bsType,
                'recorded_by' => function_exists('current_user_id') ? current_user_id() : null,
            ]);
        } catch (Throwable $ve) {
            // Non-critical, continue
        }
    }

    set_flash('success', "NCD Client [{$ncdCode}] successfully registered!");
    header("Location: /HealthLogs/public/ncd/visits/index.php?record_id={$newId}");
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Database error: ' . $e->getMessage());
    header("Location: {$returnTo}");
    exit;
}
