<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$tb_case_id = (int)($_POST['tb_case_id'] ?? 0);
$followup_datetime = $_POST['followup_datetime'] ?? date('Y-m-d H:i:s');
$adherence = $_POST['adherence'] ?? 'good';
$weight = $_POST['weight_kg'] ?? null;
$symptoms = $_POST['symptoms'] ?? null;
$notes = $_POST['notes'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE tb_followups SET tb_case_id = ?, followup_datetime = ?, adherence = ?, weight_kg = ?, symptoms = ?, notes = ? WHERE id = ?");
    $stmt->execute([$tb_case_id, str_replace('T', ' ', $followup_datetime), $adherence, $weight, $symptoms, $notes, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO tb_followups (tb_case_id, followup_datetime, adherence, weight_kg, symptoms, notes) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$tb_case_id, str_replace('T', ' ', $followup_datetime), $adherence, $weight, $symptoms, $notes]);
}

header('Location: /HealthLogs/public/tb/followups/index.php');
exit;
