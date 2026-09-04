<?php
/**
 * Instant SMS Dispatcher Endpoint
 * Sends an immediate SMS reminder via TextBee API
 */
require __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../app/Core/SmsHelper.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (!$id) {
    $_SESSION['error_message'] = 'Invalid reminder ID.';
    header('Location: /HealthLogs/public/reminders.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.contact_no, p.email
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $reminder = $stmt->fetch();

    if (!$reminder) {
        $_SESSION['error_message'] = 'Reminder record not found.';
        header('Location: /HealthLogs/public/reminders.php');
        exit;
    }

    if (empty($reminder['contact_no'])) {
        $_SESSION['error_message'] = "Cannot send SMS: Patient {$reminder['first_name']} {$reminder['last_name']} has no mobile contact number registered.";
        header('Location: /HealthLogs/public/reminders.php');
        exit;
    }

    $smsHelper = new SmsHelper();
    $patient = [
        'first_name' => $reminder['first_name'],
        'last_name' => $reminder['last_name'],
        'contact_no' => $reminder['contact_no'],
    ];

    $success = $smsHelper->sendReminder($reminder, $patient);

    if ($success) {
        $update = $pdo->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$id]);
        $_SESSION['success_message'] = "✓ SMS successfully sent to {$reminder['first_name']} {$reminder['last_name']} ({$reminder['contact_no']}) via TextBee!";
    } else {
        $_SESSION['error_message'] = "✗ Failed to send SMS via TextBee. Please check system logs or device connectivity.";
    }

} catch (Exception $e) {
    error_log("Send SMS error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while dispatching the SMS: ' . $e->getMessage();
}

header('Location: /HealthLogs/public/reminders.php');
exit;
