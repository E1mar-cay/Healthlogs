<?php
$pageTitle = 'Immunization Module';
require __DIR__ . '/partials/header.php';

$immunizationSummary = [
    'vaccines' => 0,
    'records' => 0,
    'scheduled' => 0,
    'missed' => 0,
];

$recentRecords = [];
$upcomingSchedules = [];

try {
    $immunizationSummary['vaccines'] = (int)$pdo->query("SELECT COUNT(*) FROM vaccines")->fetchColumn();
    $immunizationSummary['records'] = (int)$pdo->query("SELECT COUNT(*) FROM immunization_records")->fetchColumn();
    $immunizationSummary['scheduled'] = (int)$pdo->query("SELECT COUNT(*) FROM immunization_schedule WHERE status = 'scheduled'")->fetchColumn();
    $immunizationSummary['missed'] = (int)$pdo->query("SELECT COUNT(*) FROM immunization_schedule WHERE status = 'missed'")->fetchColumn();

    $recentRecords = $pdo->query("
        SELECT r.administered_at, r.dose_no, p.first_name, p.last_name, v.name AS vaccine_name
        FROM immunization_records r
        JOIN patients p ON p.id = r.patient_id
        JOIN vaccines v ON v.id = r.vaccine_id
        ORDER BY r.administered_at DESC
        LIMIT 5
    ")->fetchAll();

    $upcomingSchedules = $pdo->query("
        SELECT s.scheduled_date, s.dose_no, p.first_name, p.last_name, v.name AS vaccine_name
        FROM immunization_schedule s
        JOIN patients p ON p.id = s.patient_id
        JOIN vaccines v ON v.id = s.vaccine_id
        WHERE s.status = 'scheduled'
        ORDER BY s.scheduled_date ASC
        LIMIT 5
    ")->fetchAll();
} catch (Throwable $e) {
    // Keep the module page usable even if summary queries fail.
}
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Care coordination</div>
      <div class="text-2xl font-semibold">Immunization Module</div>
      <p class="text-sm text-slate-500 mt-1">Manage vaccines, track records, and keep schedules on time.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Child Health</span>
      <span class="app-chip">Community Coverage</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/vaccines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 2h8l1 3H7l1-3Z"></path>
          <path d="M7 5h10v12a5 5 0 0 1-10 0V5Z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Setup</div>
        <div class="text-lg font-semibold">Vaccines</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Maintain vaccine registry and stock details.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 3h8l4 4v14H4V3h4Z"></path>
          <path d="M8 11h8"></path>
          <path d="M8 15h8"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Logs</div>
        <div class="text-lg font-semibold">Immunization Records</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Capture administered doses and patient history.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/schedules/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M12 7v6l4 2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Scheduling</div>
        <div class="text-lg font-semibold">Immunization Schedules</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Plan dose sequences and outreach follow-ups.</p>
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Vaccines</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($immunizationSummary['vaccines'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Registered vaccine types</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Records</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($immunizationSummary['records'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Total administered doses</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Scheduled</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($immunizationSummary['scheduled'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Upcoming visits</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Missed</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($immunizationSummary['missed'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Need follow-up</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Activity</div>
    <div class="text-lg font-semibold">Latest Immunization Records</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($recentRecords)): ?>
        <div class="text-sm text-slate-500">No recent immunization records.</div>
      <?php else: ?>
        <?php foreach ($recentRecords as $record): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="font-medium text-slate-900"><?= h($record['last_name'] . ', ' . $record['first_name']) ?></div>
            <div class="text-sm text-slate-500 mt-1"><?= h($record['vaccine_name']) ?>, Dose <?= h($record['dose_no']) ?></div>
            <div class="text-xs text-slate-400 mt-2"><?= h($record['administered_at']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Summary</div>
    <div class="text-lg font-semibold">Upcoming Schedule Snapshot</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($upcomingSchedules)): ?>
        <div class="text-sm text-slate-500">No upcoming immunization schedules.</div>
      <?php else: ?>
        <?php foreach ($upcomingSchedules as $schedule): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="font-medium text-slate-900"><?= h($schedule['last_name'] . ', ' . $schedule['first_name']) ?></div>
            <div class="text-sm text-slate-500 mt-1"><?= h($schedule['vaccine_name']) ?>, Dose <?= h($schedule['dose_no']) ?></div>
            <div class="text-xs text-slate-400 mt-2">Scheduled: <?= h($schedule['scheduled_date']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
