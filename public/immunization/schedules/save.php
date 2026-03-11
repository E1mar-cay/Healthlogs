<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);
$scheduled_date = $_POST['scheduled_date'] ?? date('Y-m-d');
$status = $_POST['status'] ?? 'scheduled';

if ($id) {
    $stmt = $pdo->prepare("UPDATE immunization_schedule SET patient_id = ?, vaccine_id = ?, dose_no = ?, scheduled_date = ?, status = ? WHERE id = ?");
    $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status]);
}

header('Location: /HealthLogs/public/immunization/schedules/index.php');
exit;
