<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$name = trim($_POST['name'] ?? '');
$generic = !empty($_POST['generic_name']) ? trim($_POST['generic_name']) : null;
$formulation = !empty($_POST['formulation']) ? trim($_POST['formulation']) : null;
$strength = !empty($_POST['strength']) ? trim($_POST['strength']) : null;
$unit = trim($_POST['unit'] ?? 'pcs');
$reorder = isset($_POST['reorder_level']) && $_POST['reorder_level'] !== '' ? (int)$_POST['reorder_level'] : 100;

try {
    if ($id) {
        // Fetch old data for audit trail comparison
        $oldMed = ActivityLogger::getMedicineInfo($id);

        $stmt = $pdo->prepare("UPDATE medicines SET name = ?, generic_name = ?, formulation = ?, strength = ?, unit = ?, reorder_level = ? WHERE id = ?");
        $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder, $id]);

        ActivityLogger::logInventory('medicine_update', "Updated medicine profile: {$name}", 'medicines', (string)$id, [
            'medicine_id' => $id,
            'medicine_name' => $name,
            'old' => [
                'name' => $oldMed['name'] ?? null,
                'generic_name' => $oldMed['generic_name'] ?? null,
                'formulation' => $oldMed['formulation'] ?? null,
                'strength' => $oldMed['strength'] ?? null,
                'unit' => $oldMed['unit'] ?? null,
                'reorder_level' => $oldMed['reorder_level'] ?? null
            ],
            'new' => [
                'name' => $name,
                'generic_name' => $generic,
                'formulation' => $formulation,
                'strength' => $strength,
                'unit' => $unit,
                'reorder_level' => $reorder
            ]
        ]);

        $_SESSION['success_message'] = 'Medicine updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $generic, $formulation, $strength, $unit, $reorder]);
        $newId = (int)$pdo->lastInsertId();

        ActivityLogger::logInventory('medicine_add', "Added new medicine: {$name}" . ($strength ? " ({$strength})" : ""), 'medicines', (string)$newId, [
            'medicine_id' => $newId,
            'name' => $name,
            'generic_name' => $generic,
            'formulation' => $formulation,
            'strength' => $strength,
            'unit' => $unit,
            'reorder_level' => $reorder
        ]);

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
