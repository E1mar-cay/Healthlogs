<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/SchedulerSettings.php';

echo "=== DIAGNOSTIC REPORT ===\n";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Scheduled Time in config: " . SchedulerSettings::getScheduledTime() . " (" . SchedulerSettings::getFormattedTime() . ")\n";

$settingsFile = __DIR__ . '/../storage/scheduler_settings.json';
if (file_exists($settingsFile)) {
    echo "Settings File content: " . file_get_contents($settingsFile) . "\n";
} else {
    echo "Settings File does not exist!\n";
}

$stmt = $pdo->query("SELECT r.id, r.patient_id, r.due_date, r.status, r.sent_at, p.first_name, p.last_name, p.contact_no 
                     FROM reminders r 
                     LEFT JOIN patients p ON p.id = r.patient_id 
                     ORDER BY r.id DESC");
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nReminders in DB (" . count($reminders) . " total):\n";
foreach ($reminders as $r) {
    echo "  ID: {$r['id']} | Status: [{$r['status']}] | Due: {$r['due_date']} | Patient: {$r['first_name']} {$r['last_name']} | Phone: {$r['contact_no']} | Sent At: {$r['sent_at']}\n";
}

$pendingCount = 0;
foreach ($reminders as $r) {
    if ($r['status'] === 'pending') {
        $pendingCount++;
    }
}
echo "\nTotal Pending: {$pendingCount}\n";
