<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pregnancy_id = (int)($_POST['pregnancy_id'] ?? 0);
$visit_datetime = $_POST['visit_datetime'] ?? date('Y-m-d H:i:s');
$mother = $_POST['mother_condition'] ?? null;
$baby = $_POST['baby_condition'] ?? null;
$notes = $_POST['notes'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE postnatal_visits SET pregnancy_id = ?, visit_datetime = ?, mother_condition = ?, baby_condition = ?, notes = ? WHERE id = ?");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes, $id]);
        $_SESSION['success_message'] = 'Postnatal visit updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO postnatal_visits (pregnancy_id, visit_datetime, mother_condition, baby_condition, notes) VALUES (?,?,?,?,?)");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $mother, $baby, $notes]);
        $_SESSION['success_message'] = 'Postnatal visit created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Postnatal save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the postnatal visit. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/maternal/postnatal/form_embed.php?id=$id" : "/HealthLogs/public/maternal/postnatal/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/maternal/postnatal/form_embed.php" : "/HealthLogs/public/maternal/postnatal/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/maternal/postnatal/index.php');
exit;
