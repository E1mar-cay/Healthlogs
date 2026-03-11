<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pregnancy_id = (int)($_POST['pregnancy_id'] ?? 0);
$visit_datetime = $_POST['visit_datetime'] ?? date('Y-m-d H:i:s');
$mother = $_POST['mother_condition'] ?? null;
$baby = $_POST['baby_condition'] ?? null;
$notes = $_POST['notes'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE postnatal_visits SET pregnancy_id = ?, visit_datetime = ?, mother_condition = ?, baby_condition = ?, notes = ? WHERE id = ?");
    $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO postnatal_visits (pregnancy_id, visit_datetime, mother_condition, baby_condition, notes) VALUES (?,?,?,?,?)");
    $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes]);
}

header('Location: /HealthLogs/public/maternal/postnatal/index.php');
exit;
