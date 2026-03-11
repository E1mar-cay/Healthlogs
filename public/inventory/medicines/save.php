<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$name = $_POST['name'] ?? '';
$generic = $_POST['generic_name'] ?? null;
$formulation = $_POST['formulation'] ?? null;
$strength = $_POST['strength'] ?? null;
$unit = $_POST['unit'] ?? '';
$reorder = $_POST['reorder_level'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE medicines SET name = ?, generic_name = ?, formulation = ?, strength = ?, unit = ?, reorder_level = ? WHERE id = ?");
    $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder]);
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
