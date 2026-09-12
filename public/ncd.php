<?php
$pageTitle = 'Non-Communicable Disease (NCD) Module';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$summary = [
    'total_clients' => 0,
    'hypertension_count' => 0,
    'diabetes_count' => 0,
    'due_this_month' => 0,
    'overdue' => 0,
    'high_risk' => 0,
];

$recentVisits = [];
$upcomingAppointments = [];

try {
    $summary['total_clients'] = (int)$pdo->query("SELECT COUNT(*) FROM ncd_records WHERE status IN ('active', 'controlled', 'uncontrolled')")->fetchColumn();
    $summary['hypertension_count'] = (int)$pdo->query("SELECT COUNT(*) FROM ncd_records WHERE diagnosis_type IN ('hypertension', 'hypertension_diabetes') AND status IN ('active', 'controlled', 'uncontrolled')")->fetchColumn();
    $summary['diabetes_count'] = (int)$pdo->query("SELECT COUNT(*) FROM ncd_records WHERE diagnosis_type IN ('diabetes', 'hypertension_diabetes') AND status IN ('active', 'controlled', 'uncontrolled')")->fetchColumn();
    $summary['high_risk'] = (int)$pdo->query("SELECT COUNT(*) FROM ncd_records WHERE philpen_risk_level IN ('high', 'very_high') AND status IN ('active', 'controlled', 'uncontrolled')")->fetchColumn();

    // Compute due and overdue counts based on last visit's next appointment date
    $summary['due_this_month'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM ncd_records r
        JOIN (
            SELECT ncd_record_id, MAX(next_appointment_date) AS next_date 
            FROM ncd_visits 
            GROUP BY ncd_record_id
        ) v ON v.ncd_record_id = r.id
        WHERE r.status IN ('active', 'controlled', 'uncontrolled') 
          AND v.next_date >= CURDATE() 
          AND v.next_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();

    $summary['overdue'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM ncd_records r
        JOIN (
            SELECT ncd_record_id, MAX(next_appointment_date) AS next_date 
            FROM ncd_visits 
            GROUP BY ncd_record_id
        ) v ON v.ncd_record_id = r.id
        WHERE r.status IN ('active', 'controlled', 'uncontrolled') 
          AND v.next_date < CURDATE()
    ")->fetchColumn();

    // Recent Consultations & Vital Logs
    $recentVisits = $pdo->query("
        SELECT v.visit_date, v.bp_systolic, v.bp_diastolic, v.blood_sugar_mgdl, v.blood_sugar_type,
               v.medications_dispensed, v.treatment_adherence,
               p.first_name, p.last_name, r.ncd_code, r.diagnosis_type
        FROM ncd_visits v
        JOIN ncd_records r ON r.id = v.ncd_record_id
        JOIN patients p ON p.id = r.patient_id
        ORDER BY v.visit_date DESC, v.id DESC
        LIMIT 6
    ")->fetchAll();

    // Upcoming Appointments & Refills Snapshot
    $upcomingAppointments = $pdo->query("
        SELECT r.ncd_code, r.diagnosis_type, r.philpen_risk_level, p.first_name, p.last_name, p.contact_no, p.barangay,
               lv.next_appointment_date
        FROM ncd_records r
        JOIN patients p ON p.id = r.patient_id
        JOIN (
            SELECT ncd_record_id, MAX(next_appointment_date) AS next_appointment_date
            FROM ncd_visits
            WHERE next_appointment_date IS NOT NULL
            GROUP BY ncd_record_id
        ) lv ON lv.ncd_record_id = r.id
        WHERE r.status IN ('active', 'controlled', 'uncontrolled')
        ORDER BY 
            CASE WHEN lv.next_appointment_date < CURDATE() THEN 0 ELSE 1 END,
            lv.next_appointment_date ASC
        LIMIT 6
    ")->fetchAll();

} catch (Throwable $e) {
    // Keep module page usable even if query fails
}

function ncd_format_diagnosis(?string $d): string {
    $map = [
        'hypertension' => 'Hypertension (HPN)',
        'diabetes' => 'Type 2 Diabetes (DM)',
        'hypertension_diabetes' => 'HPN & Diabetes',
        'cardiovascular' => 'Cardiovascular Disease',
        'asthma_copd' => 'Asthma / COPD',
        'chronic_kidney' => 'Chronic Kidney Disease',
        'cancer' => 'Cancer Registry',
        'other' => 'Other NCD',
    ];
    return $map[$d] ?? ucwords(str_replace('_', ' ', (string)$d));
}

function ncd_bp_badge(?int $sys, ?int $dia): string {
    if (!$sys || !$dia) return '<span class="text-xs text-slate-400">—</span>';
    if ($sys >= 160 || $dia >= 100) {
        return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200\" title=\"Stage 2 Hypertension\">{$sys}/{$dia} mmHg (Stage 2)</span>";
    } elseif ($sys >= 140 || $dia >= 90) {
        return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200\" title=\"Stage 1 Hypertension\">{$sys}/{$dia} mmHg (Stage 1)</span>";
    } elseif ($sys >= 120 || $dia >= 80) {
        return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200\" title=\"Elevated / Pre-HTN\">{$sys}/{$dia} mmHg (Pre-HTN)</span>";
    }
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200\" title=\"Normal Blood Pressure\">{$sys}/{$dia} mmHg</span>";
}
?>

<?php display_flash_messages(); ?>

<!-- Main Header Card -->
<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Barangay Chronic Disease Care</div>
      <div class="text-2xl font-semibold">Non-Communicable Disease (NCD) Module</div>
      <p class="text-sm text-slate-500 mt-1">Hypertension, Diabetes, and lifestyle disease monitoring, vital tracking, and PhilPEN risk management.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">PhilPEN Protocol</span>
      <span class="app-chip">DOH TCL-NCD</span>
      <button type="button" onclick="openEnrollModal()" class="inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2 rounded shadow text-xs font-semibold hover:bg-slate-800 transition ml-1">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> Enroll NCD Patient
      </button>
    </div>
  </div>
</div>

<!-- Primary Navigation Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/ncd/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
        <i class="fas fa-address-book text-xl"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Registry</div>
        <div class="text-lg font-semibold">Patient Registry</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Masterlist of enrolled hypertensive, diabetic, and chronic care clients.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/ncd/visits/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <i class="fas fa-heart-pulse text-xl"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Clinical Logs</div>
        <div class="text-lg font-semibold">Consultations &amp; Vitals</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Record BP measurements, blood sugar (FBS), meds dispensing, and appointments.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/ncd/tcl.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-pink-100 text-pink-700 flex items-center justify-center">
        <i class="fas fa-table-list text-xl"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">DOH Reportable</div>
        <div class="text-lg font-semibold">Target Client List (TCL-NCD)</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">DOH-compliant PhilPEN masterlist with quarterly vital indicators and CSV export.</p>
  </a>
</div>

<!-- Summary Metric Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total Enrolled</div>
    <div class="text-2xl font-semibold mt-2 text-slate-900"><?= h(number_format($summary['total_clients'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Active NCD Patients</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Hypertension</div>
    <div class="text-2xl font-semibold mt-2 text-indigo-600"><?= h(number_format($summary['hypertension_count'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">HPN &amp; Combined cases</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Diabetes</div>
    <div class="text-2xl font-semibold mt-2 text-teal-600"><?= h(number_format($summary['diabetes_count'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Type 2 DM clients</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">High Risk</div>
    <div class="text-2xl font-semibold mt-2 text-purple-600"><?= h(number_format($summary['high_risk'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">PhilPEN ≥20% Risk</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Due for Refill</div>
    <div class="text-2xl font-semibold mt-2 text-amber-600"><?= h(number_format($summary['due_this_month'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Next 30 days</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Overdue</div>
    <div class="text-2xl font-semibold mt-2 text-rose-600"><?= h(number_format($summary['overdue'])) ?></div>
    <div class="text-xs text-slate-500 mt-1">Requires follow-up</div>
  </div>
</div>

<!-- 2-Column Feeds: Recent Activity & Upcoming Appointments -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <!-- Recent Consultations & Vitals -->
  <div class="bg-white p-5 rounded shadow">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Recent Activity</div>
        <div class="text-lg font-semibold text-slate-900">Latest Consultations &amp; BP Logs</div>
      </div>
      <a href="/HealthLogs/public/ncd/visits/index.php" class="text-xs text-teal-600 hover:text-teal-800 font-medium">View All &rarr;</a>
    </div>

    <div class="mt-4 space-y-3">
      <?php if (empty($recentVisits)): ?>
        <div class="text-sm text-slate-500 py-4 text-center">No recent consultations logged.</div>
      <?php else: ?>
        <?php foreach ($recentVisits as $visit): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100/70">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-900"><?= h($visit['last_name'] . ', ' . $visit['first_name']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5">
                  <span class="font-medium text-slate-700"><?= ncd_format_diagnosis($visit['diagnosis_type']) ?></span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <?= ncd_bp_badge($visit['bp_systolic'], $visit['bp_diastolic']) ?>
                  <?php if (!empty($visit['blood_sugar_mgdl'])): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-teal-100 text-teal-800 border border-teal-200">
                      <?= strtoupper($visit['blood_sugar_type'] ?: 'BS') ?>: <?= number_format($visit['blood_sugar_mgdl'], 1) ?> mg/dL
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($visit['medications_dispensed'])): ?>
                    <span class="text-xs text-slate-500 truncate max-w-xs" title="<?= h($visit['medications_dispensed']) ?>">
                      <i class="fas fa-pills mr-1 text-slate-400"></i><?= h($visit['medications_dispensed']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              <span class="text-xs font-mono font-semibold text-slate-700 bg-slate-200/80 px-2 py-1 rounded border border-slate-300 shrink-0">
                <?= h($visit['ncd_code']) ?>
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2 flex items-center justify-between border-t border-slate-200/60 pt-2">
              <span>Date: <?= date('M d, Y', strtotime($visit['visit_date'])) ?></span>
              <span>Adherence: <strong class="<?= $visit['treatment_adherence'] === 'good' ? 'text-emerald-600' : ($visit['treatment_adherence'] === 'fair' ? 'text-amber-600' : 'text-rose-600') ?>"><?= ucfirst($visit['treatment_adherence']) ?></strong></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Upcoming Appointments -->
  <div class="bg-white p-5 rounded shadow">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Upcoming Schedule</div>
        <div class="text-lg font-semibold text-slate-900">Follow-up &amp; Medicine Refills</div>
      </div>
      <a href="/HealthLogs/public/reminders.php" class="text-xs text-teal-600 hover:text-teal-800 font-medium">Reminders Queue &rarr;</a>
    </div>

    <div class="mt-4 space-y-3">
      <?php if (empty($upcomingAppointments)): ?>
        <div class="text-sm text-slate-500 py-4 text-center">No upcoming appointments scheduled.</div>
      <?php else: ?>
        <?php foreach ($upcomingAppointments as $app): ?>
          <?php $isOverdue = ($app['next_appointment_date'] < date('Y-m-d')); ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-slate-100/70">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-900"><?= h($app['last_name'] . ', ' . $app['first_name']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5">
                  <?= ncd_format_diagnosis($app['diagnosis_type']) ?> &bull; Brgy. <?= h($app['barangay'] ?: '—') ?>
                </div>
                <?php if (!empty($app['contact_no'])): ?>
                  <div class="text-xs text-slate-500 mt-1">
                    <i class="fas fa-phone text-slate-400 mr-1"></i><?= h($app['contact_no']) ?>
                  </div>
                <?php endif; ?>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $isOverdue ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-100 text-amber-700 border border-amber-200' ?>">
                <?= $isOverdue ? '<i class="fas fa-circle-exclamation mr-1 text-xs"></i> Overdue' : '<i class="fas fa-clock mr-1 text-xs"></i> Scheduled' ?>
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2 flex items-center justify-between border-t border-slate-200/60 pt-2">
              <span>Appointment: <strong class="text-slate-700"><?= date('M d, Y', strtotime($app['next_appointment_date'])) ?></strong></span>
              <span class="font-mono text-slate-500"><?= h($app['ncd_code']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/ncd/_enroll_modal.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
