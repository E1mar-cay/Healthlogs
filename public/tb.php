<?php
$pageTitle = 'TB Monitoring Module';
require __DIR__ . '/partials/header.php';

$tbSummary = [
    'active_cases' => 0,
    'total_cases' => 0,
    'dots_today' => 0,
    'lab_tests' => 0,
    'cured_completed' => 0,
];

$activeCases = [];

try {
    $tbSummary['active_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE status = 'active'")->fetchColumn();
    $tbSummary['total_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases")->fetchColumn();
    $tbSummary['dots_today'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_dot_logs WHERE log_date = CURRENT_DATE() AND status IN ('taken', 'supervised')")->fetchColumn();
    $tbSummary['lab_tests'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_lab_examinations")->fetchColumn();
    $tbSummary['cured_completed'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE treatment_outcome IN ('cured', 'treatment_completed')")->fetchColumn();

    $activeCases = $pdo->query("
        SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        WHERE c.status = 'active'
        ORDER BY c.created_at DESC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    // Keep module page usable even if summary queries fail
}
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Care coordination</div>
      <div class="text-2xl font-semibold">TB Monitoring Module</div>
      <p class="text-sm text-slate-500 mt-1">Manage TB case registrations, track daily DOTS intake, and record lab results.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">NTP Protocol</span>
      <span class="app-chip">DOTS Compliance</span>
      <a href="/HealthLogs/public/tb/cases/create.php" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow text-sm font-medium hover:bg-slate-800 transition">
        <i class="fas fa-plus mr-1.5 text-xs"></i>Register New TB Case
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/cases/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Registry</div>
        <div class="text-lg font-semibold">TB Case Registry</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Enrolled patient cases, treatment classifications, and regimens.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/dots/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Adherence</div>
        <div class="text-lg font-semibold">Daily DOTS Tracker</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log daily anti-TB drug intake (taken, supervised, or missed).</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/labs/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L5.6 15.12a2 2 0 00-1.18.118l-1.05.42a2 2 0 00-1.22 1.86v.482a2 2 0 002 2h17.7a2 2 0 002-2v-.482a2 2 0 00-1.422-1.93zM12 3v9m-4-5l4-4 4 4"/>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Monitoring</div>
        <div class="text-lg font-semibold">Lab Examinations</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Record Sputum Smear, GeneXpert, and Chest X-ray results.</p>
  </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active Cases</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($tbSummary['active_cases'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Currently on treatment</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">DOTS Today</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($tbSummary['dots_today'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Doses recorded today</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Lab Examinations</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($tbSummary['lab_tests'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Total lab tests logged</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Cured / Completed</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($tbSummary['cured_completed'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Successful outcomes</div>
  </div>
</div>

<div class="bg-white p-4 sm:p-5 rounded-xl shadow mt-6">
  <div class="flex items-center justify-between">
    <div>
      <div class="text-sm text-slate-500">Active Cases</div>
      <div class="text-lg font-semibold">Patients Under Treatment</div>
    </div>
    <a href="/HealthLogs/public/tb/cases/index.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 underline">View all cases</a>
  </div>

  <?php if (empty($activeCases)): ?>
    <div class="text-sm text-slate-500 mt-4">No active TB cases currently registered.</div>
  <?php else: ?>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-sm min-w-[640px]">
        <thead>
          <tr class="border-b text-slate-500 uppercase text-xs">
            <th class="py-2.5 px-3">Case No</th>
            <th class="py-2.5 px-3">Patient Name</th>
            <th class="py-2.5 px-3">Barangay</th>
            <th class="py-2.5 px-3">Type</th>
            <th class="py-2.5 px-3">Category</th>
            <th class="py-2.5 px-3">Start Date</th>
            <th class="py-2.5 px-3">Doses Taken</th>
            <th class="py-2.5 px-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php foreach ($activeCases as $c): ?>
            <tr>
              <td class="py-3 px-3 font-mono font-medium text-slate-900 whitespace-nowrap"><?= h($c['case_number']) ?></td>
              <td class="py-3 px-3 font-medium text-slate-900 whitespace-nowrap"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></td>
              <td class="py-3 px-3 whitespace-nowrap"><?= h($c['barangay']) ?></td>
              <td class="py-3 px-3 capitalize whitespace-nowrap"><?= h(str_replace('_', ' ', $c['tb_type'])) ?></td>
              <td class="py-3 px-3 font-medium uppercase whitespace-nowrap"><?= h(str_replace('_', ' ', $c['treatment_category'])) ?></td>
              <td class="py-3 px-3 whitespace-nowrap"><?= h($c['treatment_start_date']) ?></td>
              <td class="py-3 px-3 font-medium text-teal-700 whitespace-nowrap"><?= (int)$c['doses_taken'] ?> doses</td>
              <td class="py-3 px-3 text-right whitespace-nowrap">
                <div class="inline-flex items-center justify-end gap-1.5">
                  <a href="/HealthLogs/public/tb/dots/index.php?case_id=<?= $c['id'] ?>" class="text-xs bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1.5 rounded font-medium transition">DOTS Log</a>
                  <a href="/HealthLogs/public/tb/cases/edit.php?id=<?= $c['id'] ?>" class="text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 px-2.5 py-1.5 rounded font-medium transition">Edit</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
