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

<div class="bg-white p-6 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-teal-700">Care Coordination &amp; DOH Registry</div>
      <div class="text-2xl font-bold text-slate-900 mt-1">Child Immunization Module</div>
      <p class="text-sm text-slate-500 mt-1">Manage vaccines, track administered doses, and maintain the official DOH Target Client List (TCL-2).</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/immunization/tcl.php" class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow transition">
        <i class="fas fa-table-list"></i> Target Client List (TCL-2)
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-5 rounded-xl shadow border border-teal-200 bg-gradient-to-br from-white to-teal-50/50 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/immunization/tcl.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-file-medical"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-teal-700">DOH Register</div>
        <div class="text-base font-bold text-slate-900">Target Client List (TCL-2)</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Master registry for 0–23 months (BCG, HepB, Penta, OPV, IPV, PCV, MMR, FIC, CIC).</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/immunization/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-syringe"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-blue-700">Daily Logs</div>
        <div class="text-base font-bold text-slate-900">Immunization Records</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Capture individual administered doses and clinical injection details.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/immunization/schedules/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-calendar-check"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-amber-700">Scheduling</div>
        <div class="text-base font-bold text-slate-900">Vaccine Schedules</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Plan dose sequences, next visits, and outreach reminders.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/immunization/vaccines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-vial"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-emerald-700">Setup</div>
        <div class="text-base font-bold text-slate-900">Vaccine Registry</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Maintain vaccine registry, required doses, and age guidelines.</p>
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
