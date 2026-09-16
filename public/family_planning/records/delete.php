<?php
require __DIR__ . '/../../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/family_planning/records/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid Family Planning record.');
    header('Location: /HealthLogs/public/family_planning/records/index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $recordStmt = $pdo->prepare('SELECT patient_id FROM fp_records WHERE id = ? FOR UPDATE');
    $recordStmt->execute([$id]);
    $record = $recordStmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new RuntimeException('Family Planning record not found.');
    }

    $deleteReminders = $pdo->prepare("DELETE FROM reminders WHERE patient_id = ? AND reminder_type = 'family_planning'");
    $deleteReminders->execute([(int)$record['patient_id']]);

    $deleteRecord = $pdo->prepare('DELETE FROM fp_records WHERE id = ?');
    $deleteRecord->execute([$id]);

    $pdo->commit();
    set_flash('success', 'Family Planning client and related visit history deleted successfully.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Family Planning record delete error: ' . $e->getMessage());
    set_flash('error', 'Unable to delete the Family Planning client.');
}

header('Location: /HealthLogs/public/family_planning/records/index.php');
exit;
