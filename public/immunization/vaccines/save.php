<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$name = $_POST['name'] ?? '';
$code = $_POST['code'] ?? '';
$min = $_POST['recommended_min_age_months'] ?? null;
$max = $_POST['recommended_max_age_months'] ?? null;
$doses = $_POST['doses_required'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE vaccines SET name = ?, code = ?, recommended_min_age_months = ?, recommended_max_age_months = ?, doses_required = ? WHERE id = ?");
    $stmt->execute([$name, $code, $min, $max, $doses, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO vaccines (name, code, recommended_min_age_months, recommended_max_age_months, doses_required) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $code, $min, $max, $doses]);
}

header('Location: /HealthLogs/public/immunization/vaccines/index.php');
exit;
