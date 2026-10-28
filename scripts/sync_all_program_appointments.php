<?php
require_once __DIR__ . '/../config/db.php';

echo "--- Syncing Program Appointments to patient_appointments table ---\n";

$year = date('Y');
function generateAppointmentCode(PDO $pdo, string $year): string {
    static $counter = null;
    if ($counter === null) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM patient_appointments WHERE appointment_code LIKE 'APT-{$year}-%'")->fetchColumn();
        $counter = $count;
    }
    $counter++;
    return sprintf('APT-%s-%04d', $year, $counter);
}

$defaultUserId = (int)$pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: null;

// 1. Sync Immunization Schedules
echo "1. Checking Immunization Schedules...\n";
$immSchedules = $pdo->query("
    SELECT s.*, v.name AS vaccine_name 
    FROM immunization_schedule s
    JOIN vaccines v ON v.id = s.vaccine_id
")->fetchAll(PDO::FETCH_ASSOC);

$immInserted = 0;
foreach ($immSchedules as $s) {
    // Check if an appointment already exists for this patient, service 1 (EPI_VACCINE), and date
    $check = $pdo->prepare("SELECT id FROM patient_appointments WHERE patient_id = ? AND service_id = 1 AND appointment_date = ?");
    $check->execute([$s['patient_id'], $s['scheduled_date']]);
    if (!$check->fetchColumn()) {
        $aptCode = generateAppointmentCode($pdo, $year);
        $status = match($s['status']) {
            'completed' => 'completed',
            'missed' => 'missed',
            'cancelled' => 'cancelled',
            default => 'scheduled'
        };
        $reason = "Immunization: {$s['vaccine_name']} (Dose {$s['dose_no']})";
        $insert = $pdo->prepare("
            INSERT INTO patient_appointments 
            (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
            VALUES (?, ?, 1, ?, '08:30:00', ?, ?, 'Barangay Health Worker', ?)
        ");
        $insert->execute([$aptCode, $s['patient_id'], $s['scheduled_date'], $status, $reason, $defaultUserId]);
        $immInserted++;
    }
}
echo "Synced {$immInserted} Immunization appointments.\n";

// 2. Sync Family Planning Next Appointments
echo "2. Checking Family Planning Visits...\n";
$fpVisits = $pdo->query("
    SELECT v.next_appointment_date, v.method_prescribed, r.patient_id, r.client_code
    FROM fp_visits v
    JOIN fp_records r ON r.id = v.fp_record_id
    WHERE v.next_appointment_date IS NOT NULL AND v.next_appointment_date != ''
")->fetchAll(PDO::FETCH_ASSOC);

$fpInserted = 0;
foreach ($fpVisits as $fv) {
    $check = $pdo->prepare("SELECT id FROM patient_appointments WHERE patient_id = ? AND service_id = 4 AND appointment_date = ?");
    $check->execute([$fv['patient_id'], $fv['next_appointment_date']]);
    if (!$check->fetchColumn()) {
        $aptCode = generateAppointmentCode($pdo, $year);
        $isPast = ($fv['next_appointment_date'] < date('Y-m-d'));
        $status = $isPast ? 'completed' : 'scheduled';
        $reason = "Family Planning Consultation & Refill ({$fv['method_prescribed']}) [Client: {$fv['client_code']}]";
        $insert = $pdo->prepare("
            INSERT INTO patient_appointments 
            (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
            VALUES (?, ?, 4, ?, '09:00:00', ?, ?, 'Midwife Corazon Rivera, RM', ?)
        ");
        $insert->execute([$aptCode, $fv['patient_id'], $fv['next_appointment_date'], $status, $reason, $defaultUserId]);
        $fpInserted++;
    }
}
echo "Synced {$fpInserted} Family Planning appointments.\n";

// 3. Sync Maternal Prenatal / Postnatal Reminders into patient_appointments
echo "3. Checking Maternal Reminders...\n";
$maternalReminders = $pdo->query("
    SELECT patient_id, reminder_type, due_date, message 
    FROM reminders 
    WHERE reminder_type IN ('prenatal', 'postnatal')
")->fetchAll(PDO::FETCH_ASSOC);

$maternalInserted = 0;
foreach ($maternalReminders as $mr) {
    $serviceId = ($mr['reminder_type'] === 'postnatal') ? 3 : 2;
    $check = $pdo->prepare("SELECT id FROM patient_appointments WHERE patient_id = ? AND service_id = ? AND appointment_date = ?");
    $check->execute([$mr['patient_id'], $serviceId, $mr['due_date']]);
    if (!$check->fetchColumn()) {
        $aptCode = generateAppointmentCode($pdo, $year);
        $isPast = ($mr['due_date'] < date('Y-m-d'));
        $status = $isPast ? 'completed' : 'scheduled';
        $reason = ($serviceId === 3) ? "Postnatal & Infant Recovery Checkup" : "Maternal & Prenatal Health Check-up";
        $insert = $pdo->prepare("
            INSERT INTO patient_appointments 
            (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
            VALUES (?, ?, ?, ?, '09:30:00', ?, ?, 'Midwife Corazon Rivera, RM', ?)
        ");
        $insert->execute([$aptCode, $mr['patient_id'], $serviceId, $mr['due_date'], $status, $reason, $defaultUserId]);
        $maternalInserted++;
    }
}
echo "Synced {$maternalInserted} Maternal appointments.\n";

$totalNow = (int)$pdo->query("SELECT COUNT(*) FROM patient_appointments")->fetchColumn();
echo "--- Sync Complete! Total patient appointments now: {$totalNow} ---\n";
