<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$lmp_date = $_POST['lmp_date'] ?? date('Y-m-d');
$edd_date = $_POST['edd_date'] ?? date('Y-m-d');
$gravida = $_POST['gravida'] ?? null;
$para = $_POST['para'] ?? null;
$status = $_POST['status'] ?? 'ongoing';

if ($id) {
    $stmt = $pdo->prepare("UPDATE pregnancies SET patient_id = ?, lmp_date = ?, edd_date = ?, gravida = ?, para = ?, status = ? WHERE id = ?");
    $stmt->execute([$patient_id, $lmp_date, $edd_date, $gravida, $para, $status, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO pregnancies (patient_id, lmp_date, edd_date, gravida, para, status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$patient_id, $lmp_date, $edd_date, $gravida, $para, $status]);
}

header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
exit;
