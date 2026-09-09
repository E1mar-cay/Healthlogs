<?php
$pageTitle = 'TB Monitoring Module';
require __DIR__ . '/partials/header.php';

$targetDate = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    $targetDate = date('Y-m-d');
}

$statusFilter = trim($_GET['filter'] ?? 'all'); // 'all', 'needs_medicine', 'already_given', 'missed'
$search = trim($_GET['search'] ?? '');
$successMsg = '';
$errorMsg = '';

// Handle Quick Dispense / Log Intake from TB Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_log_dose') {
    $posted_case_id = (int)($_POST['tb_case_id'] ?? 0);
    $dose_status = trim($_POST['status'] ?? 'supervised');
    $log_date = trim($_POST['log_date'] ?? $targetDate);
    $remarks = trim($_POST['remarks'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if ($posted_case_id > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $log_date)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_dot_logs (tb_case_id, log_date, status, remarks, recorded_by)
                VALUES (:tb_case_id, :log_date, :status, :remarks, :recorded_by)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by)
            ");
            $stmt->execute([
                'tb_case_id' => $posted_case_id,
                'log_date' => $log_date,
                'status' => in_array($dose_status, ['taken', 'supervised', 'missed'], true) ? $dose_status : 'supervised',
                'remarks' => $remarks !== '' ? $remarks : ($dose_status === 'supervised' ? 'Supervised daily dose' : 'Dose recorded'),
                'recorded_by' => $recorded_by,
            ]);

            // Fetch patient name for friendly toast
            $pStmt = $pdo->prepare("SELECT p.first_name, p.last_name, c.case_number FROM tb_cases c JOIN patients p ON p.id = c.patient_id WHERE c.id = ?");
            $pStmt->execute([$posted_case_id]);
            $patient = $pStmt->fetch();
            $patientName = $patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : "Case #{$posted_case_id}";

            $actionText = $dose_status === 'missed' ? 'marked as Missed' : 'marked as Medicine Given (' . ucfirst($dose_status) . ')';
            $successMsg = "Success: {$patientName} has been {$actionText} for " . date('M d, Y', strtotime($log_date)) . ".";
        } catch (Throwable $e) {
            $errorMsg = 'Failed to record medication status: ' . $e->getMessage();
        }
    }
}

$tbSummary = [
    'active_cases' => 0,
    'needs_medicine' => 0,
    'already_given' => 0,
    'missed_today' => 0,
    'total_cases' => 0,
    'cured_completed' => 0,
    'lab_tests' => 0,
];

$activeCases = [];

try {
    $tbSummary['active_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE status = 'active'")->fetchColumn();
    $tbSummary['total_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases")->fetchColumn();
    $tbSummary['lab_tests'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_lab_examinations")->fetchColumn();
    $tbSummary['cured_completed'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE treatment_outcome IN ('cured', 'treatment_completed')")->fetchColumn();

    // Query active TB cases joined with dose status on target date
    $query = "
        SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay, p.sex, p.birth_date,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status = 'missed') AS doses_missed,
               today_log.id AS today_log_id,
               today_log.status AS today_status,
               today_log.remarks AS today_remarks,
               today_log.created_at AS today_logged_at,
               u.full_name AS recorded_by_name
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        LEFT JOIN tb_dot_logs today_log ON today_log.tb_case_id = c.id AND today_log.log_date = :target_date
        LEFT JOIN users u ON u.id = today_log.recorded_by
        WHERE c.status = 'active'
    ";
    $params = ['target_date' => $targetDate];

    if ($search !== '') {
        $query .= " AND (c.case_number LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR p.barangay LIKE :search)";
        $params['search'] = "%{$search}%";
    }

    $query .= " ORDER BY 
        CASE 
            WHEN today_log.status IS NULL THEN 1 
            WHEN today_log.status = 'missed' THEN 2 
            ELSE 3 
        END, 
        p.last_name ASC, p.first_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $allActiveCases = $stmt->fetchAll();

    // Calculate daily status counts
    foreach ($allActiveCases as $ac) {
        if ($ac['today_status'] === 'taken' || $ac['today_status'] === 'supervised') {
            $tbSummary['already_given']++;
        } elseif ($ac['today_status'] === 'missed') {
            $tbSummary['missed_today']++;
            $tbSummary['needs_medicine']++;
        } else {
            $tbSummary['needs_medicine']++;
        }
    }

    // Filter list according to tab
    if ($statusFilter === 'needs_medicine') {
        $activeCases = array_filter($allActiveCases, function ($c) {
            return $c['today_status'] === null || $c['today_status'] === 'missed';
        });
    } elseif ($statusFilter === 'already_given') {
        $activeCases = array_filter($allActiveCases, function ($c) {
            return $c['today_status'] === 'taken' || $c['today_status'] === 'supervised';
        });
    } elseif ($statusFilter === 'missed') {
        $activeCases = array_filter($allActiveCases, function ($c) {
            return $c['today_status'] === 'missed';
        });
    } else {
        $activeCases = $allActiveCases;
    }
} catch (Throwable $e) {
    // Keep module usable
    $errorMsg = 'Error loading TB monitoring data: ' . $e->getMessage();
}
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-medium">National Tuberculosis Control Program</div>
      <div class="text-2xl font-bold text-slate-900">TB Monitoring & Medication Module</div>
      <p class="text-sm text-slate-500 mt-1">Monitor daily anti-TB drug dispensing, track DOTS adherence, and ensure zero missed doses.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip bg-teal-50 text-teal-700 border border-teal-200">
        <i class="fas fa-calendar-check mr-1 text-xs"></i> Daily DOTS Monitor
      </span>
      <a href="/HealthLogs/public/tb/cases/create.php" class="w-full sm:w-auto inline-flex items-center justify-center bg-slate-900 text-white px-4 py-2.5 rounded-lg shadow text-sm font-medium hover:bg-slate-800 transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i>Register New Case
      </a>
    </div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
      <i class="fas fa-check-circle text-emerald-600 text-base"></i>
      <span><?= h($successMsg) ?></span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-sm font-bold">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
  <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-rose-600 text-base"></i>
      <span><?= h($errorMsg) ?></span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-sm font-bold">&times;</button>
  </div>
<?php endif; ?>

<!-- Quick Navigation Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 hover:shadow-md transition" href="/HealthLogs/public/tb/cases/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center shadow-xs">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </span>
      <div>
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Registry</div>
        <div class="text-lg font-bold text-slate-900">TB Case Registry</div>
      </div>
    </div>
    <p class="text-xs text-slate-600 mt-3">Enrolled patient cases, regimens (Category 1, 2, MDR-TB), and outcomes.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 hover:shadow-md transition" href="/HealthLogs/public/tb/dots/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center shadow-xs">
        <i class="fas fa-pills text-xl"></i>
      </span>
      <div>
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Adherence Tracker</div>
        <div class="text-lg font-bold text-slate-900">Daily DOTS Intake</div>
      </div>
    </div>
    <p class="text-xs text-slate-600 mt-3">Full daily adherence tracking, supervised drug observation, and missed logs.</p>
  </a>

  <a class="bg-white p-5 rounded-xl shadow border border-slate-100 block hover:-translate-y-0.5 hover:shadow-md transition" href="/HealthLogs/public/tb/labs/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center shadow-xs">
        <i class="fas fa-microscope text-xl"></i>
      </span>
      <div>
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Diagnostic Monitoring</div>
        <div class="text-lg font-bold text-slate-900">Lab Examinations</div>
      </div>
    </div>
    <p class="text-xs text-slate-600 mt-3">Sputum Smear, GeneXpert, and Chest X-ray diagnostic results.</p>
  </a>
</div>

<!-- Primary Daily Medication Status Indicators -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <!-- Needs Medicine Card -->
  <a href="?date=<?= urlencode($targetDate) ?>&filter=needs_medicine" class="bg-white p-5 rounded-xl shadow border-l-4 border-amber-500 hover:shadow-md transition block group">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-wider font-bold text-amber-700">Needs Medicine Today</div>
      <span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-sm">
        <i class="fas fa-hourglass-half"></i>
      </span>
    </div>
    <div class="text-3xl font-extrabold text-slate-900 mt-2"><?= h(number_format($tbSummary['needs_medicine'])) ?></div>
    <div class="text-xs text-amber-700 font-medium mt-1 flex items-center gap-1">
      <i class="fas fa-exclamation-triangle text-xs"></i>
      Pending / Due for medicine today
    </div>
  </a>

  <!-- Already Given Medicine Card -->
  <a href="?date=<?= urlencode($targetDate) ?>&filter=already_given" class="bg-white p-5 rounded-xl shadow border-l-4 border-emerald-500 hover:shadow-md transition block group">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-wider font-bold text-emerald-700">Already Given Today</div>
      <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm">
        <i class="fas fa-check-double"></i>
      </span>
    </div>
    <div class="text-3xl font-extrabold text-slate-900 mt-2"><?= h(number_format($tbSummary['already_given'])) ?></div>
    <div class="text-xs text-emerald-700 font-medium mt-1 flex items-center gap-1">
      <i class="fas fa-check-circle text-xs"></i>
      Medication dispensed & logged
    </div>
  </a>

  <!-- Total Active Cases Card -->
  <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-wider font-bold text-blue-700">Active Cases</div>
      <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm">
        <i class="fas fa-user-injured"></i>
      </span>
    </div>
    <div class="text-3xl font-extrabold text-slate-900 mt-2"><?= h(number_format($tbSummary['active_cases'])) ?></div>
    <div class="text-xs text-slate-500 font-medium mt-1">Currently undergoing NTP regimen</div>
  </div>

  <!-- Cured / Treatment Completed Card -->
  <div class="bg-white p-5 rounded-xl shadow border-l-4 border-purple-500">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-wider font-bold text-purple-700">Cured / Completed</div>
      <span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm">
        <i class="fas fa-award"></i>
      </span>
    </div>
    <div class="text-3xl font-extrabold text-slate-900 mt-2"><?= h(number_format($tbSummary['cured_completed'])) ?></div>
    <div class="text-xs text-slate-500 font-medium mt-1">Successful recovery outcomes</div>
  </div>
</div>

<!-- Daily Medication Monitoring & Dispensing Section -->
<div class="bg-white p-4 sm:p-6 rounded-xl shadow mt-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800">
          <i class="fas fa-pills mr-1 text-xs"></i> Daily Monitoring
        </span>
        <span class="text-xs text-slate-500">Monitoring Date: <strong><?= date('F d, Y', strtotime($targetDate)) ?></strong></span>
      </div>
      <div class="text-xl font-bold text-slate-900 mt-1">Daily TB Medicine Dispensing & Compliance</div>
      <p class="text-xs text-slate-500">Quickly monitor who needs medicine today and record directly observed therapy.</p>
    </div>

    <!-- Date selector -->
    <form method="GET" class="flex flex-wrap items-center gap-2">
      <input type="hidden" name="filter" value="<?= h($statusFilter) ?>" />
      <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
        <label class="text-xs font-semibold text-slate-600 px-1">Date:</label>
        <input type="date" name="date" value="<?= h($targetDate) ?>" onchange="this.form.submit()" class="border-0 bg-transparent text-sm font-medium text-slate-800 focus:ring-0 p-0" />
      </div>
      <?php if ($targetDate !== date('Y-m-d')): ?>
        <a href="?date=<?= date('Y-m-d') ?>&filter=<?= h($statusFilter) ?>" class="text-xs font-semibold px-2.5 py-2 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 transition">Today</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Filter Tabs & Search Bar -->
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mt-4">
    <div class="flex flex-wrap items-center gap-2">
      <a href="?date=<?= urlencode($targetDate) ?>&filter=all&search=<?= urlencode($search) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
        All Active (<?= count($allActiveCases) ?>)
      </a>
      <a href="?date=<?= urlencode($targetDate) ?>&filter=needs_medicine&search=<?= urlencode($search) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'needs_medicine' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' ?>">
        <i class="fas fa-hourglass-half mr-1 text-xs"></i> Needs Medicine (<?= $tbSummary['needs_medicine'] ?>)
      </a>
      <a href="?date=<?= urlencode($targetDate) ?>&filter=already_given&search=<?= urlencode($search) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'already_given' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' ?>">
        <i class="fas fa-check-circle mr-1 text-xs"></i> Already Given (<?= $tbSummary['already_given'] ?>)
      </a>
      <?php if ($tbSummary['missed_today'] > 0): ?>
        <a href="?date=<?= urlencode($targetDate) ?>&filter=missed&search=<?= urlencode($search) ?>" class="px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'missed' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' ?>">
          <i class="fas fa-times-circle mr-1 text-xs"></i> Missed (<?= $tbSummary['missed_today'] ?>)
        </a>
      <?php endif; ?>
    </div>

    <!-- Search Form -->
    <form method="GET" class="flex items-center gap-2">
      <input type="hidden" name="date" value="<?= h($targetDate) ?>" />
      <input type="hidden" name="filter" value="<?= h($statusFilter) ?>" />
      <div class="relative w-full sm:w-64">
        <input name="search" value="<?= h($search) ?>" placeholder="Search name or case..." class="w-full text-xs pl-8 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-teal-500" />
        <i class="fas fa-search absolute left-2.5 top-2.5 text-xs text-slate-400"></i>
      </div>
      <?php if ($search !== ''): ?>
        <a href="?date=<?= urlencode($targetDate) ?>&filter=<?= h($statusFilter) ?>" class="text-xs text-slate-500 hover:text-slate-800 p-2">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Patients Adherence Table -->
  <?php if (empty($activeCases)): ?>
    <div class="text-center py-10 bg-slate-50 rounded-xl mt-4 border border-dashed border-slate-300">
      <i class="fas fa-clipboard-check text-3xl text-slate-400 mb-2"></i>
      <p class="text-sm font-semibold text-slate-700">No patient records match the current filter.</p>
      <p class="text-xs text-slate-500 mt-1">
        <?php if ($statusFilter === 'needs_medicine'): ?>
          Great job! All active TB patients have already received their medication for <?= date('M d, Y', strtotime($targetDate)) ?>.
        <?php else: ?>
          Try clearing search or filters to see all enrolled patients.
        <?php endif; ?>
      </p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-sm min-w-[760px]">
        <thead>
          <tr class="border-b bg-slate-50 text-slate-500 uppercase text-xs">
            <th class="py-3 px-3">Case No</th>
            <th class="py-3 px-3">Patient Name</th>
            <th class="py-3 px-3">Barangay</th>
            <th class="py-3 px-3">Regimen</th>
            <th class="py-3 px-3">Daily Status (<?= date('M d', strtotime($targetDate)) ?>)</th>
            <th class="py-3 px-3">Adherence Progress</th>
            <th class="py-3 px-3 text-right">Medicine Action</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php foreach ($activeCases as $c): 
            $isGiven = ($c['today_status'] === 'taken' || $c['today_status'] === 'supervised');
            $isMissed = ($c['today_status'] === 'missed');
            $needsMed = !$isGiven;
          ?>
            <tr class="hover:bg-slate-50/80 transition <?= $needsMed ? 'bg-amber-50/20' : '' ?>">
              <!-- Case No -->
              <td class="py-3 px-3 font-mono font-semibold text-slate-900 whitespace-nowrap">
                <?= h($c['case_number']) ?>
              </td>

              <!-- Patient Name -->
              <td class="py-3 px-3 font-medium text-slate-900 whitespace-nowrap">
                <div><?= h($c['last_name'] . ', ' . $c['first_name']) ?></div>
                <div class="text-xs text-slate-400"><?= h($c['contact_no'] ?: 'No contact number') ?></div>
              </td>

              <!-- Barangay -->
              <td class="py-3 px-3 whitespace-nowrap text-xs text-slate-600">
                <?= h($c['barangay']) ?>
              </td>

              <!-- Category -->
              <td class="py-3 px-3 whitespace-nowrap text-xs">
                <span class="font-semibold uppercase text-slate-700"><?= h(str_replace('_', ' ', $c['treatment_category'])) ?></span>
                <div class="text-[11px] text-slate-400 capitalize"><?= h(str_replace('_', ' ', $c['tb_type'])) ?></div>
              </td>

              <!-- Daily Status -->
              <td class="py-3 px-3 whitespace-nowrap">
                <?php if ($isGiven): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <?= $c['today_status'] === 'supervised' ? 'Supervised (Given)' : 'Taken' ?>
                  </span>
                  <?php if (!empty($c['recorded_by_name'])): ?>
                    <div class="text-[10px] text-slate-400 mt-0.5">By: <?= h($c['recorded_by_name']) ?></div>
                  <?php endif; ?>
                <?php elseif ($isMissed): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    <i class="fas fa-exclamation-triangle text-rose-600"></i>
                    Missed Dose
                  </span>
                  <div class="text-[10px] text-rose-600 font-medium mt-0.5">Needs Follow-up</div>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                    <i class="fas fa-clock text-amber-600"></i>
                    Needs Medicine
                  </span>
                  <div class="text-[10px] text-amber-700 font-medium mt-0.5">Due for intake today</div>
                <?php endif; ?>
              </td>

              <!-- Adherence Stats -->
              <td class="py-3 px-3 whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <span class="font-bold text-teal-700 text-xs"><?= (int)$c['doses_taken'] ?> doses</span>
                  <?php if ((int)$c['doses_missed'] > 0): ?>
                    <span class="text-xs text-rose-500 font-medium">(<?= (int)$c['doses_missed'] ?> missed)</span>
                  <?php endif; ?>
                </div>
                <div class="text-[10px] text-slate-400">Started <?= h($c['treatment_start_date']) ?></div>
              </td>

              <!-- Actions / 1-Click Quick Dispense -->
              <td class="py-3 px-3 text-right whitespace-nowrap">
                <div class="inline-flex items-center justify-end gap-1.5">
                  <?php if ($needsMed): ?>
                    <!-- Quick 1-Click Dispense Form (Supervised) -->
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="quick_log_dose" />
                      <input type="hidden" name="tb_case_id" value="<?= (int)$c['id'] ?>" />
                      <input type="hidden" name="log_date" value="<?= h($targetDate) ?>" />
                      <input type="hidden" name="status" value="supervised" />
                      <input type="hidden" name="remarks" value="Directly Observed Therapy (Supervised at Health Center)" />
                      <button type="submit" class="inline-flex items-center gap-1 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition" title="Mark dose as Given & Supervised today">
                        <i class="fas fa-pills text-xs"></i>
                        <span>Give Medicine</span>
                      </button>
                    </form>

                    <!-- Quick Mark Missed Form -->
                    <form method="POST" class="inline">
                      <input type="hidden" name="action" value="quick_log_dose" />
                      <input type="hidden" name="tb_case_id" value="<?= (int)$c['id'] ?>" />
                      <input type="hidden" name="log_date" value="<?= h($targetDate) ?>" />
                      <input type="hidden" name="status" value="missed" />
                      <input type="hidden" name="remarks" value="Patient missed daily dose" />
                      <button type="submit" onclick="return confirm('Mark this dose as missed for <?= h($c['first_name']) ?>?');" class="inline-flex items-center bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold px-2 py-1.5 rounded-lg transition" title="Mark dose as Missed">
                        <i class="fas fa-times text-xs"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <!-- Already Given Badge / Edit option -->
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-700 px-2 py-1 bg-emerald-50 rounded-lg">
                      <i class="fas fa-check mr-1 text-xs"></i> Given
                    </span>
                  <?php endif; ?>

                  <!-- History Link -->
                  <a href="/HealthLogs/public/tb/dots/index.php?case_id=<?= $c['id'] ?>" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg font-medium transition" title="View Full DOTS History">
                    <i class="fas fa-history mr-1 text-xs"></i>Log
                  </a>

                  <!-- Remind link if patient has phone -->
                  <?php if (!empty($c['contact_no'])): ?>
                    <a href="/HealthLogs/public/reminders/form.php?patient_id=<?= $c['patient_id'] ?>&type=tb_monitoring" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-2 py-1.5 rounded-lg font-medium transition" title="Send SMS Reminder">
                      <i class="fas fa-sms text-xs"></i>
                    </a>
                  <?php endif; ?>
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
