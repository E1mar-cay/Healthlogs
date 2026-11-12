<?php
$pageTitle = 'Health Worker Dashboard';
require __DIR__ . '/../partials/bootstrap.php';

$hwStats = [
    'total_patients' => 0,
    'scheduled_vaccines' => 0,
    'pending_reminders' => 0,
    'due_today_reminders' => 0,
    'low_stock_medicines' => 0,
    'ncd_patients' => 0,
];

$upcomingReminders = [];

try {
    $hwStats['total_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'")->fetchColumn();
    $hwStats['scheduled_vaccines'] = (int)$pdo->query("SELECT COUNT(*) FROM immunization_schedule WHERE status = 'scheduled'")->fetchColumn();
    $hwStats['pending_reminders'] = (int)$pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending'")->fetchColumn();
    $hwStats['due_today_reminders'] = (int)$pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending' AND due_date <= CURDATE()")->fetchColumn();
    $hwStats['ncd_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM ncd_records WHERE status IN ('active', 'controlled', 'uncontrolled')")->fetchColumn();
    
    $hwStats['low_stock_medicines'] = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT m.id
            FROM medicines m
            LEFT JOIN (
                SELECT mb.medicine_id, SUM(mt.quantity) AS valid_stock
                FROM medicine_batches mb
                JOIN medicine_transactions mt ON mt.batch_id = mb.id
                WHERE mb.expiry_date >= CURDATE()
                GROUP BY mb.medicine_id
            ) stk ON stk.medicine_id = m.id
            GROUP BY m.id, m.reorder_level
            HAVING COALESCE(MAX(stk.valid_stock), 0) <= COALESCE(m.reorder_level, 0)
        ) low_stock
    ")->fetchColumn();

    $upcomingReminders = $pdo->query("
        SELECT r.*, p.first_name, p.last_name, p.contact_no, p.barangay
        FROM reminders r
        JOIN patients p ON p.id = r.patient_id
        WHERE r.status = 'pending'
        ORDER BY r.due_date ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

require __DIR__ . '/../partials/header.php';
?>

<!-- Header Banner -->
<div class="bg-white p-6 rounded-2xl border border-slate-200 border-t-4 border-t-teal-600">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200 mb-1.5">
        <i class="fas fa-stethoscope text-[10px]"></i>
        <span>Barangay Health Services</span>
      </div>
      <div class="text-2xl font-bold text-slate-900">Health Worker Dashboard</div>
      <p class="text-sm text-slate-500 mt-0.5">Daily patient intake, community health programs, and outreach reminders.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> BHW Shift Active</span>
      <a href="/HealthLogs/public/patients/form.php" class="inline-flex items-center justify-center bg-teal-600 text-white px-4 py-2 rounded-xl text-xs font-semibold hover:bg-teal-700 shadow-2xs transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> New Patient Intake
      </a>
    </div>
  </div>
</div>

<!-- Primary Program Navigation Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-6">
  <a class="bg-white p-5 rounded-2xl border border-slate-200 block hover:bg-teal-50/30 transition group" href="/HealthLogs/public/patients/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-teal-50 text-teal-700 border border-teal-200 flex items-center justify-center font-bold text-base shadow-2xs">
        <i class="fas fa-hospital-user text-base"></i>
      </span>
      <div>
        <div class="text-[11px] text-teal-700 font-bold uppercase tracking-wider">Registry</div>
        <div class="text-base font-bold text-slate-900 group-hover:text-teal-800">Patients</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-3">Directory &amp; family serials.</div>
  </a>

  <a class="bg-white p-5 rounded-2xl border border-slate-200 block hover:bg-sky-50/30 transition group" href="/HealthLogs/public/immunization/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 flex items-center justify-center font-bold text-base shadow-2xs">
        <i class="fas fa-syringe text-base"></i>
      </span>
      <div>
        <div class="text-[11px] text-sky-700 font-bold uppercase tracking-wider">EPI Program</div>
        <div class="text-base font-bold text-slate-900 group-hover:text-sky-800">Immunization</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-3">Child vaccines &amp; TCL-2.</div>
  </a>

  <a class="bg-white p-5 rounded-2xl border border-slate-200 block hover:bg-pink-50/30 transition group" href="/HealthLogs/public/maternal.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-pink-50 text-pink-700 border border-pink-200 flex items-center justify-center font-bold text-base shadow-2xs">
        <i class="fas fa-person-pregnant text-base"></i>
      </span>
      <div>
        <div class="text-[11px] text-pink-700 font-bold uppercase tracking-wider">ANC / PNC</div>
        <div class="text-base font-bold text-slate-900 group-hover:text-pink-800">Maternal</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-3">Prenatal, delivery &amp; 4PNC.</div>
  </a>

  <a class="bg-white p-5 rounded-2xl border border-slate-200 block hover:bg-emerald-50/30 transition group" href="/HealthLogs/public/family_planning.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-base shadow-2xs">
        <i class="fas fa-people-roof text-base"></i>
      </span>
      <div>
        <div class="text-[11px] text-emerald-700 font-bold uppercase tracking-wider">Reproductive</div>
        <div class="text-base font-bold text-slate-900 group-hover:text-emerald-800">Family Planning</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-3">Methods &amp; DOH Form 1.</div>
  </a>

  <a class="bg-white p-5 rounded-2xl border border-slate-200 block hover:bg-indigo-50/30 transition group" href="/HealthLogs/public/ncd/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center font-bold text-base shadow-2xs">
        <i class="fas fa-heart-pulse text-base"></i>
      </span>
      <div>
        <div class="text-[11px] text-indigo-700 font-bold uppercase tracking-wider">Chronic Care</div>
        <div class="text-base font-bold text-slate-900 group-hover:text-indigo-800">Non-Communicable</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-3">HPN, Diabetes &amp; PhilPEN.</div>
  </a>
</div>

<!-- Summary Metric Cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mt-6">
  <div class="bg-white p-4 rounded-2xl border border-slate-200 border-l-4 border-l-teal-500">
    <div class="text-xs text-teal-700 font-bold uppercase tracking-wider">Registered Patients</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= number_format($hwStats['total_patients']) ?></div>
    <div class="text-xs text-slate-500 mt-1">Active in community</div>
  </div>

  <div class="bg-white p-4 rounded-2xl border border-slate-200 border-l-4 border-l-sky-500">
    <div class="text-xs text-sky-700 font-bold uppercase tracking-wider">Vaccine Schedules</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= number_format($hwStats['scheduled_vaccines']) ?></div>
    <div class="text-xs text-slate-500 mt-1">Pending child doses</div>
  </div>

  <div class="bg-white p-4 rounded-2xl border border-slate-200 border-l-4 border-l-indigo-500">
    <div class="text-xs text-indigo-700 font-bold uppercase tracking-wider">NCD Patients</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= number_format($hwStats['ncd_patients']) ?></div>
    <div class="text-xs text-slate-500 mt-1">HPN, DM &amp; chronic care</div>
  </div>

  <div class="bg-white p-4 rounded-2xl border border-slate-200 border-l-4 border-l-amber-500">
    <div class="text-xs text-amber-700 font-bold uppercase tracking-wider">Pending Reminders</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= number_format($hwStats['pending_reminders']) ?></div>
    <div class="text-xs text-slate-500 mt-1">
      <?= $hwStats['due_today_reminders'] > 0 ? '<span class="text-amber-700 font-semibold">' . $hwStats['due_today_reminders'] . ' due today</span>' : 'Outreach notifications' ?>
    </div>
  </div>

  <div class="bg-white p-4 rounded-2xl border border-slate-200 border-l-4 <?= $hwStats['low_stock_medicines'] > 0 ? 'border-l-rose-500' : 'border-l-emerald-500' ?> col-span-2 sm:col-span-1">
    <div class="text-xs <?= $hwStats['low_stock_medicines'] > 0 ? 'text-rose-700' : 'text-emerald-700' ?> font-bold uppercase tracking-wider">Low Stock Medicines</div>
    <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= number_format($hwStats['low_stock_medicines']) ?></div>
    <div class="text-xs text-slate-500 mt-1">
      <?= $hwStats['low_stock_medicines'] > 0 ? '<span class="text-rose-700 font-semibold">Needs resupply</span>' : 'Supplies healthy' ?>
    </div>
  </div>
</div>

<!-- Activity & Shortcuts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
  <!-- Outreach Queue -->
  <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <div>
        <div class="text-xs text-slate-500 uppercase font-semibold">Outreach Queue</div>
        <div class="text-lg font-semibold text-slate-900">Upcoming Reminders &amp; Follow-ups</div>
      </div>
      <a class="text-xs text-slate-600 hover:text-slate-900 font-semibold" href="/HealthLogs/public/reminders.php">
        View All &rarr;
      </a>
    </div>

    <div class="mt-4 space-y-3">
      <?php if (empty($upcomingReminders)): ?>
        <div class="py-8 text-center text-slate-400 text-sm">
          <i class="fas fa-check-circle text-slate-400 text-2xl block mb-1"></i>
          All patient reminders are up to date! No pending follow-ups.
        </div>
      <?php else: ?>
        <?php foreach ($upcomingReminders as $rem): ?>
          <?php 
            $isPastDue = strtotime($rem['due_date']) < strtotime(date('Y-m-d'));
            $isToday = $rem['due_date'] === date('Y-m-d');
            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
            $badgeText = $isPastDue ? 'Overdue' : ($isToday ? 'Due Today' : 'Upcoming');
          ?>
          <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded bg-white border border-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">
                <i class="fas fa-comment-sms"></i>
              </span>
              <div>
                <div class="font-semibold text-slate-900 text-sm">
                  <?= h($rem['first_name'] . ' ' . $rem['last_name']) ?>
                  <span class="text-xs font-normal text-slate-400">&bull; <?= h($rem['barangay']) ?></span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                  <span class="font-semibold text-slate-700 uppercase text-[10px]"><?= h($rem['reminder_type'] ?? 'General') ?>:</span>
                  <?= h($rem['message']) ?>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0 ml-4">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold border <?= $badgeClass ?>">
                <?= $badgeText ?>
              </span>
              <div class="text-xs font-mono text-slate-400 mt-1"><?= h($rem['due_date']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick Actions / Program Shortcuts -->
  <div class="bg-white p-6 rounded-xl border border-slate-200 flex flex-col justify-between">
    <div>
      <div class="text-xs text-slate-500 uppercase font-semibold">Shortcuts</div>
      <div class="text-lg font-semibold text-slate-900">Quick Actions</div>
      
      <div class="mt-4 space-y-2">
        <a class="block p-3 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-700 transition" href="/HealthLogs/public/patients/form.php">
          <i class="fas fa-user-plus mr-2"></i>New Patient Intake
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/immunization/tcl.php">
          <i class="fas fa-table-list mr-2 text-slate-500"></i>Target Client List (TCL-2)
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/maternal/tcl.php">
          <i class="fas fa-clipboard-list mr-2 text-slate-500"></i>Maternal Care List (8-ANC TCL)
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/ncd/tcl.php">
          <i class="fas fa-heart-pulse mr-2 text-slate-500"></i>Non-Communicable Disease TCL
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/family_planning/tcl.php">
          <i class="fas fa-venus-mars mr-2 text-slate-500"></i>Family Planning TCL
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/inventory.php">
          <i class="fas fa-pills mr-2 text-slate-500"></i>Medicine Inventory &amp; Dispensing
        </a>
        <a class="block p-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" href="/HealthLogs/public/reminders.php">
          <i class="fas fa-bell mr-2 text-slate-500"></i>Send SMS Reminder
        </a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
