<?php
/**
 * Cron job: Send reminders due today or earlier via SMS and/or Email
 * 
 * Run this script daily via cron job or Windows Task Scheduler
 * Example: php C:\xampp\htdocs\HealthLogs\scripts\cron_reminders.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/EnvLoader.php';

// Load .env file
EnvLoader::load(__DIR__ . '/../.env');

// Load Helpers
require_once __DIR__ . '/../app/Core/SmsHelper.php';
require_once __DIR__ . '/../app/Core/SchedulerSettings.php';

date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');
$currentTime = date('H:i');
$scheduledTime = SchedulerSettings::getScheduledTime();
$force = in_array('--force', $argv ?? [], true);

echo "===========================================\n";
echo "HealthLogs SMS Reminder Cron Job\n";
echo "===========================================\n";
echo "Date:                " . date('Y-m-d H:i:s') . "\n";
echo "Scheduled Send Time: " . SchedulerSettings::getFormattedTime() . " ({$scheduledTime})\n";
echo "Current Time:        " . date('g:i A') . " ({$currentTime})\n";
if ($force) {
    echo "Mode:                Manual / Force Run\n";
}
echo "-------------------------------------------\n\n";

if (!$force && $currentTime < $scheduledTime) {
    echo "ℹ Current time ({$currentTime}) has not reached the scheduled dispatch time ({$scheduledTime}).\n";
    echo "Reminders will be dispatched after " . SchedulerSettings::getFormattedTime() . ".\n";
    echo "(Use --force or click 'Run Now' on the web dashboard to dispatch immediately)\n";
    exit(0);
}

// Get all pending reminders
$sql = "SELECT r.*, p.first_name, p.last_name, p.contact_no
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status = 'pending' 
        ORDER BY r.due_date ASC";

$stmt = $pdo->query($sql);
$reminders = $stmt->fetchAll();

echo "Found " . count($reminders) . " pending reminder(s)\n\n";

if (empty($reminders)) {
    echo "No reminders to process.\n";
    exit(0);
}

$smsHelper = new SmsHelper();

$sent = 0;
$failed = 0;
$skipped = 0;

foreach ($reminders as $reminder) {
    $patientName = $reminder['first_name'] . ' ' . $reminder['last_name'];
    $reminderType = ucfirst(str_replace('_', ' ', $reminder['reminder_type']));
    
    echo "Processing: {$patientName} - {$reminderType} (Due: {$reminder['due_date']})\n";
    
    if (empty($reminder['contact_no'])) {
        echo "  ⚠ Skipped: No mobile contact number on file\n";
        
        $update = $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?");
        $update->execute([$reminder['id']]);
        
        $skipped++;
        continue;
    }
    
    $patient = [
        'first_name' => $reminder['first_name'],
        'last_name' => $reminder['last_name'],
        'contact_no' => $reminder['contact_no'],
    ];

    $smsResult = $smsHelper->sendReminder($reminder, $patient);
    if ($smsResult) {
        echo "  ✓ SMS sent to: {$reminder['contact_no']}\n";
        $update = $pdo->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$reminder['id']]);
        $sent++;
    } else {
        echo "  ✗ SMS dispatch failed or disabled\n";
        $update = $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?");
        $update->execute([$reminder['id']]);
        $failed++;
    }
    
    echo "\n";
    usleep(500000); // 0.5 sec delay
}

echo "===========================================\n";
echo "Summary:\n";
echo "-------------------------------------------\n";
echo "Total processed: " . count($reminders) . "\n";
echo "Successfully sent: {$sent}\n";
echo "Failed: {$failed}\n";
echo "Skipped: {$skipped}\n";
echo "===========================================\n";

// Log to file
$logFile = __DIR__ . '/../storage/reminder_cron.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = sprintf(
    "[%s] Processed: %d, Sent: %d, Failed: %d, Skipped: %d\n",
    date('Y-m-d H:i:s'),
    count($reminders),
    $sent,
    $failed,
    $skipped
);

file_put_contents($logFile, $logEntry, FILE_APPEND);

SchedulerSettings::markRunToday();

exit($failed > 0 ? 1 : 0);
