<?php
$script = __DIR__ . '/../../scripts/cron_reminders.php';
$cmd = 'php ' . escapeshellarg($script);
$output = shell_exec($cmd);

$pageTitle = 'Reminders';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">Cron Result</div>
  <pre class="mt-2 text-sm text-slate-700"><?= h($output ?? 'No output') ?></pre>
  <a class="inline-block mt-3 text-blue-600" href="/HealthLogs/public/reminders.php">Back to Reminders</a>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
