<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$case_no = $_POST['case_no'] ?? null;
$diagnosis_date = $_POST['diagnosis_date'] ?? date('Y-m-d');
$case_type = $_POST['case_type'] ?? 'drug_susceptible';
$status = $_POST['status'] ?? 'active';
$treatment_start = $_POST['treatment_start'] ?? date('Y-m-d');
$treatment_end = $_POST['treatment_end'] ?? null;
$notes = $_POST['notes'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE tb_cases SET patient_id = ?, case_no = ?, diagnosis_date = ?, case_type = ?, status = ?, treatment_start = ?, treatment_end = ?, notes = ? WHERE id = ?");
    $stmt->execute([$patient_id, $case_no, $diagnosis_date, $case_type, $status, $treatment_start, $treatment_end, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO tb_cases (patient_id, case_no, diagnosis_date, case_type, status, treatment_start, treatment_end, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$patient_id, $case_no, $diagnosis_date, $case_type, $status, $treatment_start, $treatment_end, $notes]);
}

header('Location: /HealthLogs/public/tb/cases/index.php');
exit;
