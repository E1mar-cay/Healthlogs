<?php
$pageTitle = 'Maternal Health Module';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$maternalSummary = [
    'ongoing' => 0,
    'pregnancies' => 0,
    'prenatal' => 0,
    'postnatal' => 0,
    'sensitive_6_7' => 0,
];

$recentPrenatal = [];
$sensitivePregnancies = [];

try {
    $maternalSummary['pregnancies'] = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies")->fetchColumn();
    $maternalSummary['ongoing'] = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies WHERE status = 'ongoing'")->fetchColumn();
    $maternalSummary['prenatal'] = (int)$pdo->query("SELECT COUNT(*) FROM prenatal_visits")->fetchColumn();
    $maternalSummary['postnatal'] = (int)$pdo->query("SELECT COUNT(*) FROM postnatal_visits")->fetchColumn();

    // Sensitive pregnancies: Ongoing between 24 and 31 weeks (6 to 7 months)
    $maternalSummary['sensitive_6_7'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM pregnancies 
        WHERE status = 'ongoing' 
          AND TIMESTAMPDIFF(DAY, lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)
    ")->fetchColumn();

    $recentPrenatal = $pdo->query("
        SELECT v.visit_datetime, v.gestational_age_weeks, v.bp_systolic, v.bp_diastolic, p.first_name, p.last_name
        FROM prenatal_visits v
        JOIN pregnancies pr ON pr.id = v.pregnancy_id
        JOIN patients p ON p.id = pr.patient_id
        ORDER BY v.visit_datetime DESC
        LIMIT 5
    ")->fetchAll();

    $sensitivePregnancies = $pdo->query("
        SELECT pr.id, pr.lmp_date, pr.edd_date, p.first_name, p.last_name, p.barangay,
               FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS weeks_pregnant
        FROM pregnancies pr
        JOIN patients p ON p.id = pr.patient_id
        WHERE pr.status = 'ongoing'
          AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)
        ORDER BY weeks_pregnant DESC
        LIMIT 5
    ")->fetchAll();

} catch (Throwable $e) {
    // Keep the module page usable even if summary queries fail.
}
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Maternal &amp; Child Health</div>
      <div class="text-2xl font-semibold">Maternal Health Module</div>
      <p class="text-sm text-slate-500 mt-1">Track pregnancies, prenatal care, postnatal visits, and the DOH 8-ANC Target Client List.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">8-ANC Registry</span>
      <span class="app-chip">Postnatal Care</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/tcl.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line>
          <line x1="3" y1="15" x2="21" y2="15"></line>
          <line x1="9" y1="3" x2="9" y2="21"></line>
          <line x1="15" y1="3" x2="15" y2="21"></line>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">DOH Register</div>
        <div class="text-lg font-semibold">8-ANC TCL Register</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Official 4-part Target Client List (8-ANC, Nutrition, Lab, 4PNC).</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/pregnancies/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Registry</div>
        <div class="text-lg font-semibold">Pregnancies</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Register pregnancy cases, EDD dates, and monitor risk factors.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/prenatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Clinical Visits</div>
        <div class="text-lg font-semibold">Prenatal Visits</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log regular prenatal checkups, gestational age, and blood pressure.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/postnatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Outcomes</div>
        <div class="text-lg font-semibold">Postnatal Care</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Record postpartum recovery, newborn condition, and 4PNC visits.</p>
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active Cases</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($maternalSummary['ongoing'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Ongoing pregnancies</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total Pregnancies</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($maternalSummary['pregnancies'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">All-time registered cases</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Prenatal Logs</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($maternalSummary['prenatal'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Recorded checkup visits</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Postnatal Logs</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($maternalSummary['postnatal'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Recorded postpartum visits</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Activity</div>
    <div class="text-lg font-semibold">Latest Prenatal Checkups</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($recentPrenatal)): ?>
        <div class="text-sm text-slate-500">No recent prenatal visits.</div>
      <?php else: ?>
        <?php foreach ($recentPrenatal as $visit): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($visit['last_name'] . ', ' . $visit['first_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1">
                  GA: <?= (int)$visit['gestational_age_weeks'] ?> weeks
                  <?php if (!empty($visit['bp_systolic'])): ?>
                    &bull; BP: <?= (int)$visit['bp_systolic'] ?>/<?= (int)$visit['bp_diastolic'] ?>
                  <?php endif; ?>
                </div>
              </div>
              <span class="text-xs text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                <?= (int)$visit['gestational_age_weeks'] ?> wks
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2"><?= date('M d, Y', strtotime($visit['visit_datetime'])) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Summary</div>
    <div class="text-lg font-semibold">Priority Watchlist: 6–7 Months</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($sensitivePregnancies)): ?>
        <div class="text-sm text-slate-500">No high-risk 6–7 month cases at this time.</div>
      <?php else: ?>
        <?php foreach ($sensitivePregnancies as $sp): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($sp['last_name'] . ', ' . $sp['first_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1">
                  Brgy. <?= h($sp['barangay']) ?> &bull; EDD: <?= h($sp['edd_date']) ?>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-rose-100 text-rose-700">
                <?= (int)$sp['weeks_pregnant'] ?> wks
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2">Critical gestational window for glucose &amp; BP monitoring</div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
