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

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Welcome back, <?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
      <div class="text-2xl font-semibold text-slate-900 mt-1">Health Worker Dashboard</div>
      <p class="text-sm text-slate-500 mt-1">Daily patient intake, priority community health programs, and outreach reminders.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip bg-teal-50 text-teal-700 border border-teal-200">
        <i class="fas fa-user-nurse mr-1"></i> BHW Shift Ready
      </span>
      <a href="/HealthLogs/public/reminders.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-700 hover:bg-teal-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-bell"></i> Reminders Queue (<?= $hwStats['pending_reminders'] ?>)
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
  <!-- Patient Intake Card -->
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3">
        <span class="h-11 w-11 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-base shadow-xs">
          <i class="fas fa-users"></i>
        </span>
        <div>
          <div class="text-xs uppercase tracking-widest text-slate-400 font-bold">Patient Records</div>
          <div class="text-2xl font-bold mt-0.5 text-slate-900"><?= number_format($hwStats['total_patients']) ?></div>
        </div>
      </div>
      <div class="text-xs text-slate-500 mt-3">Register new patients &amp; update records.</div>
    </div>
    <a class="w-full inline-flex items-center justify-center mt-4 px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/patients/index.php">
      Open Patients
    </a>
  </div>

  <!-- Immunization Card -->
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3">
        <span class="h-11 w-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shadow-xs">
          <i class="fas fa-syringe"></i>
        </span>
        <div>
          <div class="text-xs uppercase tracking-widest text-slate-400 font-bold">Immunization</div>
          <div class="text-2xl font-bold mt-0.5 text-slate-900"><?= number_format($hwStats['scheduled_vaccines']) ?></div>
        </div>
      </div>
      <div class="text-xs text-slate-500 mt-3">Log vaccines, schedules &amp; TCL-2.</div>
    </div>
    <a class="w-full inline-flex items-center justify-center mt-4 px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/immunization.php">
      Open Immunization
    </a>
  </div>

  <!-- Reminders & SMS Card -->
  <div class="bg-white p-5 rounded-xl shadow border border-slate-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3">
        <span class="h-11 w-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-base shadow-xs">
          <i class="fas fa-bell"></i>
        </span>
        <div>
          <div class="text-xs uppercase tracking-widest text-slate-500 font-bold">SMS Reminders</div>
          <div class="text-2xl font-bold mt-0.5 text-slate-900"><?= number_format($hwStats['pending_reminders']) ?></div>
        </div>
      </div>
      <div class="text-xs text-slate-600 font-medium mt-3">
        <?php if ($hwStats['due_today_reminders'] > 0): ?>
          <span class="text-rose-600 font-bold"><i class="fas fa-circle-exclamation mr-1"></i><?= $hwStats['due_today_reminders'] ?> due today</span> for outreach
        <?php else: ?>
          Patient follow-up notifications
        <?php endif; ?>
      </div>
    </div>
    <a class="w-full inline-flex items-center justify-center mt-4 px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/reminders.php">
      Open Reminders
    </a>
  </div>

  <!-- Medicine Inventory Card -->
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3">
        <span class="h-11 w-11 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-base shadow-xs">
          <i class="fas fa-pills"></i>
        </span>
        <div>
          <div class="text-xs uppercase tracking-widest text-slate-500 font-bold">Medicine Supplies</div>
          <div class="text-2xl font-bold mt-0.5 text-slate-900"><?= number_format($hwStats['low_stock_medicines']) ?></div>
        </div>
      </div>
      <div class="text-xs text-slate-600 font-medium mt-3">
        <?php if ($hwStats['low_stock_medicines'] > 0): ?>
          <span class="text-amber-700 font-bold"><i class="fas fa-triangle-exclamation mr-1"></i><?= $hwStats['low_stock_medicines'] ?> low stock item(s)</span>
        <?php else: ?>
          Supply stocks healthy
        <?php endif; ?>
      </div>
    </div>
    <a class="w-full inline-flex items-center justify-center mt-4 px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/inventory.php">
      View Inventory
    </a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
  <!-- Reminders Follow-up Queue -->
  <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow border border-slate-100">
    <div class="flex items-center justify-between border-b pb-3">
      <div>
        <div class="text-xs uppercase font-bold tracking-wider text-slate-500">Patient Outreach Queue</div>
        <div class="text-lg font-bold text-slate-900 mt-0.5">Upcoming Reminders &amp; Follow-ups</div>
      </div>
      <a class="text-xs text-slate-600 hover:text-slate-900 hover:underline font-semibold" href="/HealthLogs/public/reminders.php">
        View All Reminders &rarr;
      </a>
    </div>

    <div class="mt-4 space-y-3">
      <?php if (empty($upcomingReminders)): ?>
        <div class="py-6 text-center text-slate-400 text-sm">
          <i class="fas fa-check-circle text-emerald-500 text-xl block mb-1"></i>
          All patient reminders are up to date! No pending follow-ups.
        </div>
      <?php else: ?>
        <?php foreach ($upcomingReminders as $rem): ?>
          <?php 
            $isPastDue = strtotime($rem['due_date']) < strtotime(date('Y-m-d'));
            $isToday = $rem['due_date'] === date('Y-m-d');
            $badgeColor = $isPastDue ? 'bg-rose-100 text-rose-800 border-rose-200' : ($isToday ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200');
            $badgeText = $isPastDue ? 'Overdue' : ($isToday ? 'Due Today' : 'Upcoming');
          ?>
          <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition">
            <div class="flex items-center gap-3">
              <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm shrink-0">
                <i class="fas fa-comment-sms"></i>
              </span>
              <div>
                <div class="font-bold text-slate-900 text-sm">
                  <?= h($rem['first_name'] . ' ' . $rem['last_name']) ?>
                  <span class="text-xs font-normal text-slate-400">&bull; <?= h($rem['barangay']) ?></span>
                </div>
                <div class="text-xs text-slate-600 mt-0.5">
                  <strong class="uppercase text-[11px] text-slate-700"><?= h($rem['reminder_type'] ?? 'General') ?>:</strong> 
                  <?= h($rem['message']) ?>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0 ml-4">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border <?= $badgeColor ?>">
                <?= $badgeText ?>
              </span>
              <div class="text-xs font-mono text-slate-500 mt-1"><?= h($rem['due_date']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="bg-white p-5 rounded-xl shadow border border-slate-100 flex flex-col justify-between">
    <div>
      <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Shortcuts</div>
      <div class="text-lg font-bold text-slate-900 mt-0.5">Quick Actions</div>
      
      <div class="mt-4 space-y-2">
        <a class="block px-4 py-3 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow hover:bg-slate-800 transition" href="/HealthLogs/public/patients/form.php">
          <i class="fas fa-user-plus mr-2"></i>New Patient (BHW Intake)
        </a>
        <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/reminders.php">
          <i class="fas fa-bell mr-2 text-slate-600"></i>Send SMS Reminder
        </a>
        <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/immunization/tcl.php">
          <i class="fas fa-table-list mr-2 text-teal-600"></i>Target Client List (TCL-2)
        </a>
        <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/immunization/records/form.php">
          <i class="fas fa-syringe mr-2 text-blue-600"></i>Add Vaccine Record
        </a>
        <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition" href="/HealthLogs/public/inventory/transactions/form.php">
          <i class="fas fa-pills mr-2 text-amber-600"></i>Dispense Medicine
        </a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
