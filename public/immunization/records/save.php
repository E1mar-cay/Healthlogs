<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);
$admin_on = $_POST['administered_on'] ?? date('Y-m-d');
$admin_at = $_POST['administered_at'] ?? date('Y-m-d H:i:s');
$lot_no = $_POST['lot_no'] ?? null;
$notes = $_POST['notes'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE immunization_records SET patient_id = ?, vaccine_id = ?, dose_no = ?, administered_on = ?, administered_at = ?, lot_no = ?, notes = ? WHERE id = ?");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, str_replace('T', ' ', $admin_at), $lot_no, $notes, $id]);
        $_SESSION['success_message'] = 'Immunization record updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO immunization_records (patient_id, vaccine_id, dose_no, administered_on, administered_at, lot_no, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, str_replace('T', ' ', $admin_at), $lot_no, $notes]);
        $_SESSION['success_message'] = 'Immunization record created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Immunization record save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the immunization record. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/immunization/records/form_embed.php?id=$id" : "/HealthLogs/public/immunization/records/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/immunization/records/form_embed.php" : "/HealthLogs/public/immunization/records/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/immunization/records/index.php');
exit;
