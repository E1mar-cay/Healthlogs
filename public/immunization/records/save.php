<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$returnTo = trim($_POST['return_to'] ?? '');

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);

$admin_on = !empty($_POST['administered_on']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['administered_on']) 
    ? $_POST['administered_on'] 
    : date('Y-m-d');

$admin_time = !empty($_POST['administered_time']) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $_POST['administered_time'])
    ? $_POST['administered_time'] . ':00'
    : date('H:i:s');

$admin_at = $admin_on . ' ' . $admin_time;
$lot_no = trim($_POST['lot_no'] ?? '') ?: null;
$notes = trim($_POST['notes'] ?? '') ?: null;

try {
    if ($patient_id <= 0 || $vaccine_id <= 0) {
        throw new InvalidArgumentException('Please select a valid patient and vaccine.');
    }

    $pdo->beginTransaction();

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE immunization_records 
            SET patient_id = ?, vaccine_id = ?, dose_no = ?, administered_on = ?, administered_at = ?, lot_no = ?, notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, $admin_at, $lot_no, $notes, $id]);
        $_SESSION['success_message'] = 'Immunization record updated successfully';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO immunization_records (patient_id, vaccine_id, dose_no, administered_on, administered_at, lot_no, notes) 
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $admin_on, $admin_at, $lot_no, $notes]);
        $_SESSION['success_message'] = 'Immunization record saved successfully';
    }

    // Auto-update matching pending schedule if any exists
    $updateSched = $pdo->prepare("
        UPDATE immunization_schedule 
        SET status = 'administered' 
        WHERE patient_id = ? AND vaccine_id = ? AND dose_no = ? AND status = 'scheduled'
    ");
    $updateSched->execute([$patient_id, $vaccine_id, $dose_no]);

    // Auto-complete corresponding pending reminder if any
    $updateRem = $pdo->prepare("
        UPDATE reminders 
        SET status = 'completed' 
        WHERE patient_id = ? AND reminder_type = 'immunization' AND status = 'pending'
    ");
    $updateRem->execute([$patient_id]);

    $pdo->commit();
    unset($_SESSION['old_input']);

    if ($returnTo === 'tcl') {
        header('Location: /HealthLogs/public/immunization/tcl.php');
        exit;
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Immunization record save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the immunization record: ' . $e->getMessage();
    $_SESSION['old_input'] = $_POST;
    
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/immunization/records/form_embed.php?id=$id" : "/HealthLogs/public/immunization/records/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/immunization/records/form_embed.php" : "/HealthLogs/public/immunization/records/form.php");
        
    if ($returnTo) {
        $redirectUrl .= (str_contains($redirectUrl, '?') ? '&' : '?') . 'return_to=' . urlencode($returnTo);
    }
    header("Location: $redirectUrl");
    exit;
}

if ($isEmbed && !$returnTo) {
    echo "<script>if (window.parent && window.parent !== window) { window.parent.location.reload(); } else { window.location.href = '/HealthLogs/public/immunization/records/index.php'; }</script>";
    exit;
}

header('Location: /HealthLogs/public/immunization/records/index.php');
exit;
