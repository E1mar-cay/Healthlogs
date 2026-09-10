<?php
$pageTitle = 'Family Planning Module';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$summary = [
    'active_users' => 0,
    'total_clients' => 0,
    'due_this_month' => 0,
    'overdue' => 0,
];

$recentVisits = [];
$upcomingAppointments = [];

try {
    $summary['total_clients'] = (int)$pdo->query("SELECT COUNT(*) FROM fp_records")->fetchColumn();
    $summary['active_users'] = (int)$pdo->query("SELECT COUNT(*) FROM fp_records WHERE status = 'active'")->fetchColumn();

    // Compute due and overdue counts
    $today = date('Y-m-d');
    $monthEnd = date('Y-m-d', strtotime('+30 days'));

    $summary['due_this_month'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM fp_records r
        JOIN (
            SELECT fp_record_id, MAX(next_appointment_date) AS next_date 
            FROM fp_visits 
            GROUP BY fp_record_id
        ) v ON v.fp_record_id = r.id
        WHERE r.status = 'active' AND v.next_date >= CURDATE() AND v.next_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetchColumn();

    $summary['overdue'] = (int)$pdo->query("
        SELECT COUNT(*) 
        FROM fp_records r
        JOIN (
            SELECT fp_record_id, MAX(next_appointment_date) AS next_date 
            FROM fp_visits 
            GROUP BY fp_record_id
        ) v ON v.fp_record_id = r.id
        WHERE r.status = 'active' AND v.next_date < CURDATE()
    ")->fetchColumn();

    // Recent Visits
    $recentVisits = $pdo->query("
        SELECT v.visit_date, v.method_prescribed, v.quantity_dispensed, v.bp_systolic, v.bp_diastolic, 
               p.first_name, p.last_name, r.client_code
        FROM fp_visits v
        JOIN fp_records r ON r.id = v.fp_record_id
        JOIN patients p ON p.id = r.patient_id
        ORDER BY v.visit_date DESC, v.id DESC
        LIMIT 5
    ")->fetchAll();

    // Upcoming Appointments
    $upcomingAppointments = $pdo->query("
        SELECT r.client_code, r.method_accepted, p.first_name, p.last_name, p.contact_no, p.barangay,
               lv.next_appointment_date
        FROM fp_records r
        JOIN patients p ON p.id = r.patient_id
        JOIN (
            SELECT fp_record_id, MAX(next_appointment_date) AS next_appointment_date
            FROM fp_visits
            WHERE next_appointment_date IS NOT NULL
            GROUP BY fp_record_id
        ) lv ON lv.fp_record_id = r.id
        WHERE r.status = 'active'
        ORDER BY 
            CASE WHEN lv.next_appointment_date < CURDATE() THEN 0 ELSE 1 END,
            lv.next_appointment_date ASC
        LIMIT 5
    ")->fetchAll();

} catch (Throwable $e) {
    // Keep module usable even if summary queries fail
}

function fp_format_method_label(?string $m): string {
    $map = [
        'pills_coc' => 'Pills (COC)',
        'pills_pop' => 'Pills (POP)',
        'injectable_dmpa' => 'DMPA Injectable',
        'implant' => 'Subdermal Implant',
        'iud_interval' => 'IUD (Interval)',
        'iud_postpartum' => 'IUD (Postpartum)',
        'condom' => 'Condom',
        'btl' => 'BTL',
        'nsv' => 'NSV',
        'natural_lam' => 'LAM',
        'natural_sdm' => 'SDM',
        'natural_stm' => 'STM',
    ];
    return $map[$m] ?? ucwords(str_replace('_', ' ', (string)$m));
}
?>

<?php display_flash_messages(); ?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Reproductive Health</div>
      <div class="text-2xl font-semibold">Family Planning Module</div>
      <p class="text-sm text-slate-500 mt-1">Manage contraceptive methods, client records, and service consultations in one place.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">Responsible Parenthood</span>
      <span class="app-chip">DOH Form 1</span>
      <button type="button" onclick="openEnrollModal()" class="inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2 rounded shadow text-xs font-semibold hover:bg-slate-800 transition ml-1">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> Enroll New Client
      </button>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/family_planning/records/index.php">
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
        <div class="text-lg font-semibold">Client Records</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Profile clients, track accepted contraceptive methods, and partner details.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/family_planning/visits/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Services</div>
        <div class="text-lg font-semibold">Visits &amp; Dispensing</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log consultations, vital signs, supplies issued, and next appointment dates.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/family_planning/tcl.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-pink-100 text-pink-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line>
          <line x1="3" y1="15" x2="21" y2="15"></line>
          <line x1="9" y1="3" x2="9" y2="21"></line>
          <line x1="15" y1="3" x2="15" y2="21"></line>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">DOH Reportable</div>
        <div class="text-lg font-semibold">Target Client List (TCL-FP)</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Official DOH Form 1 registry with quarterly service logs and CSV export.</p>
  </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Active Users</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['active_users'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Current contraceptive clients</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Total Registered</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['total_clients'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">All-time enrolled cases</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Due for Refill</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['due_this_month'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Appointments in next 30 days</div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Overdue</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($summary['overdue'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Need follow-up outreach</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Activity</div>
    <div class="text-lg font-semibold">Latest Consultations &amp; Dispensing</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($recentVisits)): ?>
        <div class="text-sm text-slate-500">No recent consultations logged.</div>
      <?php else: ?>
        <?php foreach ($recentVisits as $visit): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($visit['last_name'] . ', ' . $visit['first_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1">
                  <?= h($visit['method_prescribed']) ?> &bull; Qty: <?= (int)$visit['quantity_dispensed'] ?>
                  <?php if (!empty($visit['bp_systolic'])): ?>
                    &bull; BP: <?= (int)$visit['bp_systolic'] ?>/<?= (int)$visit['bp_diastolic'] ?>
                  <?php endif; ?>
                </div>
              </div>
              <span class="text-xs font-mono font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                <?= h($visit['client_code']) ?>
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2"><?= date('M d, Y', strtotime($visit['visit_date'])) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Summary</div>
    <div class="text-lg font-semibold">Upcoming Appointments Snapshot</div>
    <div class="mt-4 space-y-3">
      <?php if (empty($upcomingAppointments)): ?>
        <div class="text-sm text-slate-500">No upcoming appointments scheduled.</div>
      <?php else: ?>
        <?php foreach ($upcomingAppointments as $app): ?>
          <?php $isOverdue = ($app['next_appointment_date'] < date('Y-m-d')); ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-medium text-slate-900"><?= h($app['last_name'] . ', ' . $app['first_name']) ?></div>
                <div class="text-sm text-slate-500 mt-1">
                  <?= fp_format_method_label($app['method_accepted']) ?> &bull; Brgy. <?= h($app['barangay'] ?: '—') ?>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium <?= $isOverdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' ?>">
                <?= $isOverdue ? 'Overdue' : 'Due Soon' ?>
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-2 flex items-center justify-between">
              <span>Appointment: <?= date('M d, Y', strtotime($app['next_appointment_date'])) ?></span>
              <span class="font-mono"><?= h($app['client_code']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/family_planning/_enroll_modal.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
