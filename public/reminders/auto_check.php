require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/Core/EnvLoader.php';
EnvLoader::load(__DIR__ . '/../../.env');
require_once __DIR__ . '/../../app/Core/SchedulerSettings.php';
require_once __DIR__ . '/../../app/Core/SmsHelper.php';

header('Content-Type: application/json');

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');

$currentTime = date('H:i');
$scheduledTime = SchedulerSettings::getScheduledTime();

// Check if current time has reached the scheduled time
if ($currentTime < $scheduledTime) {
    echo json_encode([
        'status' => 'waiting',
        'current_time' => $currentTime,
        'scheduled_time' => $scheduledTime,
        'message' => 'Scheduled time not yet reached.'
    ]);
    exit;
}

// Find all pending reminders (any due date)
$sql = "SELECT r.*, p.first_name, p.last_name, p.contact_no
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status = 'pending' 
        ORDER BY r.due_date ASC";

$stmt = $pdo->query($sql);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($reminders)) {
    echo json_encode([
        'status' => 'idle',
        'current_time' => $currentTime,
        'scheduled_time' => $scheduledTime,
        'pending_count' => 0,
        'message' => 'No pending reminders due.'
    ]);
    exit;
}

$smsHelper = new SmsHelper();
$sent = 0;
$failed = 0;
$patientNames = [];

foreach ($reminders as $reminder) {
    if (empty($reminder['contact_no'])) {
        $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?")->execute([$reminder['id']]);
        $failed++;
        continue;
    }

    $patient = [
        'first_name' => $reminder['first_name'],
        'last_name' => $reminder['last_name'],
        'contact_no' => $reminder['contact_no'],
    ];

    $ok = $smsHelper->sendReminder($reminder, $patient);
    if ($ok) {
        $pdo->prepare("UPDATE reminders SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$reminder['id']]);
        $sent++;
        $patientNames[] = trim($reminder['first_name'] . ' ' . $reminder['last_name']);
    } else {
        $pdo->prepare("UPDATE reminders SET status = 'failed' WHERE id = ?")->execute([$reminder['id']]);
        $failed++;
    }
}

SchedulerSettings::markRunToday();

echo json_encode([
    'status' => 'dispatched',
    'sent_count' => $sent,
    'failed_count' => $failed,
    'patient_names' => $patientNames,
    'message' => "Dispatched {$sent} reminder(s) via TextBee."
]);
exit;
