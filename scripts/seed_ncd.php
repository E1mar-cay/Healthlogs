<?php
/**
 * Non-Communicable Disease (NCD) Seeder
 * Populates realistic client enrollment, vital logs, consultations, and reminders
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Seeding Non-Communicable Disease (NCD) Module ===\n";

try {
    // 1. Fetch eligible patients
    $patients = $pdo->query("
        SELECT id, first_name, last_name, middle_name, birth_date, barangay, sex, contact_no
        FROM patients
        WHERE status = 'active'
        ORDER BY id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($patients)) {
        echo "No patients found. Please seed patients first.\n";
        exit;
    }

    // Clear existing NCD data
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE ncd_visits;");
    $pdo->exec("TRUNCATE TABLE ncd_records;");
    $pdo->exec("DELETE FROM reminders WHERE reminder_type = 'non_communicable';");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $diagnoses = [
        [
            'type' => 'hypertension',
            'label' => 'Hypertension (Stage 1 / 2)',
            'meds' => 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD',
            'target_bp' => '<140/90',
            'target_fbs' => '<100 mg/dL',
            'dispense_text' => 'Losartan 50mg #30, Amlodipine 5mg #30',
        ],
        [
            'type' => 'diabetes',
            'label' => 'Type 2 Diabetes Mellitus',
            'meds' => 'Metformin 500mg 1 tab BID after meals',
            'target_bp' => '<130/80',
            'target_fbs' => '<126 mg/dL',
            'dispense_text' => 'Metformin 500mg #60',
        ],
        [
            'type' => 'hypertension_diabetes',
            'label' => 'Hypertension & Type 2 Diabetes',
            'meds' => 'Losartan 50mg OD, Metformin 500mg BID, Gliclazide 80mg OD',
            'target_bp' => '<130/80',
            'target_fbs' => '<126 mg/dL',
            'dispense_text' => 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30',
        ],
        [
            'type' => 'asthma_copd',
            'label' => 'Bronchial Asthma / Chronic Airway Disease',
            'meds' => 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg OD',
            'target_bp' => '<140/90',
            'target_fbs' => '<100 mg/dL',
            'dispense_text' => 'Salbutamol MDI 1 cannister, Montelukast 10mg #30',
        ],
        [
            'type' => 'cardiovascular',
            'label' => 'Coronary Artery Disease / Post-Stroke',
            'meds' => 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID',
            'target_bp' => '<130/80',
            'target_fbs' => '<100 mg/dL',
            'dispense_text' => 'Aspirin 80mg #30, Atorvastatin 20mg #30',
        ],
        [
            'type' => 'chronic_kidney',
            'label' => 'Chronic Kidney Disease (Stage 2-3)',
            'meds' => 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID',
            'target_bp' => '<130/80',
            'target_fbs' => '<100 mg/dL',
            'dispense_text' => 'Losartan 25mg #30, Sodium Bicarb #90',
        ],
    ];

    $riskLevels = ['low', 'moderate', 'moderate', 'high', 'high', 'very_high'];
    $smokerStatuses = ['never', 'former', 'current', 'never', 'never'];
    $alcoholStatuses = ['never', 'occasional', 'never', 'regular'];
    $statuses = ['active', 'controlled', 'controlled', 'uncontrolled', 'active'];

    $complaintsPool = [
        'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.',
        'Reports occasional morning dizziness and nape heaviness after skipping medication.',
        'Patient reports good compliance with diet and daily maintenance medications. Feels well.',
        'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.',
        'Monthly blood pressure monitoring. Advised low salt, low fat diet and 30 mins daily walking.',
        'Follow-up for asthma symptoms. Inhaler technique re-demonstrated. No nocturnal wheezing noted.',
        'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.',
    ];

    $planPool = [
        'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.',
        'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.',
        'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.',
        'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.',
        'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.',
    ];

    $recordInsertStmt = $pdo->prepare("
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

    $visitInsertStmt = $pdo->prepare("
        INSERT INTO ncd_visits (
            ncd_record_id, visit_date, bp_systolic, bp_diastolic, blood_sugar_mgdl,
            blood_sugar_type, weight_kg, height_cm, bmi, waist_cm,
            medications_dispensed, quantity_dispensed, treatment_adherence,
            findings_complaints, management_plan, next_appointment_date, recorded_by
        ) VALUES (
            :ncd_record_id, :visit_date, :bp_systolic, :bp_diastolic, :blood_sugar_mgdl,
            :blood_sugar_type, :weight_kg, :height_cm, :bmi, :waist_cm,
            :medications_dispensed, :quantity_dispensed, :treatment_adherence,
            :findings_complaints, :management_plan, :next_appointment_date, :recorded_by
        )
    ");

    $reminderInsertStmt = $pdo->prepare("
        INSERT INTO reminders (patient_id, reminder_type, due_date, message, status)
        VALUES (?, 'non_communicable', ?, ?, ?)
    ");

    $totalRecords = 0;
    $totalVisits = 0;
    $totalReminders = 0;

    $userId = (int)($pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: 1);

    // Seed 25 patients
    $selectedPatients = array_slice($patients, 0, 25);
    $currYear = date('Y');

    foreach ($selectedPatients as $idx => $p) {
        $seq = $idx + 1;
        $ncdCode = sprintf('NCD-%s-%04d', $currYear, $seq);
        $diag = $diagnoses[$idx % count($diagnoses)];
        $risk = $riskLevels[$idx % count($riskLevels)];
        $smoker = $smokerStatuses[$idx % count($smokerStatuses)];
        $alcohol = $alcoholStatuses[$idx % count($alcoholStatuses)];
        $status = $statuses[$idx % count($statuses)];

        $monthsDiagnosedAgo = rand(3, 24);
        $dateDiagnosed = date('Y-m-d', strtotime("-{$monthsDiagnosedAgo} months"));
        $regDate = date('Y-m-d', strtotime("-{$monthsDiagnosedAgo} months +5 days"));

        $remarks = "PhilPEN screened. Patient registered under Barangay Health Station NCD Club.";

        $recordInsertStmt->execute([
            'patient_id' => $p['id'],
            'ncd_code' => $ncdCode,
            'registration_date' => $regDate,
            'diagnosis_type' => $diag['type'],
            'date_diagnosed' => $dateDiagnosed,
            'philpen_risk_level' => $risk,
            'is_smoker' => $smoker,
            'is_alcohol_drinker' => $alcohol,
            'maintenance_meds' => $diag['meds'],
            'target_bp' => $diag['target_bp'],
            'target_fbs' => $diag['target_fbs'],
            'status' => $status,
            'remarks' => $remarks,
        ]);

        $recordId = (int)$pdo->lastInsertId();
        $totalRecords++;

        // Add 1 to 3 visits per patient
        $numVisits = rand(1, 3);
        $heightCm = rand(150, 175);

        for ($v = 0; $v < $numVisits; $v++) {
            $isLatest = ($v === $numVisits - 1);
            $visitDaysAgo = ($numVisits - 1 - $v) * 30 + rand(2, 10);
            $visitDate = date('Y-m-d', strtotime("-{$visitDaysAgo} days"));

            // Generate realistic BP and Blood sugar
            $bpSys = ($diag['type'] === 'hypertension' || $diag['type'] === 'hypertension_diabetes') 
                ? rand(125, 160) 
                : rand(110, 135);
            $bpDia = ($bpSys >= 140) ? rand(88, 100) : rand(70, 85);

            $bsMgdl = null;
            $bsType = 'none';
            if ($diag['type'] === 'diabetes' || $diag['type'] === 'hypertension_diabetes') {
                $bsMgdl = (float)rand(105, 195);
                $bsType = 'fbs';
            } elseif ($idx % 3 === 0) {
                $bsMgdl = (float)rand(90, 120);
                $bsType = 'rbs';
            }

            $weightKg = round(rand(500, 850) / 10, 1);
            $bmi = round($weightKg / (($heightCm / 100) * ($heightCm / 100)), 1);
            $waistCm = round(rand(720, 980) / 10, 1);

            $adherence = ($status === 'uncontrolled' || $idx % 5 === 0) ? 'poor' : (($idx % 4 === 0) ? 'fair' : 'good');
            $complaint = $complaintsPool[($idx + $v) % count($complaintsPool)];
            $plan = $planPool[($idx + $v) % count($planPool)];

            // Appointment date for latest visit
            $nextAppDate = null;
            if ($isLatest) {
                // Some overdue, some due in next few days/weeks
                if ($idx % 5 === 0) {
                    $nextAppDate = date('Y-m-d', strtotime('-' . rand(3, 15) . ' days')); // overdue
                } else {
                    $nextAppDate = date('Y-m-d', strtotime('+' . rand(5, 28) . ' days')); // upcoming
                }
            } else {
                $nextAppDate = date('Y-m-d', strtotime($visitDate . ' + 30 days'));
            }

            $visitInsertStmt->execute([
                'ncd_record_id' => $recordId,
                'visit_date' => $visitDate,
                'bp_systolic' => $bpSys,
                'bp_diastolic' => $bpDia,
                'blood_sugar_mgdl' => $bsMgdl,
                'blood_sugar_type' => $bsType,
                'weight_kg' => $weightKg,
                'height_cm' => $heightCm,
                'bmi' => $bmi,
                'waist_cm' => $waistCm,
                'medications_dispensed' => $diag['dispense_text'],
                'quantity_dispensed' => 30,
                'treatment_adherence' => $adherence,
                'findings_complaints' => $complaint,
                'management_plan' => $plan,
                'next_appointment_date' => $nextAppDate,
                'recorded_by' => $userId,
            ]);
            $totalVisits++;

            // Create reminder for the upcoming appointment
            if ($isLatest && $nextAppDate !== null && !empty($p['contact_no'])) {
                $remMsg = "Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill ({$diag['label']}) is scheduled on " . date('M d, Y', strtotime($nextAppDate)) . ". Please bring your monitoring card.";
                $reminderStatus = ($nextAppDate < date('Y-m-d')) ? 'pending' : 'pending';
                $reminderInsertStmt->execute([
                    $p['id'],
                    $nextAppDate,
                    $remMsg,
                    $reminderStatus,
                ]);
                $totalReminders++;
            }
        }
    }

    echo "Successfully seeded NCD module:\n";
    echo "- Enrolled records: {$totalRecords}\n";
    echo "- Consultation/vital logs: {$totalVisits}\n";
    echo "- Scheduled SMS reminders: {$totalReminders}\n";

} catch (Throwable $e) {
    echo "Seeder error: " . $e->getMessage() . "\n";
    exit(1);
}
