<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$dose_no = (int)($_POST['dose_no'] ?? 1);
$scheduled_date = $_POST['scheduled_date'] ?? date('Y-m-d');
$status = $_POST['status'] ?? 'scheduled';

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE immunization_schedule SET patient_id = ?, vaccine_id = ?, dose_no = ?, scheduled_date = ?, status = ? WHERE id = ?");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status, $id]);
        $_SESSION['success_message'] = 'Schedule updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO immunization_schedule (patient_id, vaccine_id, dose_no, scheduled_date, status) VALUES (?,?,?,?,?)");
        $stmt->execute([$patient_id, $vaccine_id, $dose_no, $scheduled_date, $status]);
        $_SESSION['success_message'] = 'Schedule created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Immunization schedule save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the schedule. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/immunization/schedules/form_embed.php?id=$id" : "/HealthLogs/public/immunization/schedules/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/immunization/schedules/form_embed.php" : "/HealthLogs/public/immunization/schedules/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/immunization/schedules/index.php');
exit;
