<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE pv FROM prenatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE pv FROM postnatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE tf FROM tb_followups tf INNER JOIN tb_cases tc ON tf.tb_case_id = tc.id WHERE tc.patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM immunization_records WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM immunization_schedule WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patient_allergies WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patient_conditions WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM pregnancies WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM reminders WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM tb_cases WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM visits WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting patient: " . $e->getMessage());
    }
}

header('Location: /HealthLogs/public/patients/index.php');
exit;
