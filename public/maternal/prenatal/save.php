<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$pregnancy_id = (int)($_POST['pregnancy_id'] ?? 0);
$visit_datetime = $_POST['visit_datetime'] ?? date('Y-m-d H:i:s');
$ga = $_POST['gestational_age_weeks'] ?? null;
$sys = $_POST['bp_systolic'] ?? null;
$dia = $_POST['bp_diastolic'] ?? null;
$weight = $_POST['weight_kg'] ?? null;
$notes = $_POST['notes'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE prenatal_visits SET pregnancy_id = ?, visit_datetime = ?, gestational_age_weeks = ?, bp_systolic = ?, bp_diastolic = ?, weight_kg = ?, notes = ? WHERE id = ?");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $ga, $sys, $dia, $weight, $notes, $id]);
        $_SESSION['success_message'] = 'Prenatal visit updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO prenatal_visits (pregnancy_id, visit_datetime, gestational_age_weeks, bp_systolic, bp_diastolic, weight_kg, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$pregnancy_id, str_replace('T', ' ', $visit_datetime), $ga, $sys, $dia, $weight, $notes]);
        $_SESSION['success_message'] = 'Prenatal visit created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Prenatal save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the prenatal visit. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/maternal/prenatal/form_embed.php?id=$id" : "/HealthLogs/public/maternal/prenatal/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/maternal/prenatal/form_embed.php" : "/HealthLogs/public/maternal/prenatal/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/maternal/prenatal/index.php');
exit;
