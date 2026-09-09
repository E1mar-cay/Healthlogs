<?php
$pageTitle = 'Daily DOTS Tracker';
require __DIR__ . '/../../partials/header.php';

$case_id = (int)($_GET['case_id'] ?? 0);
$targetDate = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    $targetDate = date('Y-m-d');
}

$statusFilter = trim($_GET['filter'] ?? 'all'); // 'all', 'needs_medicine', 'already_given', 'missed'
$errors = [];
$successMsg = '';

// Handle form submission to log a DOTS intake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_case_id = (int)($_POST['tb_case_id'] ?? 0);
    $log_date = trim($_POST['log_date'] ?? $targetDate);
    $status = trim($_POST['status'] ?? 'supervised');
    $remarks = trim($_POST['remarks'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if ($posted_case_id <= 0) {
        $errors[] = 'Please select a TB case.';
    }
    if (empty($log_date)) {
        $errors[] = 'Date is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_dot_logs (tb_case_id, log_date, status, remarks, recorded_by)
                VALUES (:tb_case_id, :log_date, :status, :remarks, :recorded_by)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by)
            ");
            $stmt->execute([
                'tb_case_id' => $posted_case_id,
                'log_date' => $log_date,
                'status' => in_array($status, ['taken', 'supervised', 'missed'], true) ? $status : 'supervised',
                'remarks' => $remarks !== '' ? $remarks : ($status === 'supervised' ? 'Supervised daily dose' : null),
                'recorded_by' => $recorded_by,
            ]);

            // Fetch patient name
            $pStmt = $pdo->prepare("SELECT p.first_name, p.last_name, c.case_number FROM tb_cases c JOIN patients p ON p.id = c.patient_id WHERE c.id = ?");
            $pStmt->execute([$posted_case_id]);
            $patient = $pStmt->fetch();
            $pName = $patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : "Case #{$posted_case_id}";

            $statusLabel = $status === 'missed' ? 'Missed Dose' : ($status === 'supervised' ? 'Medicine Given (Supervised)' : 'Medicine Given (Self-Administered)');
            $successMsg = "Successfully recorded {$statusLabel} for {$pName} on " . date('M d, Y', strtotime($log_date)) . "!";
            $case_id = $posted_case_id;
        } catch (Throwable $e) {
            $errors[] = 'Failed to log DOTS intake: ' . $e->getMessage();
        }
    }
}

// Fetch all active TB cases and their status for the target date
$activeCases = [];
$dailyStats = [
    'total' => 0,
    'needs_medicine' => 0,
    'already_given' => 0,
    'missed' => 0,
];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.first_name, p.last_name, p.barangay, p.contact_no, p.sex, p.birth_date,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status = 'missed') AS doses_missed,
               l.status AS day_status,
               l.remarks AS day_remarks,
               l.created_at AS day_logged_at,
               u.full_name AS day_recorder_name
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        LEFT JOIN tb_dot_logs l ON l.tb_case_id = c.id AND l.log_date = :target_date
        LEFT JOIN users u ON u.id = l.recorded_by
        WHERE c.status = 'active'
        ORDER BY 
            CASE 
                WHEN l.status IS NULL THEN 1 
                WHEN l.status = 'missed' THEN 2 
                ELSE 3 
            END,
            p.last_name ASC, p.first_name ASC
    ");
    $stmt->execute(['target_date' => $targetDate]);
    $allCasesForDay = $stmt->fetchAll();

    $dailyStats['total'] = count($allCasesForDay);
    foreach ($allCasesForDay as $row) {
        if ($row['day_status'] === 'taken' || $row['day_status'] === 'supervised') {
            $dailyStats['already_given']++;
        } elseif ($row['day_status'] === 'missed') {
            $dailyStats['missed']++;
            $dailyStats['needs_medicine']++;
        } else {
            $dailyStats['needs_medicine']++;
        }
    }

    // Filter table list
    if ($statusFilter === 'needs_medicine') {
        $activeCases = array_filter($allCasesForDay, function ($c) {
            return $c['day_status'] === null || $c['day_status'] === 'missed';
        });
    } elseif ($statusFilter === 'already_given') {
        $activeCases = array_filter($allCasesForDay, function ($c) {
            return $c['day_status'] === 'taken' || $c['day_status'] === 'supervised';
        });
    } elseif ($statusFilter === 'missed') {
        $activeCases = array_filter($allCasesForDay, function ($c) {
            return $c['day_status'] === 'missed';
        });
    } else {
        $activeCases = $allCasesForDay;
    }

    if ($case_id <= 0 && !empty($allCasesForDay)) {
        $case_id = (int)$allCasesForDay[0]['id'];
    }
} catch (Throwable $e) {}

// Selected case details & full history
$selectedCase = null;
$dotLogs = [];
$totalTaken = 0;
$totalMissed = 0;

if ($case_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, p.first_name, p.last_name, p.barangay, p.contact_no, p.sex, p.birth_date
            FROM tb_cases c
            JOIN patients p ON p.id = c.patient_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $case_id]);
        $selectedCase = $stmt->fetch();

        if ($selectedCase) {
            $stmtLogs = $pdo->prepare("
                SELECT d.*, u.full_name AS recorder_name
                FROM tb_dot_logs d
                LEFT JOIN users u ON u.id = d.recorded_by
                WHERE d.tb_case_id = :case_id
                ORDER BY d.log_date DESC
            ");
            $stmtLogs->execute(['case_id' => $case_id]);
            $dotLogs = $stmtLogs->fetchAll();

            foreach ($dotLogs as $l) {
                if ($l['status'] === 'taken' || $l['status'] === 'supervised') {
                    $totalTaken++;
                } else if ($l['status'] === 'missed') {
                    $totalMissed++;
                }
            }
        }
    } catch (Throwable $e) {}
}
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-medium">National Tuberculosis Control Program</div>
      <div class="text-2xl font-bold text-slate-900">Daily DOTS Adherence & Medicine Tracker</div>
      <p class="text-sm text-slate-500 mt-1">Directly Observed Therapy Short-Course monitoring — track daily medication intake and identify patients needing medicine.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/tb.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900 underline">
        <i class="fas fa-arrow-left mr-1"></i> Back to TB Module
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
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold text-base">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-sm space-y-1 shadow-sm">
    <?php foreach ($errors as $err): ?>
      <p class="flex items-center gap-2"><i class="fas fa-exclamation-circle text-rose-600"></i> <?= h($err) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Daily Monitoring Section: Who Needs Medicine vs Already Given -->
<div class="bg-white rounded-xl shadow p-4 sm:p-6 mt-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
        <i class="fas fa-calendar-day"></i>
        <span>Daily Adherence Monitor</span>
      </div>
      <div class="text-xl font-bold text-slate-900 mt-1">
        Medication Status for <?= date('l, F d, Y', strtotime($targetDate)) ?>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">Quickly administer or update daily medication logs for enrolled TB patients.</p>
    </div>

    <!-- Date Navigation -->
    <form method="GET" class="flex flex-wrap items-center gap-2">
      <input type="hidden" name="case_id" value="<?= (int)$case_id ?>" />
      <input type="hidden" name="filter" value="<?= h($statusFilter) ?>" />
      <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
        <label class="text-xs font-semibold text-slate-600 px-1">Date:</label>
        <input type="date" name="date" value="<?= h($targetDate) ?>" onchange="this.form.submit()" class="border-0 bg-transparent text-sm font-medium text-slate-800 focus:ring-0 p-0" />
      </div>
      <?php if ($targetDate !== date('Y-m-d')): ?>
        <a href="?date=<?= date('Y-m-d') ?>&case_id=<?= (int)$case_id ?>&filter=<?= h($statusFilter) ?>" class="text-xs font-semibold px-2.5 py-2 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 transition">Today</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Summary counters for selected date -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=needs_medicine" class="p-3.5 rounded-xl border <?= $statusFilter === 'needs_medicine' ? 'bg-amber-100 border-amber-400' : 'bg-amber-50/60 border-amber-200 hover:bg-amber-100/70' ?> flex items-center justify-between transition">
      <div>
        <div class="text-xs font-bold text-amber-800 uppercase tracking-wider">Needs Medicine Today</div>
        <div class="text-2xl font-extrabold text-amber-900 mt-1"><?= $dailyStats['needs_medicine'] ?></div>
      </div>
      <span class="w-10 h-10 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-base">
        <i class="fas fa-hourglass-half"></i>
      </span>
    </a>

    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=already_given" class="p-3.5 rounded-xl border <?= $statusFilter === 'already_given' ? 'bg-emerald-100 border-emerald-400' : 'bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100/70' ?> flex items-center justify-between transition">
      <div>
        <div class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Already Given Medicine</div>
        <div class="text-2xl font-extrabold text-emerald-900 mt-1"><?= $dailyStats['already_given'] ?></div>
      </div>
      <span class="w-10 h-10 rounded-full bg-emerald-200 text-emerald-800 flex items-center justify-center text-base">
        <i class="fas fa-check-circle"></i>
      </span>
    </a>

    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=all" class="p-3.5 rounded-xl border <?= $statusFilter === 'all' ? 'bg-blue-100 border-blue-400' : 'bg-blue-50/60 border-blue-200 hover:bg-blue-100/70' ?> flex items-center justify-between transition">
      <div>
        <div class="text-xs font-bold text-blue-800 uppercase tracking-wider">Total Active Enrolled</div>
        <div class="text-2xl font-extrabold text-blue-900 mt-1"><?= $dailyStats['total'] ?></div>
      </div>
      <span class="w-10 h-10 rounded-full bg-blue-200 text-blue-800 flex items-center justify-center text-base">
        <i class="fas fa-users"></i>
      </span>
    </a>
  </div>

  <!-- Filter tabs -->
  <div class="flex flex-wrap items-center gap-2 mt-4 pt-3 border-t">
    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=all" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
      All Patients (<?= $dailyStats['total'] ?>)
    </a>
    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=needs_medicine" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'needs_medicine' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' ?>">
      <i class="fas fa-hourglass-half mr-1"></i> Needs Medicine (<?= $dailyStats['needs_medicine'] ?>)
    </a>
    <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=already_given" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'already_given' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' ?>">
      <i class="fas fa-check-circle mr-1"></i> Already Given (<?= $dailyStats['already_given'] ?>)
    </a>
    <?php if ($dailyStats['missed'] > 0): ?>
      <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$case_id ?>&filter=missed" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'missed' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' ?>">
        <i class="fas fa-times-circle mr-1"></i> Missed (<?= $dailyStats['missed'] ?>)
      </a>
    <?php endif; ?>
  </div>

  <!-- Daily Table -->
  <div class="overflow-x-auto mt-3 -mx-4 sm:mx-0 px-4 sm:px-0">
    <table class="w-full text-left text-sm min-w-[720px]">
      <thead>
        <tr class="border-b bg-slate-50 text-slate-500 uppercase text-xs">
          <th class="py-2.5 px-3">Patient & Case</th>
          <th class="py-2.5 px-3">Barangay</th>
          <th class="py-2.5 px-3">Status for <?= date('M d', strtotime($targetDate)) ?></th>
          <th class="py-2.5 px-3">Overall Progress</th>
          <th class="py-2.5 px-3 text-right">Quick Action</th>
        </tr>
      </thead>
      <tbody class="divide-y text-slate-700">
        <?php if (empty($activeCases)): ?>
          <tr>
            <td colspan="5" class="py-6 text-center text-xs text-slate-500">
              No patients found under this filter for <?= date('M d, Y', strtotime($targetDate)) ?>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($activeCases as $ac): 
            $isGiven = ($ac['day_status'] === 'taken' || $ac['day_status'] === 'supervised');
            $isMissed = ($ac['day_status'] === 'missed');
            $isSelected = ((int)$ac['id'] === $case_id);
          ?>
            <tr class="hover:bg-slate-50 transition <?= $isSelected ? 'bg-teal-50/40 font-medium' : '' ?>">
              <td class="py-3 px-3 whitespace-nowrap">
                <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$ac['id'] ?>&filter=<?= h($statusFilter) ?>" class="text-slate-900 hover:text-teal-700 font-semibold block">
                  <?= h($ac['last_name'] . ', ' . $ac['first_name']) ?>
                </a>
                <span class="font-mono text-xs text-slate-500"><?= h($ac['case_number']) ?></span>
              </td>

              <td class="py-3 px-3 whitespace-nowrap text-xs text-slate-600">
                <?= h($ac['barangay']) ?>
              </td>

              <td class="py-3 px-3 whitespace-nowrap">
                <?php if ($isGiven): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <?= $ac['day_status'] === 'supervised' ? 'Given (Supervised)' : 'Given (Taken)' ?>
                  </span>
                  <?php if (!empty($ac['day_recorder_name'])): ?>
                    <span class="text-[10px] text-slate-400 block mt-0.5">By <?= h($ac['day_recorder_name']) ?></span>
                  <?php endif; ?>
                <?php elseif ($isMissed): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    <i class="fas fa-exclamation-triangle text-rose-600"></i>
                    Missed Dose
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                    <i class="fas fa-clock text-amber-600"></i>
                    Needs Medicine
                  </span>
                <?php endif; ?>
              </td>

              <td class="py-3 px-3 whitespace-nowrap text-xs">
                <span class="font-bold text-teal-700"><?= (int)$ac['doses_taken'] ?> doses</span>
                <?php if ((int)$ac['doses_missed'] > 0): ?>
                  <span class="text-rose-500 font-medium">(<?= (int)$ac['doses_missed'] ?> missed)</span>
                <?php endif; ?>
              </td>

              <td class="py-3 px-3 text-right whitespace-nowrap">
                <div class="inline-flex items-center justify-end gap-1.5">
                  <?php if (!$isGiven): ?>
                    <!-- Quick Supervise / Dispense -->
                    <form method="POST" class="inline">
                      <input type="hidden" name="tb_case_id" value="<?= (int)$ac['id'] ?>" />
                      <input type="hidden" name="log_date" value="<?= h($targetDate) ?>" />
                      <input type="hidden" name="status" value="supervised" />
                      <input type="hidden" name="remarks" value="Directly Observed Therapy" />
                      <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-2.5 py-1 rounded-lg transition" title="Mark Given (Supervised)">
                        <i class="fas fa-pills mr-1 text-xs"></i> Give Medicine
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">
                      &check; Given
                    </span>
                  <?php endif; ?>

                  <!-- Switch selection to view history -->
                  <a href="?date=<?= urlencode($targetDate) ?>&case_id=<?= (int)$ac['id'] ?>&filter=<?= h($statusFilter) ?>" class="text-xs <?= $isSelected ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?> px-2.5 py-1 rounded-lg font-medium transition">
                    <?= $isSelected ? 'Viewing' : 'View History' ?>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Detailed Patient Dossier & Full Logs -->
<?php if ($selectedCase): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
    <div class="bg-white p-4 sm:p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Selected Patient Dossier</div>
      <div class="text-xl font-bold text-slate-900 mt-1"><?= h($selectedCase['last_name'] . ', ' . $selectedCase['first_name']) ?></div>
      <div class="text-sm text-slate-600 mt-1">
        Case No: <span class="font-mono font-medium text-slate-900"><?= h($selectedCase['case_number']) ?></span> &bull; Brgy. <?= h($selectedCase['barangay']) ?>
      </div>
      <div class="text-xs text-slate-400 mt-2">
        Treatment Category: <strong class="uppercase text-slate-700"><?= h(str_replace('_', ' ', $selectedCase['treatment_category'])) ?></strong> &bull; Started: <?= h($selectedCase['treatment_start_date']) ?>
      </div>
    </div>

    <div class="bg-white p-4 sm:p-5 rounded-xl shadow border border-slate-100 flex items-center justify-around text-center">
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Total Doses Taken</div>
        <div class="text-2xl font-extrabold text-teal-600 mt-1"><?= $totalTaken ?></div>
        <div class="text-[11px] text-slate-400 mt-0.5">Supervised / Taken</div>
      </div>
      <div class="w-px h-10 bg-slate-200"></div>
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Doses Missed</div>
        <div class="text-2xl font-extrabold text-rose-600 mt-1"><?= $totalMissed ?></div>
        <div class="text-[11px] text-slate-400 mt-0.5">Untaken days</div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Log Form -->
    <div class="lg:col-span-1 bg-white p-4 sm:p-5 rounded-xl shadow h-fit border border-slate-100">
      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Record Specific Entry</div>
      <div class="text-lg font-bold text-slate-900 mb-4">Log Medication Intake</div>

      <form method="POST" class="space-y-3">
        <input type="hidden" name="tb_case_id" value="<?= $selectedCase['id'] ?>" />

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Intake Date</label>
          <input type="date" name="log_date" value="<?= h($targetDate) ?>" max="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500" />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Dose Status</label>
          <select name="status" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
            <option value="supervised">Supervised (Directly Observed by BHW/Nurse)</option>
            <option value="taken">Self-Administered / Taken by Patient</option>
            <option value="missed">Missed / Untaken</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Remarks / Clinical Notes</label>
          <textarea name="remarks" rows="2" placeholder="e.g. Completed morning dose, patient tolerated well..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500"></textarea>
        </div>

        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2.5 rounded-lg text-sm font-semibold transition shadow-xs">
          <i class="fas fa-save mr-1.5 text-xs"></i> Save Intake Log
        </button>
      </form>
    </div>

    <!-- History Table -->
    <div class="lg:col-span-2 bg-white p-4 sm:p-5 rounded-xl shadow border border-slate-100">
      <div class="flex items-center justify-between border-b pb-3">
        <div>
          <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Complete Adherence History</div>
          <div class="text-lg font-bold text-slate-900">Patient Dose Logs (<?= count($dotLogs) ?>)</div>
        </div>
      </div>

      <?php if (empty($dotLogs)): ?>
        <div class="text-sm text-slate-500 mt-4 text-center py-6">No medication logs recorded yet for this patient.</div>
      <?php else: ?>
        <div class="overflow-x-auto mt-3 -mx-4 sm:mx-0 px-4 sm:px-0 max-h-[420px] overflow-y-auto">
          <table class="w-full text-left text-sm min-w-[500px]">
            <thead class="sticky top-0 bg-white">
              <tr class="border-b text-slate-500 uppercase text-xs">
                <th class="py-2.5 px-3">Date</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 px-3">Remarks</th>
                <th class="py-2.5 px-3">Recorded By</th>
              </tr>
            </thead>
            <tbody class="divide-y text-slate-700">
              <?php foreach ($dotLogs as $l): ?>
                <tr>
                  <td class="py-2.5 px-3 font-semibold text-slate-900 whitespace-nowrap">
                    <?= date('M d, Y', strtotime($l['log_date'])) ?>
                  </td>
                  <td class="py-2.5 px-3 whitespace-nowrap">
                    <?php if ($l['status'] === 'supervised'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Supervised</span>
                    <?php elseif ($l['status'] === 'taken'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Taken</span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Missed</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-2.5 px-3 text-xs text-slate-600"><?= h($l['remarks'] ?? '—') ?></td>
                  <td class="py-2.5 px-3 text-xs text-slate-500 whitespace-nowrap"><?= h($l['recorder_name'] ?? 'System') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="mt-6 bg-white p-8 rounded-xl shadow text-center text-slate-500">
    <p class="text-sm">No active TB cases available.</p>
    <a href="/HealthLogs/public/tb/cases/create.php" class="mt-2 inline-block text-sm font-medium text-slate-900 underline">Register a new case &rarr;</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
