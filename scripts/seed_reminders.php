<?php
// Seed demo data for reminders.

require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

// Create demo patient if none exists
$patient = $pdo->query("SELECT id FROM patients ORDER BY id DESC LIMIT 1")->fetch();
if (!$patient) {
    $stmt = $pdo->prepare("INSERT INTO patients (
        first_name, last_name, sex, birth_date,
        barangay, city_municipality, province, status
    ) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        'Juan', 'Dela Cruz', 'male', '2010-05-10',
        'Poblacion', 'Sample City', 'Sample Province', 'active'
    ]);
    $patientId = (int)$pdo->lastInsertId();
} else {
    $patientId = (int)$patient['id'];
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$seed = $pdo->prepare("INSERT INTO reminders (patient_id, reminder_type, due_date, message, status) VALUES (?,?,?,?,?)");
$seed->execute([$patientId, 'immunization', $today, 'Vaccination due today.', 'pending']);
$seed->execute([$patientId, 'prenatal', $tomorrow, 'Prenatal checkup tomorrow.', 'pending']);
$seed->execute([$patientId, 'tb', $today, 'TB follow-up today.', 'pending']);

echo "Seeded reminders for patient ID {$patientId}\n";
