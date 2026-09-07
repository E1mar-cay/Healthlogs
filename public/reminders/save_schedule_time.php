<?php
require __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../app/Core/SchedulerSettings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

$time = trim($_POST['scheduled_time'] ?? '');

if (empty($time)) {
    $_SESSION['error_message'] = 'Please select a valid time.';
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
    $_SESSION['error_message'] = 'Invalid time format. Please select a valid time.';
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

if (SchedulerSettings::setScheduledTime($time)) {
    $formatted = SchedulerSettings::getFormattedTime();
    $_SESSION['success_message'] = "Daily reminder send time successfully updated to {$formatted}!";
} else {
    $_SESSION['error_message'] = 'Failed to save scheduled time. Please check file permissions.';
}

header('Location: /HealthLogs/public/reminders.php');
exit;
