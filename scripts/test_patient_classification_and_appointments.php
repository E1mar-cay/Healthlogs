<?php
/**
 * Automated Test Suite for:
 * 1. Patient Classification & RHU Priority Groups (Babies, Under-5, Pregnant, Seniors, PWD, 4Ps)
 * 2. Barangay Rural Health Unit Services List
 * 3. Comprehensive Patient Records & Clinical Chart Integration
 * 4. Patient Appointments Lifecycle (Schedule, Reschedule, Complete, Cancel, Monitor)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/PatientClassifier.php';
require_once __DIR__ . '/../app/Core/ActivityLogger.php';
ActivityLogger::init($pdo);

echo "======================================================================\n";
echo " TESTING PATIENT CLASSIFICATION, SERVICES & APPOINTMENTS LIFECYCLE\n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

// 1. Patient Classification & Age Engine Tests
echo "1. Testing Patient Classification & Age Engine...\n";
$today = new DateTime();

// Test Baby / Infant (< 12 months)
$babyDob = (clone $today)->modify('-6 months')->format('Y-m-d');
$babyClass = PatientClassifier::detectClassification($babyDob, 'female');
$babyAgeStr = PatientClassifier::formatAge($babyDob);
assertTest($babyClass === 'infant', "6-month-old child classified as 'infant' (Baby)");
assertTest(strpos($babyAgeStr, '6 mos') !== false, "Baby age formatted with exact months: '{$babyAgeStr}'");

// Test Under-Five (1-4 years)
$under5Dob = (clone $today)->modify('-3 years -2 months')->format('Y-m-d');
$under5Class = PatientClassifier::detectClassification($under5Dob, 'male');
$under5AgeStr = PatientClassifier::formatAge($under5Dob);
assertTest($under5Class === 'under_five', "3-year-old child classified as 'under_five'");
assertTest(strpos($under5AgeStr, '3 yrs') !== false, "Under-5 age formatted as: '{$under5AgeStr}'");

// Test Pregnant Mother
$momDob = (clone $today)->modify('-26 years')->format('Y-m-d');
$pregnantClass = PatientClassifier::detectClassification($momDob, 'female', true);
assertTest($pregnantClass === 'pregnant', "Expectant mother classified as 'pregnant'");

// Test Senior Citizen (60+)
$seniorDob = (clone $today)->modify('-68 years')->format('Y-m-d');
$seniorClass = PatientClassifier::detectClassification($seniorDob, 'male');
assertTest($seniorClass === 'senior', "68-year-old person classified as 'senior'");

// Test Priority Groups (PWD & 4Ps)
$pwdClass = PatientClassifier::detectClassification($momDob, 'female', false, true);
assertTest($pwdClass === 'pwd', "Patient with disability flagged as 'pwd'");

// 2. Health Services Catalog Verification
echo "\n2. Testing RHU Health Services Catalog...\n";
$services = $pdo->query("SELECT * FROM health_services WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
$serviceCodes = array_column($services, 'service_code');
assertTest(count($services) >= 10, "Found " . count($services) . " active health services in RHU catalog");
assertTest(in_array('EPI_VACCINE', $serviceCodes), "Expanded Program on Immunization (EPI) routine vaccine service exists");
assertTest(in_array('PRENATAL_CARE', $serviceCodes), "Maternal & Prenatal Health check-up service exists");
assertTest(in_array('FAMILY_PLANNING', $serviceCodes), "Family Planning counseling & contraceptive service exists");
assertTest(in_array('NCD_CHECKUP', $serviceCodes), "Hypertension & Diabetes NCD checkup service exists");
assertTest(in_array('CHILD_NUTRITION', $serviceCodes), "Under-5 Child Nutrition & Deworming service exists");

// 3. Database Patient Registry & Classification Backfill
echo "\n3. Testing Patients Database Structure & Data Integrity...\n";
$patientsCount = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
assertTest($patientsCount > 0, "Patient registry contains {$patientsCount} registered individuals");

$babyCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE classification = 'infant'")->fetchColumn();
$under5Count = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE classification = 'under_five'")->fetchColumn();
$pregCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE classification = 'pregnant'")->fetchColumn();
$seniorCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE classification = 'senior'")->fetchColumn();
$pwdCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE is_pwd = 1")->fetchColumn();
$fourPsCount = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE is_4ps = 1")->fetchColumn();

assertTest($babyCount > 0, "Found {$babyCount} Baby/Infant records in registry");
assertTest($under5Count > 0, "Found {$under5Count} Under-5 children in registry");
assertTest($pregCount > 0, "Found {$pregCount} Pregnant mothers in registry");
assertTest($seniorCount > 0, "Found {$seniorCount} Senior citizens in registry");
assertTest($pwdCount > 0, "Found {$pwdCount} PWD patients in registry");
assertTest($fourPsCount > 0, "Found {$fourPsCount} 4Ps indigent priority beneficiaries in registry");

// 4. Appointments Lifecycle Tests (Schedule -> Reschedule -> Complete -> Cancel)
echo "\n4. Testing Patient Appointments Full Lifecycle...\n";

// A. SCHEDULE
$testPatientId = (int)$pdo->query("SELECT id FROM patients WHERE classification = 'infant' LIMIT 1")->fetchColumn();
$epiServiceId = (int)$pdo->query("SELECT id FROM health_services WHERE service_code = 'EPI_VACCINE' LIMIT 1")->fetchColumn();
$staffUserId = (int)$pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();

$aptCode = 'APT-TEST-' . time();
$scheduledDate = date('Y-m-d', strtotime('+2 days'));

$stmt = $pdo->prepare("INSERT INTO patient_appointments 
    (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
    VALUES (?, ?, ?, ?, '09:00:00', 'scheduled', 'Baby Pentavalent 3rd dose routine follow-up', 'Nurse Maricar Ramos', ?)");
$stmt->execute([$aptCode, $testPatientId, $epiServiceId, $scheduledDate, $staffUserId]);
$aptId = (int)$pdo->lastInsertId();

assertTest($aptId > 0, "Successfully SCHEDULED appointment {$aptCode} (ID: {$aptId})");

// Verify activity log
$logSched = ActivityLogger::logAppointment('schedule', "Scheduled test appointment {$aptCode}", (string)$aptId, [
    'appointment_code' => $aptCode,
    'patient_id' => $testPatientId
]);
assertTest($logSched > 0, "Recorded audit trail in activity_logs for appointment scheduling");

// B. RESCHEDULE
$newDate = date('Y-m-d', strtotime('+4 days'));
$reschedReason = 'Mother had conflicting barangay assembly meeting';
$reschedStmt = $pdo->prepare("UPDATE patient_appointments SET 
    appointment_date = ?, 
    previous_appointment_date = ?, 
    reschedule_reason = ?, 
    status = 'rescheduled' 
    WHERE id = ?");
$reschedStmt->execute([$newDate, $scheduledDate, $reschedReason, $aptId]);

$verifyResched = $pdo->prepare("SELECT * FROM patient_appointments WHERE id = ?");
$verifyResched->execute([$aptId]);
$reschedRow = $verifyResched->fetch(PDO::FETCH_ASSOC);

assertTest($reschedRow['status'] === 'rescheduled', "Appointment status transitioned to 'rescheduled'");
assertTest($reschedRow['appointment_date'] === $newDate, "Appointment date updated to '{$newDate}'");
assertTest($reschedRow['previous_appointment_date'] === $scheduledDate, "Previous date captured as '{$scheduledDate}'");
assertTest($reschedRow['reschedule_reason'] === $reschedReason, "Reschedule reason recorded: '{$reschedReason}'");

// C. COMPLETE
$clinicalNotes = 'Vaccine administered without adverse reaction. Weight: 7.2kg. Paracetamol drops advised for fever prophylaxis.';
$compStmt = $pdo->prepare("UPDATE patient_appointments SET 
    status = 'completed', 
    completed_at = NOW(), 
    clinical_notes = ? 
    WHERE id = ?");
$compStmt->execute([$clinicalNotes, $aptId]);

$verifyComp = $pdo->prepare("SELECT * FROM patient_appointments WHERE id = ?");
$verifyComp->execute([$aptId]);
$compRow = $verifyComp->fetch(PDO::FETCH_ASSOC);

assertTest($compRow['status'] === 'completed', "Appointment status transitioned to 'completed'");
assertTest(!empty($compRow['completed_at']), "Completion timestamp recorded");
assertTest(strpos($compRow['clinical_notes'], 'Paracetamol') !== false, "Clinical outcome notes saved");

// D. CANCEL
$cancCode = 'APT-CANC-' . time();
$cancStmt = $pdo->prepare("INSERT INTO patient_appointments 
    (appointment_code, patient_id, service_id, appointment_date, appointment_time, status, reason, assigned_personnel, created_by)
    VALUES (?, ?, ?, CURDATE(), '11:00:00', 'scheduled', 'Initial checkup', 'BHW Rosanna', ?)");
$cancStmt->execute([$cancCode, $testPatientId, $epiServiceId, $staffUserId]);
$cancId = (int)$pdo->lastInsertId();

$cancReason = 'Patient relocated to municipality center';
$cancelUpdate = $pdo->prepare("UPDATE patient_appointments SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
$cancelUpdate->execute([$cancReason, $cancId]);

$verifyCancel = $pdo->prepare("SELECT * FROM patient_appointments WHERE id = ?");
$verifyCancel->execute([$cancId]);
$cancRow = $verifyCancel->fetch(PDO::FETCH_ASSOC);

assertTest($cancRow['status'] === 'cancelled', "Appointment status transitioned to 'cancelled'");
assertTest($cancRow['cancellation_reason'] === $cancReason, "Cancellation reason recorded: '{$cancReason}'");

// Clean up test rows
$pdo->prepare("DELETE FROM patient_appointments WHERE id IN (?, ?)")->execute([$aptId, $cancId]);
$pdo->prepare("DELETE FROM activity_logs WHERE id = ?")->execute([$logSched]);

// 5. Comprehensive Patient Record Chart Query Integration
echo "\n5. Testing Master Patient Chart Query Aggregation...\n";
$chartStmt = $pdo->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM patient_allergies WHERE patient_id = p.id) AS allergy_count,
           (SELECT COUNT(*) FROM patient_conditions WHERE patient_id = p.id) AS condition_count,
           (SELECT COUNT(*) FROM visits WHERE patient_id = p.id) AS visit_count,
           (SELECT COUNT(*) FROM immunization_records WHERE patient_id = p.id) AS vaccine_count,
           (SELECT COUNT(*) FROM patient_appointments WHERE patient_id = p.id) AS appointment_count
    FROM patients p
    WHERE p.id = ?
");
$chartStmt->execute([$testPatientId]);
$chartData = $chartStmt->fetch(PDO::FETCH_ASSOC);

assertTest(!empty($chartData), "Master patient chart query successfully assembled clinical profile");
assertTest(isset($chartData['allergy_count'], $chartData['condition_count'], $chartData['visit_count'], $chartData['vaccine_count'], $chartData['appointment_count']), "Chart aggregates allergies, conditions, visits, vaccines, and appointments");

echo "\n======================================================================\n";
echo " TEST SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
