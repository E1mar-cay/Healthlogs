<?php
$pageTitle = 'Maternal Health Module';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$maternalSummary = [
    'pregnancies' => 0,
    'ongoing' => 0,
    'sensitive_6_7' => 0,
    'prenatal' => 0,
    'postnatal' => 0,
];

$recentPrenatal = [];
$recentPostnatal = [];
$sensitivePregnancies = [];

try {
    $maternalSummary['pregnancies'] = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies")->fetchColumn();
    $maternalSummary['ongoing'] = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies WHERE status = 'ongoing'")->fetchColumn();
    
    // Sensitive pregnancies: Ongoing between 24 and 31 weeks (6 to 7 months)
    $maternalSummary['sensitive_6_7'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM pregnancies 
        WHERE status = 'ongoing' 
          AND TIMESTAMPDIFF(DAY, lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)
    ")->fetchColumn();

    $maternalSummary['prenatal'] = (int)$pdo->query("SELECT COUNT(*) FROM prenatal_visits")->fetchColumn();
    $maternalSummary['postnatal'] = (int)$pdo->query("SELECT COUNT(*) FROM postnatal_visits")->fetchColumn();

    // Sensitive ongoing pregnancies (6-7 months) for watchlist
    $sensitivePregnancies = $pdo->query("
        SELECT pr.id, pr.lmp_date, pr.edd_date, pr.status, p.id AS patient_id, p.first_name, p.last_name, p.contact_no, p.barangay,
               FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS weeks_pregnant,
               ROUND(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 30.4375, 1) AS months_pregnant
        FROM pregnancies pr
        JOIN patients p ON p.id = pr.patient_id
        WHERE pr.status = 'ongoing'
          AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)
        ORDER BY weeks_pregnant DESC
    ")->fetchAll();

    $recentPrenatal = $pdo->query("
        SELECT v.visit_datetime, v.gestational_age_weeks, v.bp_systolic, v.bp_diastolic, p.first_name, p.last_name
        FROM prenatal_visits v
        JOIN pregnancies pr ON pr.id = v.pregnancy_id
        JOIN patients p ON p.id = pr.patient_id
        ORDER BY v.visit_datetime DESC
        LIMIT 6
    ")->fetchAll();

    $recentPostnatal = $pdo->query("
        SELECT v.visit_datetime, v.mother_condition, v.baby_condition, p.first_name, p.last_name
        FROM postnatal_visits v
        JOIN pregnancies pr ON pr.id = v.pregnancy_id
        JOIN patients p ON p.id = pr.patient_id
        ORDER BY v.visit_datetime DESC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    // Keep the module page usable even if summary queries fail.
}
?>

<div class="bg-white p-6 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-rose-700">Maternal Care &amp; DOH Registry</div>
      <div class="text-2xl font-bold text-slate-900 mt-1">Maternal Health Module</div>
      <p class="text-sm text-slate-500 mt-1">Track pregnancies, monitor critical 6–7 months prenatal care, and maintain the official DOH 8-ANC Target Client List.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if ($maternalSummary['sensitive_6_7'] > 0): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 shadow-2xs">
          <i class="fas fa-exclamation-circle text-rose-600 animate-pulse"></i>
          <?= $maternalSummary['sensitive_6_7'] ?> Sensitive (6–7 Mos)
        </span>
      <?php endif; ?>
      <a href="/HealthLogs/public/maternal/tcl.php" class="inline-flex items-center gap-2 bg-rose-700 hover:bg-rose-800 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow transition">
        <i class="fas fa-table-list"></i> Target Client List (8-ANC TCL)
      </a>
    </div>
  </div>
</div>

<!-- Navigation Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-5 rounded-xl shadow border border-rose-200 bg-gradient-to-br from-white to-rose-50/50 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/maternal/tcl.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-rose-600 text-white flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-file-medical"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-rose-700">DOH Register</div>
        <div class="text-base font-bold text-slate-900">8-ANC TCL Register</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Official 4-part Target Client List (8-ANC Visits, Nutrition/Td, Lab Screenings, 4PNC).</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/maternal/pregnancies/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-person-pregnant"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-purple-700">Registry</div>
        <div class="text-base font-bold text-slate-900">Pregnancies</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Register cases, calculate gestational age, and monitor high-risk patients.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/maternal/prenatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-stethoscope"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-blue-700">Clinical Visits</div>
        <div class="text-base font-bold text-slate-900">Prenatal Visits</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Log prenatal checkups, fetal heartbeat, BP, and clinical findings.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 transition group" href="/HealthLogs/public/maternal/postnatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-base shadow-sm group-hover:scale-105 transition">
        <i class="fas fa-baby"></i>
      </span>
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-teal-700">Outcomes</div>
        <div class="text-base font-bold text-slate-900">Postnatal Care</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Record delivery outcomes, newborn conditions, and 4PNC follow-up visits.</p>
  </a>
</div>

<!-- Summary Metrics -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-6">
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">Total Cases</div>
    <div class="text-2xl font-semibold mt-2 text-slate-900"><?= h(number_format($maternalSummary['pregnancies'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">All registered pregnancies</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">Active Cases</div>
    <div class="text-2xl font-semibold mt-2 text-slate-900"><?= h(number_format($maternalSummary['ongoing'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Ongoing pregnancies</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow border-2 border-rose-300 bg-gradient-to-br from-white to-rose-50/40">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-widest text-rose-700 font-bold">6–7 Mos (Sensitive)</div>
      <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
    </div>
    <div class="text-2xl font-bold mt-2 text-rose-900"><?= h(number_format($maternalSummary['sensitive_6_7'])) ?></div>
    <div class="text-xs text-rose-700 font-medium mt-1">24–31 weeks high priority</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">Prenatal Logs</div>
    <div class="text-2xl font-semibold mt-2 text-slate-900"><?= h(number_format($maternalSummary['prenatal'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Recorded checkup visits</div>
  </div>
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">Postnatal Logs</div>
    <div class="text-2xl font-semibold mt-2 text-slate-900"><?= h(number_format($maternalSummary['postnatal'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Recorded postpartum visits</div>
  </div>
</div>

<!-- High Priority Sensitive Watchlist (6-7 Months) -->
<?php if (!empty($sensitivePregnancies)): ?>
  <div class="mt-6 bg-gradient-to-r from-rose-50/90 to-amber-50/90 border-2 border-rose-300 rounded-xl p-5 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
      <div class="flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
          <i class="fas fa-heartbeat"></i>
        </span>
        <div>
          <h2 class="text-base font-bold text-rose-900">Sensitive Patients Watchlist: 6–7 Months (24–31 Weeks)</h2>
          <p class="text-xs text-rose-700">Mothers in this critical gestational window require accelerated monitoring, glucose screening, and BP surveillance.</p>
        </div>
      </div>
      <a href="/HealthLogs/public/maternal/pregnancies/index.php?status=ongoing&stage=sensitive" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-800 hover:text-rose-950 bg-white px-3 py-1.5 rounded-lg border border-rose-300 shadow-2xs hover:bg-rose-50 transition">
        <span>View all sensitive cases (<?= count($sensitivePregnancies) ?>)</span>
        <i class="fas fa-arrow-right text-[10px]"></i>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
      <?php foreach ($sensitivePregnancies as $sp): ?>
        <div class="bg-white p-4 rounded-lg border border-rose-200 shadow-xs flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="font-semibold text-slate-900 text-sm"><?= h($sp['last_name'] . ', ' . $sp['first_name']) ?></span>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                <?= h((string)$sp['weeks_pregnant']) ?> wks (<?= h((string)$sp['months_pregnant']) ?> mos)
              </span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
              <div><i class="fas fa-map-marker-alt text-slate-400 mr-1"></i> <?= h($sp['barangay']) ?></div>
              <div><i class="fas fa-phone text-slate-400 mr-1"></i> <?= h($sp['contact_no'] ?: 'No contact number') ?></div>
              <div><i class="fas fa-calendar-alt text-slate-400 mr-1"></i> EDD: <strong><?= h($sp['edd_date']) ?></strong></div>
            </div>
          </div>
          <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <a href="/HealthLogs/public/maternal/prenatal/form.php?pregnancy_id=<?= (int)$sp['id'] ?>" class="text-teal-700 hover:text-teal-900 font-semibold inline-flex items-center gap-1">
              <i class="fas fa-plus-circle text-xs"></i> Record Visit
            </a>
            <a href="/HealthLogs/public/maternal/pregnancies/index.php?q=<?= urlencode($sp['last_name']) ?>" class="text-slate-500 hover:text-slate-800">
              Details →
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Recent Activity Columns -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Recent Visits</div>
        <div class="text-lg font-semibold text-slate-900">Latest Prenatal Checkups</div>
      </div>
      <a href="/HealthLogs/public/maternal/prenatal/index.php" class="text-xs text-teal-700 hover:underline font-semibold">View all</a>
    </div>
    <div class="space-y-3">
      <?php if (empty($recentPrenatal)): ?>
        <div class="text-sm text-slate-400 text-center py-4">No recent prenatal visits.</div>
      <?php else: ?>
        <?php foreach ($recentPrenatal as $visit): ?>
          <?php 
            $ga = (int)($visit['gestational_age_weeks'] ?? 0);
            $isSensitiveVisit = ($ga >= 24 && $ga <= 31);
            $isHighBp = ((int)($visit['bp_systolic'] ?? 0) >= 140 || (int)($visit['bp_diastolic'] ?? 0) >= 90);
          ?>
          <div class="rounded-xl border p-3.5 flex items-center justify-between <?= $isSensitiveVisit ? 'bg-rose-50/50 border-rose-200' : 'bg-slate-50/75 border-slate-200' ?>">
            <div>
              <div class="font-semibold text-slate-900 text-sm"><?= h($visit['last_name'] . ', ' . $visit['first_name']) ?></div>
              <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                <span>GA: <strong><?= h((string)$ga) ?> weeks</strong></span>
                <span>•</span>
                <span>BP: <strong class="<?= $isHighBp ? 'text-rose-600 font-bold' : '' ?>"><?= h((string)$visit['bp_systolic']) ?>/<?= h((string)$visit['bp_diastolic']) ?></strong></span>
              </div>
              <div class="text-[11px] text-slate-400 mt-1"><?= date('M d, Y h:i A', strtotime($visit['visit_datetime'])) ?></div>
            </div>
            <div>
              <?php if ($isSensitiveVisit): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs">
                  <i class="fas fa-exclamation-circle text-rose-600"></i> 6–7 Mos
                </span>
              <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700">
                  <?= h((string)$ga) ?> wks
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Postpartum Recovery</div>
        <div class="text-lg font-semibold text-slate-900">Latest Postnatal Visits</div>
      </div>
      <a href="/HealthLogs/public/maternal/postnatal/index.php" class="text-xs text-teal-700 hover:underline font-semibold">View all</a>
    </div>
    <div class="space-y-3">
      <?php if (empty($recentPostnatal)): ?>
        <div class="text-sm text-slate-400 text-center py-4">No recent postnatal visits.</div>
      <?php else: ?>
        <?php foreach ($recentPostnatal as $visit): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50/75 p-3.5">
            <div class="font-semibold text-slate-900 text-sm"><?= h($visit['last_name'] . ', ' . $visit['first_name']) ?></div>
            <div class="text-xs text-slate-600 mt-1 flex flex-wrap gap-x-4 gap-y-1">
              <span>Mother: <strong class="text-slate-800"><?= h($visit['mother_condition'] ?: 'Normal') ?></strong></span>
              <span>Baby: <strong class="text-slate-800"><?= h($visit['baby_condition'] ?: 'Normal') ?></strong></span>
            </div>
            <div class="text-[11px] text-slate-400 mt-1.5"><?= date('M d, Y h:i A', strtotime($visit['visit_datetime'])) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
