<?php
/**
 * Email Configuration Test Script
 * Tests PHPMailer configuration and sends a test email
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/EnvLoader.php';

// Load .env file
EnvLoader::load(__DIR__ . '/../.env');

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("ERROR: Composer dependencies not installed. Run: composer install\n");
}

require_once __DIR__ . '/../app/Core/EmailHelper.php';

echo "===========================================\n";
echo "HealthLogs Email Configuration Test\n";
echo "===========================================\n\n";

// Test SMTP connection
echo "Testing SMTP connection...\n";
$emailHelper = new EmailHelper();
$result = $emailHelper->testConnection();

if ($result['success']) {
    echo "✓ SMTP connection successful!\n\n";
} else {
    echo "✗ SMTP connection failed: " . $result['message'] . "\n\n";
    echo "Please check your .env configuration:\n";
    echo "  - MAIL_HOST\n";
    echo "  - MAIL_PORT\n";
    echo "  - MAIL_USERNAME\n";
    echo "  - MAIL_PASSWORD\n\n";
    exit(1);
}

// Ask for test email
echo "Enter email address to send test reminder: ";
$testEmail = trim(fgets(STDIN));

if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address\n");
}

echo "\nSending test email to: {$testEmail}\n";

// Create test reminder
$testReminder = [
    'reminder_type' => 'general',
    'due_date' => date('Y-m-d'),
    'message' => 'This is a test reminder from HealthLogs. If you receive this email, your email configuration is working correctly!'
];

$testPatient = [
    'first_name' => 'Test',
    'last_name' => 'Patient',
    'email' => $testEmail
];

$sent = $emailHelper->sendReminder($testReminder, $testPatient);

if ($sent) {
    echo "✓ Test email sent successfully!\n";
    echo "\nCheck your inbox at: {$testEmail}\n";
    echo "(Don't forget to check spam/junk folder)\n\n";
} else {
    echo "✗ Failed to send test email\n";
    echo "Check the error logs for details\n\n";
    exit(1);
}

echo "===========================================\n";
echo "Email configuration test completed!\n";
echo "===========================================\n";
