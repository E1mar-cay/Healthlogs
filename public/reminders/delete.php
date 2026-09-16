<?php
require __DIR__ . '/../partials/bootstrap.php';

$ids = array_map('intval', (array)($_POST['ids'] ?? []));
if (isset($_POST['id'])) {
    $ids[] = (int)$_POST['id'];
}
$ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
if (empty($ids)) {
    $_SESSION['error_message'] = 'Reminder not found';
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $typeStmt = $pdo->prepare("SELECT id, reminder_type FROM reminders WHERE id IN ($placeholders)");
    $typeStmt->execute($ids);
    $immunizationIds = [];
    $deletableIds = [];
    foreach ($typeStmt->fetchAll(PDO::FETCH_ASSOC) as $reminder) {
        if ($reminder['reminder_type'] === 'immunization') {
            $immunizationIds[] = (int)$reminder['id'];
        } else {
            $deletableIds[] = (int)$reminder['id'];
        }
    }

    $pdo->beginTransaction();
    if (!empty($immunizationIds)) {
        $cancelPlaceholders = implode(',', array_fill(0, count($immunizationIds), '?'));
        $cancelStmt = $pdo->prepare("UPDATE reminders SET status = 'cancelled' WHERE id IN ($cancelPlaceholders)");
        $cancelStmt->execute($immunizationIds);
    }
    if (!empty($deletableIds)) {
        $deletePlaceholders = implode(',', array_fill(0, count($deletableIds), '?'));
        $deleteStmt = $pdo->prepare("DELETE FROM reminders WHERE id IN ($deletePlaceholders)");
        $deleteStmt->execute($deletableIds);
    }
    $pdo->commit();
    $deletedCount = count($immunizationIds) + count($deletableIds);
    $_SESSION['success_message'] = $deletedCount === 1
        ? 'Reminder deleted successfully'
        : "{$deletedCount} reminders deleted successfully";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reminder delete error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the reminder. Please try again.';
}

header('Location: /HealthLogs/public/reminders.php');
exit;
