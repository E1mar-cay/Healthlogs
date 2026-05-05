<?php
require __DIR__ . '/../../partials/bootstrap.php';

$isEdit = !empty($_POST['id']);
$isEmbed = ($_POST['form_context'] ?? '') === 'embed';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$patient_id = (int)($_POST['patient_id'] ?? 0);
$lmp_date = $_POST['lmp_date'] ?? date('Y-m-d');
$edd_date = $_POST['edd_date'] ?? date('Y-m-d');
$gravida = $_POST['gravida'] ?? null;
$para = $_POST['para'] ?? null;
$status = $_POST['status'] ?? 'ongoing';

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE pregnancies SET patient_id = ?, lmp_date = ?, edd_date = ?, gravida = ?, para = ?, status = ? WHERE id = ?");
        $stmt->execute([$patient_id, $lmp_date, $edd_date, $gravida, $para, $status, $id]);
        $_SESSION['success_message'] = 'Pregnancy updated successfully';
    } else {
        $stmt = $pdo->prepare("INSERT INTO pregnancies (patient_id, lmp_date, edd_date, gravida, para, status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$patient_id, $lmp_date, $edd_date, $gravida, $para, $status]);
        $_SESSION['success_message'] = 'Pregnancy created successfully';
    }
    unset($_SESSION['old_input']);
} catch (Throwable $e) {
    error_log("Pregnancy save error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while saving the pregnancy. Please try again.';
    $_SESSION['old_input'] = $_POST;
    $redirectUrl = $isEdit
        ? ($isEmbed ? "/HealthLogs/public/maternal/pregnancies/form_embed.php?id=$id" : "/HealthLogs/public/maternal/pregnancies/form.php?id=$id")
        : ($isEmbed ? "/HealthLogs/public/maternal/pregnancies/form_embed.php" : "/HealthLogs/public/maternal/pregnancies/form.php");
    header("Location: $redirectUrl");
    exit;
}

header('Location: /HealthLogs/public/maternal/pregnancies/index.php');
exit;
