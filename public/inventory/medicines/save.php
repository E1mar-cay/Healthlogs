<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$name = $_POST['name'] ?? '';
$generic = $_POST['generic_name'] ?? null;
$formulation = $_POST['formulation'] ?? null;
$strength = $_POST['strength'] ?? null;
$unit = $_POST['unit'] ?? '';
$reorder = $_POST['reorder_level'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE medicines SET name = ?, generic_name = ?, formulation = ?, strength = ?, unit = ?, reorder_level = ? WHERE id = ?");
        $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder, $id]);
        $_SESSION['success_message'] = 'Medicine updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder]);
        $_SESSION['success_message'] = 'Medicine created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Medicine save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the medicine. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/inventory/medicines/form_embed.php?id=$id" : "/HealthLogs/public/inventory/medicines/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/inventory/medicines/form_embed.php" : "/HealthLogs/public/inventory/medicines/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
