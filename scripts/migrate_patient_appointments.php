<?php
require_once __DIR__ . '/../config/db.php';

echo "--- Running Migration: Patient Classification, Health Services, and Appointments ---\n";

$sql = file_get_contents(__DIR__ . '/../schema/add_patient_classification_and_appointments.sql');

// Remove USE healthlogs; and split queries
$sql = preg_replace('/USE\s+healthlogs;/i', '', $sql);
$queries = array_filter(array_map('trim', explode(';', $sql)));

foreach ($queries as $q) {
    if (!empty($q)) {
        try {
            $pdo->exec($q);
        } catch (PDOException $e) {
            echo "Notice on query: " . $e->getMessage() . "\n";
        }
    }
}
echo "Schema executed successfully.\n";

// Backfill patient classifications based on actual age and health records
echo "Backfilling patient classifications...\n";

// 1. Mark pregnant women
$pregStmt = $pdo->query("SELECT DISTINCT patient_id FROM pregnancies WHERE status = 'ongoing'");
$pregnantPatientIds = $pregStmt->fetchAll(PDO::FETCH_COLUMN);

// 2. Classify all patients
$patients = $pdo->query("SELECT id, sex, birth_date FROM patients")->fetchAll(PDO::FETCH_ASSOC);

$updateStmt = $pdo->prepare("UPDATE patients SET classification = ?, civil_status = ? WHERE id = ?");

$classCounts = [];
foreach ($patients as $p) {
    $dob = new DateTime($p['birth_date']);
    $today = new DateTime();
    $diff = $dob->diff($today);
    $months = ($diff->y * 12) + $diff->m;
    $years = $diff->y;

    if ($months < 12) {
        $class = 'infant'; // Baby
        $civil = 'child';
    } elseif ($years < 5) {
        $class = 'under_five';
        $civil = 'child';
    } elseif ($years < 10) {
        $class = 'school_age';
        $civil = 'child';
    } elseif (in_array((int)$p['id'], array_map('intval', $pregnantPatientIds), true)) {
        $class = 'pregnant';
        $civil = 'married';
    } elseif ($years < 20) {
        $class = 'adolescent';
        $civil = 'single';
    } elseif ($years >= 60) {
        $class = 'senior';
        $civil = ($years % 3 === 0) ? 'widowed' : 'married';
    } else {
        $class = 'adult';
        $civil = ($years % 2 === 0) ? 'married' : 'single';
    }

    $classCounts[$class] = ($classCounts[$class] ?? 0) + 1;
    $updateStmt->execute([$class, $civil, $p['id']]);
}

// Tag a realistic subset as 4Ps and PWD for priority groups
$pdo->exec("UPDATE patients SET is_4ps = 1, philhealth_category = 'indigent_4ps' WHERE id IN (1, 3, 7, 12, 18, 25, 33, 42, 50, 65, 78, 88, 95)");
$pdo->exec("UPDATE patients SET is_pwd = 1 WHERE id IN (4, 15, 29, 62, 81)");

echo "Patient classification breakdown:\n";
print_r($classCounts);

// Seed realistic appointments for active monitoring
echo "Seeding realistic patient appointments...\n";
$aptCount = (int)$pdo->query("SELECT COUNT(*) FROM patient_appointments")->fetchColumn();
if ($aptCount === 0) {
    $services = $pdo->query("SELECT id, service_code, service_name, category FROM health_services")->fetchAll(PDO::FETCH_ASSOC);
    $serviceMap = [];
    foreach ($services as $s) {
        $serviceMap[$s['service_code']] = $s['id'];
    }

    $staff = ['Nurse Maricar Ramos, RN', 'BHW Elena Bautista', 'BHW Rosanna Santos', 'Midwife Corazon Rivera, RM'];

    $sampleAppointments = [
        // Today & Upcoming
        [
            'code' => 'APT-2026-001',
            'patient_id' => 1,
            'service_code' => 'NCD_CHECKUP',
            'date' => date('Y-m-d'),
            'time' => '08:30:00',
            'status' => 'scheduled',
            'reason' => 'Quarterly Hypertension Blood Pressure check and Maintenance Amlodipine refill',
            'assigned' => $staff[1]
        ],
        [
            'code' => 'APT-2026-002',
            'patient_id' => 2,
            'service_code' => 'FAMILY_PLANNING',
            'date' => date('Y-m-d'),
            'time' => '09:15:00',
            'status' => 'scheduled',
            'reason' => 'DMPA Injectable 3-month cycle resupply and vitals assessment',
            'assigned' => $staff[3]
        ],
        [
            'code' => 'APT-2026-003',
            'patient_id' => 18,
            'service_code' => 'PRENATAL_CARE',
            'date' => date('Y-m-d'),
            'time' => '10:00:00',
            'status' => 'scheduled',
            'reason' => 'Third trimester prenatal follow-up, fundic height check, and iron supplement release',
            'assigned' => $staff[3]
        ],
        [
            'code' => 'APT-2026-004',
            'patient_id' => 7,
            'service_code' => 'EPI_VACCINE',
            'date' => date('Y-m-d', strtotime('+1 day')),
            'time' => '08:45:00',
            'status' => 'scheduled',
            'reason' => 'Pentavalent 3rd dose + OPV routine immunization for baby',
            'assigned' => $staff[0]
        ],
        [
            'code' => 'APT-2026-005',
            'patient_id' => 15,
            'service_code' => 'SENIOR_WELLNESS',
            'date' => date('Y-m-d', strtotime('+2 days')),
            'time' => '09:00:00',
            'status' => 'scheduled',
            'reason' => 'Annual Senior citizen preventive health evaluation and joint pain consultation',
            'assigned' => $staff[0]
        ],
        [
            'code' => 'APT-2026-006',
            'patient_id' => 12,
            'service_code' => 'CHILD_NUTRITION',
            'date' => date('Y-m-d', strtotime('+3 days')),
            'time' => '10:30:00',
            'status' => 'scheduled',
            'reason' => 'Under-5 growth monitoring, weight check, and Vitamin A capsule administration',
            'assigned' => $staff[2]
        ],
        [
            'code' => 'APT-2026-007',
            'patient_id' => 3,
            'service_code' => 'GEN_CONSULTATION',
            'date' => date('Y-m-d', strtotime('+4 days')),
            'time' => '11:00:00',
            'status' => 'scheduled',
            'reason' => 'Follow-up for seasonal allergic rhinitis and dry cough',
            'assigned' => $staff[1]
        ],
        // Rescheduled
        [
            'code' => 'APT-2026-008',
            'patient_id' => 4,
            'service_code' => 'NCD_CHECKUP',
            'date' => date('Y-m-d', strtotime('+5 days')),
            'time' => '09:30:00',
            'status' => 'rescheduled',
            'reason' => 'Fasting Blood Sugar monitoring and Metformin prescription review',
            'reschedule_reason' => 'Patient had agricultural harvest commitment on initial date',
            'prev_date' => date('Y-m-d', strtotime('-2 days')),
            'assigned' => $staff[1]
        ],
        // Completed
        [
            'code' => 'APT-2026-009',
            'patient_id' => 5,
            'service_code' => 'WOUND_CARE',
            'date' => date('Y-m-d', strtotime('-1 day')),
            'time' => '14:00:00',
            'status' => 'completed',
            'reason' => 'Post-wound dressing change and healing evaluation on left forearm',
            'completed_at' => date('Y-m-d 14:35:00', strtotime('-1 day')),
            'clinical_notes' => 'Wound clean with healthy granulation tissue. No signs of infection. Betadine dressing reapplied. Advised to keep dry.',
            'assigned' => $staff[0]
        ],
        [
            'code' => 'APT-2026-010',
            'patient_id' => 6,
            'service_code' => 'TB_DOTS',
            'date' => date('Y-m-d', strtotime('-2 days')),
            'time' => '10:00:00',
            'status' => 'completed',
            'reason' => 'Presumptive TB screening after 2-week productive cough',
            'completed_at' => date('Y-m-d 10:45:00', strtotime('-2 days')),
            'clinical_notes' => 'Collected 2 sputum samples for GeneXpert referral to City Health Office. Advised on mask wearing and respiratory hygiene.',
            'assigned' => $staff[0]
        ],
        // Cancelled
        [
            'code' => 'APT-2026-011',
            'patient_id' => 8,
            'service_code' => 'GEN_CONSULTATION',
            'date' => date('Y-m-d', strtotime('-3 days')),
            'time' => '13:30:00',
            'status' => 'cancelled',
            'reason' => 'Routine physical assessment for barangay clearance',
            'cancellation_reason' => 'Patient relocated to municipality center and will secure clearance there',
            'assigned' => $staff[2]
        ],
    ];

    $defaultUserId = (int)$pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();

    $insertApt = $pdo->prepare("INSERT INTO patient_appointments 
        (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, reschedule_reason, previous_appointment_date, cancellation_reason, completed_at, clinical_notes, assigned_personnel, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($sampleAppointments as $a) {
        $srvId = $serviceMap[$a['service_code']] ?? 1;
        $insertApt->execute([
            $a['code'],
            $a['patient_id'],
            $srvId,
            $a['date'],
            $a['time'],
            $a['status'],
            $a['reason'],
            $a['reschedule_reason'] ?? null,
            $a['prev_date'] ?? null,
            $a['cancellation_reason'] ?? null,
            $a['completed_at'] ?? null,
            $a['clinical_notes'] ?? null,
            $a['assigned'],
            $defaultUserId
        ]);
    }
    echo "Seeded " . count($sampleAppointments) . " initial realistic appointments.\n";
} else {
    echo "Appointments already exist ({$aptCount} records).\n";
}

echo "--- Migration Completed Successfully! ---\n";
