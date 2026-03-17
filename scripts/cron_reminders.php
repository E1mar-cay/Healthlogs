<?php
/**
 * Cron job: Send reminders due today or earlier via email
 * 
 * Run this script daily via cron job or Windows Task Scheduler
 * Example: php C:\xampp\htdocs\HealthLogs\scripts\cron_reminders.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/EnvLoader.php';

// Load .env file
EnvLoader::load(__DIR__ . '/../.env');

// Load Composer autoloader for PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    echo "ERROR: Composer dependencies not installed. Run: composer install\n";
    exit(1);
}

// Load EmailHelper
require_once __DIR__ . '/../app/Core/EmailHelper.php';

date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

echo "===========================================\n";
echo "HealthLogs Reminder Cron Job\n";
echo "===========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "-------------------------------------------\n\n";

// Get pending reminders due today or earlier
$sql = "SELECT r.*, p.first_name, p.last_name, p.email, p.contact_no
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status = 'pending' 
        AND r.due_date <= ?
        ORDER BY r.due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$today]);
$reminders = $stmt->fetchAll();

echo "Found " . count($reminders) . " pending reminder(s)\n\n";

if (empty($reminders)) {
    echo "No reminders to process.\n";
    exit(0);
}

$emailHelper = new EmailHelper();
$sent = 0;
$failed = 0;
$skipped = 0;

foreach ($reminders as $reminder) {
    $patientName = $reminder['first_name'] . ' ' . $reminder['last_name'];
    $reminderType = ucfirst($reminder['reminder_type']);
    
    echo "Processing: {$patientName} - {$reminderType} (Due: {$reminder['due_date']})\n";
    
    // Check if patient has email
    if (empty($reminder['email'])) {
        echo "  ⚠ Skipped: No email address\n";
        
        // Mark as failed with note
        $update = $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?");
        $update->execute([$reminder['id']]);
        
        $skipped++;
        continue;
    }
    
    // Prepare patient data
    $patient = [
        'first_name' => $reminder['first_name'],
        'last_name' => $reminder['last_name'],
        'email' => $reminder['email']
    ];
    
    // Send email
    $emailSent = $emailHelper->sendReminder($reminder, $patient);
    
    if ($emailSent) {
        echo "  ✓ Email sent to: {$reminder['email']}\n";
        
        // Mark as sent
        $update = $pdo->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$reminder['id']]);
        
        $sent++;
    } else {
        echo "  ✗ Failed to send email\n";
        
        // Mark as failed
        $update = $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?");
        $update->execute([$reminder['id']]);
        
        $failed++;
    }
    
    echo "\n";
    
    // Small delay to avoid overwhelming SMTP server
    usleep(500000); // 0.5 seconds
}

echo "===========================================\n";
echo "Summary:\n";
echo "-------------------------------------------\n";
echo "Total processed: " . count($reminders) . "\n";
echo "Successfully sent: {$sent}\n";
echo "Failed: {$failed}\n";
echo "Skipped (no email): {$skipped}\n";
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

exit($failed > 0 ? 1 : 0);
