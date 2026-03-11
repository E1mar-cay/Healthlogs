<?php
// Cron job: send reminders due today or earlier.

require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

$sql = "SELECT * FROM reminders WHERE status = 'pending' AND due_date <= ? ORDER BY due_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$today]);
$reminders = $stmt->fetchAll();

foreach ($reminders as $r) {
    // TODO: integrate SMS/Email provider here.
    $update = $pdo->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?");
    $update->execute([$r['id']]);
}

echo 'Processed ' . count($reminders) . " reminders\n";
