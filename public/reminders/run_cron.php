<?php
require __DIR__ . '/../partials/bootstrap.php';

$script = __DIR__ . '/../../scripts/cron_reminders.php';
$cmd = 'php ' . escapeshellarg($script);
$output = shell_exec($cmd);

if ($output !== null && trim($output) !== '') {
    $_SESSION['success_message'] = "Reminder scheduler completed.\n" . trim($output);
} else {
    $_SESSION['info_message'] = 'Reminder scheduler completed with no output.';
}

header('Location: /HealthLogs/public/reminders.php');
exit;
