<?php
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

function table_count(PDO $pdo, string $table): int {
    return (int)$pdo->query("SELECT COUNT(*) AS c FROM {$table}")->fetch()['c'];
}

// Seed vaccines
if (table_count($pdo, 'vaccines') === 0) {
    $vaccines = [
        ['BCG', 'BCG', 0, 1, 1],
        ['Hepatitis B', 'HEPB', 0, 1, 1],
        ['Pentavalent', 'PENTA', 2, 6, 3],
        ['Oral Polio Vaccine', 'OPV', 2, 6, 3],
        ['Measles, Mumps, Rubella', 'MMR', 9, 24, 2],
    ];
    $stmt = $pdo->prepare("INSERT INTO vaccines (name, code, recommended_min_age_months, recommended_max_age_months, doses_required) VALUES (?,?,?,?,?)");
    foreach ($vaccines as $v) {
        $stmt->execute($v);
    }
    echo "Seeded vaccines\n";
}

// Seed medicines
if (table_count($pdo, 'medicines') === 0) {
    $meds = [
        ['Amoxicillin', 'Amoxicillin', 'Capsule', '500mg', 'capsule', 100],
        ['Paracetamol', 'Paracetamol', 'Tablet', '500mg', 'tablet', 200],
        ['ORS', 'Oral Rehydration Salts', 'Sachet', '4.1g', 'sachet', 150],
        ['Isoniazid', 'Isoniazid', 'Tablet', '300mg', 'tablet', 120],
    ];
    $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES (?,?,?,?,?,?)");
    foreach ($meds as $m) {
        $stmt->execute($m);
    }
    echo "Seeded medicines\n";
}

// Seed one household + patient if none
if (table_count($pdo, 'patients') === 0) {
    $pdo->exec("INSERT INTO households (household_code, head_name, address_line, barangay, city_municipality, province) VALUES ('HH-001','Maria Cruz','Purok 1','Poblacion','Sample City','Sample Province')");
    $householdId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO patients (household_id, first_name, last_name, sex, birth_date, barangay, city_municipality, province, status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$householdId, 'Juan', 'Dela Cruz', 'male', '2019-06-10', 'Poblacion', 'Sample City', 'Sample Province', 'active']);
    echo "Seeded patient\n";
}

// Fetch IDs
$patientId = (int)$pdo->query("SELECT id FROM patients ORDER BY id DESC LIMIT 1")->fetch()['id'];
$vaccineId = (int)$pdo->query("SELECT id FROM vaccines ORDER BY id DESC LIMIT 1")->fetch()['id'];
$medicineId = (int)$pdo->query("SELECT id FROM medicines ORDER BY id DESC LIMIT 1")->fetch()['id'];

// Seed immunization schedule/records
if (table_count($pdo, 'immunization_schedule') === 0) {
    $stmt = $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$patientId, $vaccineId, 1, date('Y-m-d', strtotime('+7 days')), 'scheduled']);
    echo "Seeded immunization schedule\n";
}

if (table_count($pdo, 'immunization_records') === 0) {
    $stmt = $pdo->prepare("INSERT INTO immunization_records (patient_id, vaccine_id, dose_no, administered_on, administered_at, lot_no, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$patientId, $vaccineId, 1, date('Y-m-d', strtotime('-30 days')), date('Y-m-d H:i:s', strtotime('-30 days')), 'LOT-001', 'Initial dose']);
    echo "Seeded immunization records\n";
}

// Seed pregnancy and visits
if (table_count($pdo, 'pregnancies') === 0) {
    $stmt = $pdo->prepare("INSERT INTO pregnancies (patient_id, lmp_date, edd_date, gravida, para, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$patientId, date('Y-m-d', strtotime('-90 days')), date('Y-m-d', strtotime('+190 days')), 1, 0, 'ongoing']);
    echo "Seeded pregnancy\n";
}
$pregId = (int)$pdo->query("SELECT id FROM pregnancies ORDER BY id DESC LIMIT 1")->fetch()['id'];
if (table_count($pdo, 'prenatal_visits') === 0) {
    $stmt = $pdo->prepare("INSERT INTO prenatal_visits (pregnancy_id, visit_datetime, gestational_age_weeks, bp_systolic, bp_diastolic, weight_kg, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$pregId, date('Y-m-d H:i:s', strtotime('-7 days')), 12, 110, 70, 52.5, 'Routine check']);
    echo "Seeded prenatal visit\n";
}

// Seed TB case and follow-up
if (table_count($pdo, 'tb_cases') === 0) {
    $stmt = $pdo->prepare("INSERT INTO tb_cases (patient_id, case_no, diagnosis_date, case_type, status, treatment_start, treatment_end, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$patientId, 'TB-001', date('Y-m-d', strtotime('-60 days')), 'drug_susceptible', 'active', date('Y-m-d', strtotime('-55 days')), null, 'On treatment']);
    echo "Seeded TB case\n";
}
$tbId = (int)$pdo->query("SELECT id FROM tb_cases ORDER BY id DESC LIMIT 1")->fetch()['id'];
if (table_count($pdo, 'tb_followups') === 0) {
    $stmt = $pdo->prepare("INSERT INTO tb_followups (tb_case_id, followup_datetime, adherence, weight_kg, symptoms, notes) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$tbId, date('Y-m-d H:i:s', strtotime('-3 days')), 'good', 54.2, 'Mild cough', 'Improving']);
    echo "Seeded TB follow-up\n";
}

// Seed medicine batch + transactions
if (table_count($pdo, 'medicine_batches') === 0) {
    $stmt = $pdo->prepare("INSERT INTO medicine_batches (medicine_id, batch_no, expiry_date, received_date, quantity_received) VALUES (?,?,?,?,?)");
    $stmt->execute([$medicineId, 'BATCH-001', date('Y-m-d', strtotime('+365 days')), date('Y-m-d', strtotime('-10 days')), 500]);
    echo "Seeded medicine batch\n";
}
$batchId = (int)$pdo->query("SELECT id FROM medicine_batches ORDER BY id DESC LIMIT 1")->fetch()['id'];
if (table_count($pdo, 'medicine_transactions') === 0) {
    $stmt = $pdo->prepare("INSERT INTO medicine_transactions (medicine_id, batch_id, transaction_type, quantity, transaction_datetime, reference, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$medicineId, $batchId, 'received', 500, date('Y-m-d H:i:s', strtotime('-10 days')), 'PO-001', 'Initial stock']);
    $stmt->execute([$medicineId, $batchId, 'dispensed', 40, date('Y-m-d H:i:s', strtotime('-2 days')), 'RX-001', 'Dispensed to patients']);
    echo "Seeded medicine transactions\n";
}

// Seed reminders
if (table_count($pdo, 'reminders') === 0) {
    $stmt = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$patientId, 'immunization', date('Y-m-d'), 'Vaccination due today.', 'pending']);
    $stmt->execute([$patientId, 'prenatal', date('Y-m-d', strtotime('+1 day')), 'Prenatal checkup tomorrow.', 'pending']);
    echo "Seeded reminders\n";
}

echo "Done.\n";
