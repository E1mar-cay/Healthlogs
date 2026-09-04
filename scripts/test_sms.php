<?php
/**
 * SMS Configuration Test Script for HealthLogs
 * Tests TextBee / SMS Gateway configuration and sends a test SMS
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Core/EnvLoader.php';

// Load .env file
EnvLoader::load(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/Core/SmsHelper.php';

echo "===========================================\n";
echo "HealthLogs SMS Configuration Test\n";
echo "===========================================\n\n";

$provider = getenv('SMS_PROVIDER') ?: 'textbee';
$apiKey = getenv('TEXTBEE_API_KEY') ?: getenv('SEMAPHORE_API_KEY');
$enabled = getenv('SMS_ENABLED');

echo "Provider: " . strtoupper($provider) . "\n";
echo "SMS Enabled: " . ($enabled !== 'false' ? 'Yes' : 'No') . "\n";
echo "API Key Set: " . (!empty($apiKey) && $apiKey !== 'your_textbee_api_key_here' ? 'Yes' : 'No (Please set TEXTBEE_API_KEY in .env)') . "\n\n";

if ($apiKey === 'your_textbee_api_key_here' || empty($apiKey)) {
    echo "WARNING: TEXTBEE_API_KEY is not configured in .env!\n";
    echo "Get your API key at: https://app.textbee.dev/dashboard\n\n";
}

echo "Enter recipient mobile number (e.g., 09171234567 or +639171234567): ";
$phone = trim(fgets(STDIN));

if (empty($phone)) {
    die("Mobile number cannot be empty.\n");
}

echo "\nSending test SMS via {$provider} to: {$phone}...\n";

$smsHelper = new SmsHelper();
$message = "HealthLogs Test SMS: TextBee SMS Gateway is successfully connected! Sent at " . date('Y-m-d H:i:s');

$sent = $smsHelper->sendSms($phone, $message);

if ($sent) {
    echo "✓ Test SMS successfully dispatched!\n";
    echo "Check the recipient phone for delivery.\n\n";
} else {
    echo "✗ Failed to send test SMS.\n";
    echo "Please check PHP error logs and verify your .env configuration & TextBee device status.\n\n";
    exit(1);
}

echo "===========================================\n";
echo "SMS Configuration Test Completed!\n";
echo "===========================================\n";
