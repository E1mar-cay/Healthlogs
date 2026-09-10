<?php
$pageTitle = 'Health Worker Dashboard';
require __DIR__ . '/../partials/bootstrap.php';

$hwStats = [
    'total_patients' => 0,
    'scheduled_vaccines' => 0,
    'pending_reminders' => 0,
    'due_today_reminders' => 0,
    'low_stock_medicines' => 0,
];

$upcomingReminders = [];

try {
    $hwStats['total_patients'] = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'")->fetchColumn();
    $hwStats['scheduled_vaccines'] = (int)$pdo->query("SELECT COUNT(*) FROM immunization_schedule WHERE status = 'scheduled'")->fetchColumn();
    $hwStats['pending_reminders'] = (int)$pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending'")->fetchColumn();
    $hwStats['due_today_reminders'] = (int)$pdo->query("SELECT COUNT(*) FROM reminders WHERE status = 'pending' AND due_date <= CURDATE()")->fetchColumn();
    
    $hwStats['low_stock_medicines'] = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT m.id
            FROM medicines m
            LEFT JOIN medicine_transactions t ON t.medicine_id = m.id
            GROUP BY m.id, m.reorder_level
            HAVING COALESCE(SUM(t.quantity), 0) <= COALESCE(m.reorder_level, 0)
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
<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Barangay Health Services</div>
      <div class="text-2xl font-semibold">Health Worker Dashboard</div>
      <p class="text-sm text-slate-500 mt-1">Daily patient intake, community health programs, and outreach reminders.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">BHW Shift Active</span>
      <a href="/HealthLogs/public/patients/form.php" class="inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2 rounded shadow text-xs font-semibold hover:bg-slate-800 transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> New Patient Intake
      </a>
    </div>
  </div>
</div>

<!-- Primary Program Navigation Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/patients/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-base">
        <i class="fas fa-users text-lg"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Registry</div>
        <div class="text-lg font-semibold text-slate-900">Patient Directory</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-4">Search profiles, family serials &amp; health history.</div>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base">
        <i class="fas fa-syringe text-lg"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">EPI Program</div>
        <div class="text-lg font-semibold text-slate-900">Immunization</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-4">Child vaccine schedules, doses &amp; TCL-2.</div>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-base">
        <i class="fas fa-person-pregnant text-lg"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">ANC Program</div>
        <div class="text-lg font-semibold text-slate-900">Maternal Care</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-4">8-ANC prenatal checkups, vitamins &amp; delivery.</div>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/family_planning.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-base">
        <i class="fas fa-heart text-lg"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Reproductive</div>
        <div class="text-lg font-semibold text-slate-900">Family Planning</div>
      </div>
    </div>
    <div class="text-xs text-slate-500 mt-4">Contraceptive dispensing, users &amp; DOH Form 1.</div>
  </a>
</div>

<!-- Summary Metric Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-4 rounded shadow">
    <div class="text-xs text-slate-500 font-medium">Registered Patients</div>
    <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($hwStats['total_patients']) ?></div>
    <div class="text-xs text-slate-400 mt-1">Active in community</div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <div class="text-xs text-slate-500 font-medium">Vaccine Schedules</div>
    <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($hwStats['scheduled_vaccines']) ?></div>
    <div class="text-xs text-slate-400 mt-1">Pending child doses</div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <div class="text-xs text-slate-500 font-medium">Pending Reminders</div>
    <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($hwStats['pending_reminders']) ?></div>
    <div class="text-xs text-slate-400 mt-1">
      <?= $hwStats['due_today_reminders'] > 0 ? '<span class="text-rose-600 font-bold">' . $hwStats['due_today_reminders'] . ' due today</span>' : 'Outreach notifications' ?>
    </div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <div class="text-xs text-slate-500 font-medium">Low Stock Medicines</div>
    <div class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($hwStats['low_stock_medicines']) ?></div>
    <div class="text-xs text-slate-400 mt-1">
      <?= $hwStats['low_stock_medicines'] > 0 ? '<span class="text-amber-700 font-bold">Needs resupply</span>' : 'Supplies healthy' ?>
    </div>
  </div>
</div>

<!-- Activity & Shortcuts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
  <!-- Outreach Queue -->
  <div class="lg:col-span-2 bg-white p-6 rounded shadow">
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
          <i class="fas fa-check-circle text-emerald-500 text-2xl block mb-1"></i>
          All patient reminders are up to date! No pending follow-ups.
        </div>
      <?php else: ?>
        <?php foreach ($upcomingReminders as $rem): ?>
          <?php 
            $isPastDue = strtotime($rem['due_date']) < strtotime(date('Y-m-d'));
            $isToday = $rem['due_date'] === date('Y-m-d');
            $badgeClass = $isPastDue ? 'bg-rose-50 text-rose-700 border-rose-200' : ($isToday ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-700 border-slate-200');
            $badgeText = $isPastDue ? 'Overdue' : ($isToday ? 'Due Today' : 'Upcoming');
          ?>
          <div class="flex items-center justify-between p-3 rounded border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition">
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
  <div class="bg-white p-6 rounded shadow flex flex-col justify-between">
    <div>
      <div class="text-xs text-slate-500 uppercase font-semibold">Shortcuts</div>
      <div class="text-lg font-semibold text-slate-900">Quick Actions</div>
      
      <div class="mt-4 space-y-2">
        <a class="block p-3 rounded bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/patients/form.php">
          <i class="fas fa-user-plus mr-2"></i>New Patient Intake
        </a>
        <a class="block p-3 rounded bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/immunization/tcl.php">
          <i class="fas fa-table-list mr-2 text-slate-500"></i>Target Client List (TCL-2)
        </a>
        <a class="block p-3 rounded bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/maternal/tcl.php">
          <i class="fas fa-clipboard-list mr-2 text-slate-500"></i>Maternal Care List (8-ANC TCL)
        </a>
        <a class="block p-3 rounded bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/family_planning/tcl.php">
          <i class="fas fa-file-lines mr-2 text-slate-500"></i>Family Planning List (DOH Form 1)
        </a>
        <a class="block p-3 rounded bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/inventory.php">
          <i class="fas fa-pills mr-2 text-slate-500"></i>Medicine Inventory &amp; Dispensing
        </a>
        <a class="block p-3 rounded bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/reminders.php">
          <i class="fas fa-bell mr-2 text-slate-500"></i>Send SMS Reminder
        </a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
