<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$name = $_POST['name'] ?? '';
$code = $_POST['code'] ?? '';
$min = $_POST['recommended_min_age_months'] ?? null;
$max = $_POST['recommended_max_age_months'] ?? null;
$doses = $_POST['doses_required'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE vaccines SET name = ?, code = ?, recommended_min_age_months = ?, recommended_max_age_months = ?, doses_required = ? WHERE id = ?");
        $stmt->execute([$name, $code, $min, $max, $doses, $id]);
        $_SESSION['success_message'] = 'Vaccine updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO vaccines (name, code, recommended_min_age_months, recommended_max_age_months, doses_required) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $code, $min, $max, $doses]);
        $_SESSION['success_message'] = 'Vaccine created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Vaccine save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the vaccine. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/immunization/vaccines/form_embed.php?id=$id" : "/HealthLogs/public/immunization/vaccines/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/immunization/vaccines/form_embed.php" : "/HealthLogs/public/immunization/vaccines/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/immunization/vaccines/index.php');
exit;
