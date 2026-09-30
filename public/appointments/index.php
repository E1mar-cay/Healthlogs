<?php
$pageTitle = 'Patient Appointments';
require __DIR__ . '/../partials/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$serviceFilter = trim($_GET['service_id'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date_filter'] ?? '');
$classificationFilter = trim($_GET['classification'] ?? '');
$purokFilter = trim($_GET['purok'] ?? '');
$patientIdFilter = (int)($_GET['patient_id'] ?? 0);
$isPrintMode = (isset($_GET['print']) && $_GET['print'] === '1');

$whereParts = [];
$params = [];

if ($q !== '') {
    $whereParts[] = "(a.appointment_code LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR a.reason LIKE ? OR a.assigned_personnel LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($serviceFilter !== '') {
    $whereParts[] = "a.service_id = ?";
    $params[] = (int)$serviceFilter;
}
if ($statusFilter !== '') {
    $whereParts[] = "a.status = ?";
    $params[] = $statusFilter;
}
if ($purokFilter !== '') {
    $whereParts[] = "p.barangay = ?";
    $params[] = $purokFilter;
}
if ($patientIdFilter > 0) {
    $whereParts[] = "a.patient_id = ?";
    $params[] = $patientIdFilter;
}
if ($classificationFilter !== '') {
    if ($classificationFilter === 'pwd') {
        $whereParts[] = "p.is_pwd = 1";
    } elseif ($classificationFilter === '4ps') {
        $whereParts[] = "p.is_4ps = 1";
    } else {
        $whereParts[] = "p.classification = ?";
        $params[] = $classificationFilter;
    }
}

if ($dateFilter === 'today') {
    $whereParts[] = "a.appointment_date = CURDATE()";
} elseif ($dateFilter === 'tomorrow') {
    $whereParts[] = "a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
} elseif ($dateFilter === 'this_week') {
    $whereParts[] = "a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($dateFilter === 'past_overdue') {
    $whereParts[] = "a.appointment_date < CURDATE() AND a.status IN ('scheduled', 'rescheduled')";
}

$whereSql = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// KPI Statistics
$kpis = [
    'today' => 0,
    'upcoming' => 0,
    'completed' => 0,
    'rescheduled' => 0,
    'cancelled' => 0,
    'total' => 0
];

try {
    $kpiRow = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN appointment_date = CURDATE() AND status IN ('scheduled', 'rescheduled') THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN appointment_date > CURDATE() AND status IN ('scheduled', 'rescheduled') THEN 1 ELSE 0 END) AS upcoming_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) AS rescheduled_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
        FROM patient_appointments
    ")->fetch(PDO::FETCH_ASSOC);

    if ($kpiRow) {
        $kpis['total'] = (int)$kpiRow['total'];
        $kpis['today'] = (int)$kpiRow['today_count'];
        $kpis['upcoming'] = (int)$kpiRow['upcoming_count'];
        $kpis['completed'] = (int)$kpiRow['completed_count'];
        $kpis['rescheduled'] = (int)$kpiRow['rescheduled_count'];
        $kpis['cancelled'] = (int)$kpiRow['cancelled_count'];
    }
} catch (Throwable $e) {}

// Services List
$services = $pdo->query("SELECT id, service_code, service_name, category FROM health_services WHERE is_active = 1 ORDER BY service_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// If print mode requested
if ($isPrintMode) {
    $printStmt = $pdo->prepare("
        SELECT a.*, hs.service_name, hs.service_code, p.first_name, p.last_name, p.birth_date, p.sex, p.barangay, p.contact_no, p.classification
        FROM patient_appointments a
        JOIN health_services hs ON hs.id = a.service_id
        JOIN patients p ON p.id = a.patient_id
        $whereSql
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $printStmt->execute($params);
    $printRows = $printStmt->fetchAll(PDO::FETCH_ASSOC);
    include __DIR__ . '/_print_schedule.php';
    exit;
}

// Pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM patient_appointments a
    JOIN health_services hs ON hs.id = a.service_id
    JOIN patients p ON p.id = a.patient_id
    $whereSql
");
$countStmt->execute($params);
$totalAppointments = (int)$countStmt->fetchColumn();

$paginator = paginate($totalAppointments, 15);

$querySql = "
    SELECT a.*, hs.service_name, hs.service_code, hs.category AS service_category,
           p.first_name, p.last_name, p.middle_name, p.birth_date, p.sex, p.barangay, p.contact_no, p.classification, p.is_pwd, p.is_4ps
    FROM patient_appointments a
    JOIN health_services hs ON hs.id = a.service_id
    JOIN patients p ON p.id = a.patient_id
    $whereSql
    ORDER BY 
      CASE WHEN a.appointment_date = CURDATE() THEN 0 WHEN a.appointment_date > CURDATE() THEN 1 ELSE 2 END ASC,
      a.appointment_date ASC, a.appointment_time ASC
    " . $paginator->getLimitSql();

$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../partials/header.php';
?>

<?php display_flash_messages(); ?>

<!-- Top Header -->
<div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs mb-6">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-teal-600 uppercase tracking-widest">
        <i class="fas fa-calendar-check mr-1"></i> Patient Appointment Lifecycle & Scheduling
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mt-0.5">Appointments & Follow-ups</h1>
      <p class="text-xs text-slate-500 mt-1">Schedule, reschedule, complete, cancel, and monitor appointments based on required health services and patient classifications.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a target="_blank" href="?<?= h(http_build_query(array_merge($_GET, ['print' => '1']))) ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition border border-slate-300 shadow-xs">
        <i class="fas fa-print text-teal-700"></i> Print Schedule Manifest
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="appointmentModalOpenNew" data-embed-url="/HealthLogs/public/appointments/form_embed.php" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
          <i class="fas fa-calendar-plus"></i> New Appointment
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring & Oversight Mode
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 mt-5 pt-4 border-t border-slate-100 text-xs">
    <a href="?date_filter=today" class="p-3 rounded-xl border <?= $dateFilter === 'today' ? 'bg-teal-600 text-white border-teal-600' : 'bg-teal-50/60 border-teal-200 hover:bg-teal-100 text-teal-900' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['today']) ?></div>
      <div class="text-[11px] <?= $dateFilter === 'today' ? 'text-teal-100' : 'text-teal-700' ?>">Today's Schedule</div>
    </a>
    <a href="?date_filter=this_week" class="p-3 rounded-xl border <?= $dateFilter === 'this_week' ? 'bg-blue-600 text-white border-blue-600' : 'bg-blue-50/60 border-blue-200 hover:bg-blue-100 text-blue-900' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['upcoming']) ?></div>
      <div class="text-[11px] <?= $dateFilter === 'this_week' ? 'text-blue-100' : 'text-blue-700' ?>">Upcoming (7 Days)</div>
    </a>
    <a href="?status=completed" class="p-3 rounded-xl border <?= $statusFilter === 'completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100 text-emerald-900' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['completed']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'completed' ? 'text-emerald-100' : 'text-emerald-700' ?>">Completed</div>
    </a>
    <a href="?status=rescheduled" class="p-3 rounded-xl border <?= $statusFilter === 'rescheduled' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50/60 border-amber-200 hover:bg-amber-100 text-amber-900' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['rescheduled']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'rescheduled' ? 'text-amber-100' : 'text-amber-700' ?>">Rescheduled</div>
    </a>
    <a href="?status=cancelled" class="p-3 rounded-xl border <?= $statusFilter === 'cancelled' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50/60 border-rose-200 hover:bg-rose-100 text-rose-900' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['cancelled']) ?></div>
      <div class="text-[11px] <?= $statusFilter === 'cancelled' ? 'text-rose-100' : 'text-rose-700' ?>">Cancelled</div>
    </a>
    <a href="/HealthLogs/public/appointments/index.php" class="p-3 rounded-xl border <?= empty($statusFilter) && empty($dateFilter) ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700' ?>">
      <div class="font-bold text-xl leading-tight"><?= number_format($kpis['total']) ?></div>
      <div class="text-[11px] <?= empty($statusFilter) && empty($dateFilter) ? 'text-slate-300' : 'text-slate-500' ?>">All Appointments</div>
    </a>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 mb-6">
  <div class="md:col-span-2 relative">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
      <i class="fas fa-search text-xs"></i>
    </span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search patient, appointment code, staff..." />
  </div>

  <!-- Service Required Filter -->
  <select name="service_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All Health Services</option>
    <?php foreach ($services as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= $serviceFilter === (string)$s['id'] ? 'selected' : '' ?>>
        <?= h($s['service_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Patient Classification Filter -->
  <select name="classification" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All Classifications</option>
    <option value="infant" <?= $classificationFilter === 'infant' ? 'selected' : '' ?>>Babies / Infants (0-11m)</option>
    <option value="under_five" <?= $classificationFilter === 'under_five' ? 'selected' : '' ?>>Under-5 Children (1-4y)</option>
    <option value="pregnant" <?= $classificationFilter === 'pregnant' ? 'selected' : '' ?>>Pregnant Mothers</option>
    <option value="senior" <?= $classificationFilter === 'senior' ? 'selected' : '' ?>>Senior Citizens (60+y)</option>
    <option value="pwd" <?= $classificationFilter === 'pwd' ? 'selected' : '' ?>>Persons with Disability (PWD)</option>
    <option value="4ps" <?= $classificationFilter === '4ps' ? 'selected' : '' ?>>4Ps Priority Group</option>
  </select>

  <!-- Status Filter -->
  <select name="status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All Statuses</option>
    <option value="scheduled" <?= $statusFilter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
    <option value="rescheduled" <?= $statusFilter === 'rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
  </select>

  <div class="flex gap-2">
    <button class="w-full bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition" href="/HealthLogs/public/appointments/index.php">Clear</a>
  </div>
</form>

<!-- Appointments Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
  <div class="overflow-x-auto">
    <table class="min-w-full text-xs text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3 px-4">Code</th>
          <th class="py-3 px-4">Patient & Classification</th>
          <th class="py-3 px-4">Service Required</th>
          <th class="py-3 px-4">Date & Time</th>
          <th class="py-3 px-4">Assigned Personnel</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4">Reason & Notes</th>
          <th class="py-3 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($rows)): ?>
          <tr>
            <td class="px-4 py-8 text-center text-slate-500" colspan="8">
              No appointments found matching the specified criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $apt): ?>
            <?php
              $isToday = $apt['appointment_date'] === date('Y-m-d');
              $statusBadge = match($apt['status']) {
                  'scheduled' => 'bg-blue-100 text-blue-800 border-blue-200',
                  'rescheduled' => 'bg-amber-100 text-amber-800 border-amber-200',
                  'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                  'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                  default => 'bg-slate-100 text-slate-700 border-slate-200',
              };
            ?>
            <tr class="hover:bg-slate-50/70 transition <?= $isToday ? 'bg-teal-50/20' : '' ?>">
              <!-- Code -->
              <td class="px-4 py-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                <?= h($apt['appointment_code']) ?>
                <?php if ($isToday): ?>
                  <span class="inline-block w-2 h-2 rounded-full bg-teal-500 ml-1" title="Scheduled for Today"></span>
                <?php endif; ?>
              </td>

              <!-- Patient -->
              <td class="px-4 py-3">
                <a href="/HealthLogs/public/patients/view.php?id=<?= (int)$apt['patient_id'] ?>" class="font-bold text-slate-900 hover:text-teal-700 transition">
                  <?= h($apt['last_name'] . ', ' . $apt['first_name'] . ($apt['middle_name'] ? ' ' . $apt['middle_name'] : '')) ?>
                </a>
                <div class="mt-1 flex items-center gap-1 flex-wrap">
                  <?= PatientClassifier::renderBadge($apt['classification'] ?? 'adult', $apt['birth_date']) ?>
                  <?php if (!empty($apt['is_4ps'])): ?>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">4Ps</span>
                  <?php endif; ?>
                  <?php if (!empty($apt['is_pwd'])): ?>
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">PWD</span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Service Required -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900"><?= h($apt['service_name']) ?></div>
                <div class="text-[10px] font-mono text-slate-400 uppercase mt-0.5"><?= h($apt['service_code']) ?></div>
              </td>

              <!-- Date & Time -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="font-bold <?= $isToday ? 'text-teal-700' : 'text-slate-800' ?>">
                  <?= date('M d, Y', strtotime($apt['appointment_date'])) ?>
                </div>
                <div class="text-[11px] text-slate-500"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
              </td>

              <!-- Assigned Personnel -->
              <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                <i class="fas fa-user-nurse text-[10px] mr-1 text-slate-400"></i> <?= h($apt['assigned_personnel']) ?>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border <?= $statusBadge ?>">
                  <?= ucfirst(h($apt['status'])) ?>
                </span>
                <?php if ($apt['status'] === 'rescheduled' && $apt['previous_appointment_date']): ?>
                  <div class="text-[10px] text-amber-700 mt-0.5">
                    Was: <?= date('M d', strtotime($apt['previous_appointment_date'])) ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Reason & Clinical Notes -->
              <td class="px-4 py-3 max-w-xs text-slate-600">
                <?php if ($apt['reason']): ?>
                  <div><strong class="text-slate-700">Reason:</strong> <?= h($apt['reason']) ?></div>
                <?php endif; ?>
                <?php if ($apt['reschedule_reason']): ?>
                  <div class="text-amber-700 mt-0.5"><strong class="text-amber-800">Rescheduled:</strong> <?= h($apt['reschedule_reason']) ?></div>
                <?php endif; ?>
                <?php if ($apt['clinical_notes']): ?>
                  <div class="text-emerald-700 mt-0.5"><strong class="text-emerald-800">Notes:</strong> <?= h($apt['clinical_notes']) ?></div>
                <?php endif; ?>
                <?php if ($apt['cancellation_reason']): ?>
                  <div class="text-rose-700 mt-0.5"><strong class="text-rose-800">Cancelled:</strong> <?= h($apt['cancellation_reason']) ?></div>
                <?php endif; ?>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <div class="flex items-center justify-end gap-1.5">
                    <?php if ($apt['status'] === 'scheduled' || $apt['status'] === 'rescheduled'): ?>
                      <!-- Complete Button -->
                      <button type="button" class="btn-complete px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs border border-emerald-200 transition" 
                              data-id="<?= (int)$apt['id'] ?>" 
                              data-code="<?= h($apt['appointment_code']) ?>"
                              data-patient="<?= h($apt['last_name'] . ', ' . $apt['first_name']) ?>"
                              data-service="<?= h($apt['service_name']) ?>">
                        <i class="fas fa-check text-[10px]"></i> Complete
                      </button>

                      <!-- Reschedule Button -->
                      <button type="button" class="btn-reschedule px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs border border-amber-200 transition"
                              data-id="<?= (int)$apt['id'] ?>"
                              data-code="<?= h($apt['appointment_code']) ?>"
                              data-patient="<?= h($apt['last_name'] . ', ' . $apt['first_name']) ?>"
                              data-date="<?= h($apt['appointment_date']) ?>"
                              data-time="<?= h(substr($apt['appointment_time'], 0, 5)) ?>">
                        <i class="fas fa-clock-rotate-left text-[10px]"></i> Reschedule
                      </button>

                      <!-- Cancel Button -->
                      <button type="button" class="btn-cancel px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs border border-rose-200 transition"
                              data-id="<?= (int)$apt['id'] ?>"
                              data-code="<?= h($apt['appointment_code']) ?>"
                              data-patient="<?= h($apt['last_name'] . ', ' . $apt['first_name']) ?>">
                        <i class="fas fa-times text-[10px]"></i> Cancel
                      </button>
                    <?php else: ?>
                      <span class="text-[11px] text-slate-400 font-medium">Archived</span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-slate-400 text-xs font-medium"><i class="fas fa-eye mr-1"></i> Read-Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?= $paginator->render() ?>
</div>

<!-- MODAL: RESCHEDULE APPOINTMENT -->
<div id="rescheduleModal" class="fixed inset-0 z-[100] hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" id="rescheduleBackdrop"></div>
  <div class="relative z-10 mx-auto mt-12 max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Reschedule Appointment</h3>
          <p id="rescheduleSubtext" class="text-xs text-slate-500 mt-0.5"></p>
        </div>
        <button type="button" class="close-reschedule text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
      </div>
      <form method="post" action="/HealthLogs/public/appointments/reschedule.php" class="space-y-4">
        <input type="hidden" name="id" id="rescheduleId" />
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">New Appointment Date <span class="text-rose-500">*</span></label>
          <input type="date" name="appointment_date" id="rescheduleDate" required min="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">New Appointment Time <span class="text-rose-500">*</span></label>
          <input type="time" name="appointment_time" id="rescheduleTime" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason for Rescheduling <span class="text-rose-500">*</span></label>
          <textarea name="reschedule_reason" required rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Patient requested schedule adjustment, health worker field visit conflict..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" class="close-reschedule px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition shadow-xs">Confirm Reschedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: COMPLETE APPOINTMENT -->
<div id="completeModal" class="fixed inset-0 z-[100] hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" id="completeBackdrop"></div>
  <div class="relative z-10 mx-auto mt-12 max-w-lg px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Complete Appointment Encounter</h3>
          <p id="completeSubtext" class="text-xs text-slate-500 mt-0.5"></p>
        </div>
        <button type="button" class="close-complete text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
      </div>
      <form method="post" action="/HealthLogs/public/appointments/complete.php" class="space-y-4">
        <input type="hidden" name="id" id="completeId" />
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Clinical Outcome & Management Notes</label>
          <textarea name="clinical_notes" rows="3" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Service successfully rendered. Patient vitals stable. Prescribed maintenance medicines. Advised on follow-up in 1 month..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" class="close-complete px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">Dismiss</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-xs">
            <i class="fas fa-check mr-1"></i> Mark as Completed
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: CANCEL APPOINTMENT -->
<div id="cancelModal" class="fixed inset-0 z-[100] hidden" aria-modal="true" role="dialog">
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" id="cancelBackdrop"></div>
  <div class="relative z-10 mx-auto mt-12 max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden p-6">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Cancel Appointment</h3>
          <p id="cancelSubtext" class="text-xs text-slate-500 mt-0.5"></p>
        </div>
        <button type="button" class="close-cancel text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times"></i></button>
      </div>
      <form method="post" action="/HealthLogs/public/appointments/cancel.php" class="space-y-4">
        <input type="hidden" name="id" id="cancelId" />
        <div>
          <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason for Cancellation <span class="text-rose-500">*</span></label>
          <textarea name="cancellation_reason" rows="2" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Patient moved to another municipality, cancelled by guardian..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" class="close-cancel px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">Dismiss</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-xs">
            <i class="fas fa-ban mr-1"></i> Cancel Appointment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: NEW / EDIT APPOINTMENT IFRAME -->
<div id="appointmentModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="appointmentModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-3 sm:mt-6 max-w-4xl px-2 sm:px-4">
    <div class="rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)]">
      <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-bold text-slate-900">Schedule Patient Appointment</div>
        <button type="button" id="appointmentModalClose" class="rounded-xl border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="appointmentModalFrame" class="w-full min-h-[75vh] border-0 flex-1" title="Appointment form"></iframe>
    </div>
  </div>
</div>

<script>
(function() {
  // Appointment Form Modal
  const apptModal = document.getElementById('appointmentModal');
  const apptFrame = document.getElementById('appointmentModalFrame');
  const apptBackdrop = document.getElementById('appointmentModalBackdrop');
  const apptClose = document.getElementById('appointmentModalClose');
  const apptOpenBtn = document.getElementById('appointmentModalOpenNew');

  function openApptModal(url) {
    if (!apptModal || !apptFrame) return;
    apptFrame.src = url;
    apptModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeApptModal() {
    if (!apptModal || !apptFrame) return;
    apptModal.classList.add('hidden');
    apptFrame.src = 'about:blank';
    document.body.classList.remove('overflow-hidden');
  }

  if (apptOpenBtn) {
    apptOpenBtn.addEventListener('click', function() {
      openApptModal(this.getAttribute('data-embed-url'));
    });
  }
  if (apptBackdrop) apptBackdrop.addEventListener('click', closeApptModal);
  if (apptClose) apptClose.addEventListener('click', closeApptModal);

  // Reschedule Modal
  const reschedModal = document.getElementById('rescheduleModal');
  document.querySelectorAll('.btn-reschedule').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('rescheduleId').value = this.getAttribute('data-id');
      document.getElementById('rescheduleSubtext').textContent = this.getAttribute('data-code') + ' • ' + this.getAttribute('data-patient');
      document.getElementById('rescheduleDate').value = this.getAttribute('data-date');
      document.getElementById('rescheduleTime').value = this.getAttribute('data-time') || '08:30';
      reschedModal.classList.remove('hidden');
    });
  });
  document.querySelectorAll('.close-reschedule, #rescheduleBackdrop').forEach(function(el) {
    el.addEventListener('click', function() {
      reschedModal.classList.add('hidden');
    });
  });

  // Complete Modal
  const compModal = document.getElementById('completeModal');
  document.querySelectorAll('.btn-complete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('completeId').value = this.getAttribute('data-id');
      document.getElementById('completeSubtext').textContent = this.getAttribute('data-code') + ' • ' + this.getAttribute('data-patient') + ' (' + this.getAttribute('data-service') + ')';
      compModal.classList.remove('hidden');
    });
  });
  document.querySelectorAll('.close-complete, #completeBackdrop').forEach(function(el) {
    el.addEventListener('click', function() {
      compModal.classList.add('hidden');
    });
  });

  // Cancel Modal
  const cancModal = document.getElementById('cancelModal');
  document.querySelectorAll('.btn-cancel').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('cancelId').value = this.getAttribute('data-id');
      document.getElementById('cancelSubtext').textContent = this.getAttribute('data-code') + ' • ' + this.getAttribute('data-patient');
      cancModal.classList.remove('hidden');
    });
  });
  document.querySelectorAll('.close-cancel, #cancelBackdrop').forEach(function(el) {
    el.addEventListener('click', function() {
      cancModal.classList.add('hidden');
    });
  });
})();
</script>
