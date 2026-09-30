<?php
require __DIR__ . '/../partials/bootstrap.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    // Fetch patient name before deleting
    $pStmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE id = ?");
    $pStmt->execute([$id]);
    $patient = $pStmt->fetch();
    $fullName = $patient ? trim($patient['first_name'] . ' ' . $patient['last_name']) : "ID #{$id}";

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE pv FROM prenatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE pv FROM postnatal_visits pv INNER JOIN pregnancies p ON pv.pregnancy_id = p.id WHERE p.patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM immunization_records WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM immunization_schedule WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patient_allergies WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patient_conditions WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM pregnancies WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM reminders WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM visits WHERE patient_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
        $pdo->commit();

        ActivityLogger::logClinical('patients', 'delete', "Deleted patient record: {$fullName}", $id, [
            'patient_id' => $id,
            'full_name' => $fullName
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error deleting patient: " . $e->getMessage());
    }
}

header('Location: /HealthLogs/public/patients/index.php');
exit;
