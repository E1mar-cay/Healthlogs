<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);
$admin_on = $_POST['administered_on'] ?? date('Y-m-d');
$admin_at = $_POST['administered_at'] ?? date('Y-m-d H:i:s');
$lot_no = $_POST['lot_no'] ?? null;
$notes = $_POST['notes'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE immunization_records SET patient_id = ?, vaccine_id = ?, dose_no = ?, administered_on = ?, administered_at = ?, lot_no = ?, notes = ? WHERE id = ?");
    $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, str_replace('T', ' ', $admin_at), $lot_no, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO immunization_records (patient_id, vaccine_id, dose_no, administered_on, administered_at, lot_no, notes) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, str_replace('T', ' ', $admin_at), $lot_no, $notes]);
}

header('Location: /HealthLogs/public/immunization/records/index.php');
exit;
