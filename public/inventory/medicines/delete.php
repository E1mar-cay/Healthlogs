<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    $_SESSION['error_message'] = 'Medicine not found';
    header('Location: /HealthLogs/public/inventory/medicines/index.php');
    exit;
}

try {
    $med = ActivityLogger::getMedicineInfo($id);
    $medName = $med['name'] ?? "ID #{$id}";

    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);

    ActivityLogger::logInventory('medicine_delete', "Deleted medicine record: {$medName}", 'medicines', (string)$id, [
        'medicine_id' => $id,
        'medicine_name' => $medName,
        'deleted_record' => $med
    ]);

    $_SESSION['success_message'] = 'Medicine deleted successfully';
} catch (Throwable $e) {
    error_log("Medicine delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the medicine. Please try again.';
}

header('Location: /HealthLogs/public/inventory/medicines/index.php');
exit;
