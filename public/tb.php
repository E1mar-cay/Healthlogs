<?php
$pageTitle = 'TB Monitoring & DOTS Adherence';
require __DIR__ . '/partials/header.php';

$successMsg = '';
$errorMsg = '';

// Handle quick DOTS log from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_dot_log') {
    $case_id = (int)($_POST['case_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'supervised');
    $remarks = trim($_POST['remarks'] ?? 'Daily dose administered at health center');
    $recorded_by = $_SESSION['user_id'] ?? null;
    $log_date = date('Y-m-d');

    if ($case_id > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_dot_logs (tb_case_id, log_date, status, remarks, recorded_by)
                VALUES (:tb_case_id, :log_date, :status, :remarks, :recorded_by)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by)
            ");
            $stmt->execute([
                'tb_case_id' => $case_id,
                'log_date' => $log_date,
                'status' => $status,
                'remarks' => $remarks !== '' ? $remarks : null,
                'recorded_by' => $recorded_by,
            ]);
            $successMsg = 'Medication intake successfully recorded for today!';
        } catch (Throwable $e) {
            $errorMsg = 'Failed to record dose: ' . $e->getMessage();
        }
    }
}

$tbSummary = [
    'active_cases' => 0,
    'pending_today' => 0,
    'given_today' => 0,
    'cured_completed' => 0,
    'total_cases' => 0,
    'lab_tests' => 0,
];

$pendingTodayCases = [];
$givenTodayCases = [];
$completedTreatmentCases = [];

try {
    $tbSummary['active_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE status = 'active'")->fetchColumn();
    $tbSummary['total_cases'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases")->fetchColumn();
    $tbSummary['lab_tests'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_lab_examinations")->fetchColumn();
    $tbSummary['cured_completed'] = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE status = 'completed' OR treatment_outcome IN ('cured', 'treatment_completed')")->fetchColumn();

    // 1. Pending medication today (Active cases with NO dose logged today)
    $pendingTodayCases = $pdo->query("
        SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay, p.sex, p.birth_date,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken,
               (SELECT d.status FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.log_date = CURRENT_DATE() LIMIT 1) AS today_status,
               DATEDIFF(CURRENT_DATE(), c.treatment_start_date) AS days_on_treatment
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        WHERE c.status = 'active'
          AND NOT EXISTS (
              SELECT 1 FROM tb_dot_logs d
              WHERE d.tb_case_id = c.id
                AND d.log_date = CURRENT_DATE()
                AND d.status IN ('taken', 'supervised')
          )
        ORDER BY p.last_name ASC, p.first_name ASC
    ")->fetchAll();
    $tbSummary['pending_today'] = count($pendingTodayCases);

    // 2. Completed medication today (Active cases with dose taken/supervised today)
    $givenTodayCases = $pdo->query("
        SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken,
               today_log.status AS today_status,
               today_log.remarks AS today_remarks,
               today_log.created_at AS log_time,
               u.full_name AS recorder_name
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        JOIN tb_dot_logs today_log ON today_log.tb_case_id = c.id AND today_log.log_date = CURRENT_DATE() AND today_log.status IN ('taken', 'supervised')
        LEFT JOIN users u ON u.id = today_log.recorded_by
        WHERE c.status = 'active'
        ORDER BY today_log.created_at DESC
    ")->fetchAll();
    $tbSummary['given_today'] = count($givenTodayCases);

    // 3. Completed Full Treatment / Cured
    $completedTreatmentCases = $pdo->query("
        SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay, p.sex,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        WHERE c.status = 'completed' OR c.treatment_outcome IN ('cured', 'treatment_completed')
        ORDER BY c.treatment_outcome_date DESC, c.updated_at DESC
        LIMIT 20
    ")->fetchAll();
} catch (Throwable $e) {
    // Keep module page usable even if summary queries fail
}

$activeTab = $_GET['tab'] ?? 'pending';
if (!in_array($activeTab, ['pending', 'given', 'completed', 'registry'])) {
    $activeTab = 'pending';
}
?>

<!-- Header Banner -->
<div class="bg-white/90 backdrop-blur-md p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-semibold uppercase tracking-wider text-teal-700 flex items-center gap-1.5 mb-1">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        National TB Control Program (NTP) • Directly Observed Therapy
      </div>
      <div class="text-2xl sm:text-3xl font-bold text-slate-900 brand-font">TB Monitoring & Medication Tracker</div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        Monitor patients due for today's medicine intake, track completed daily doses, and review cured/completed cases.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/tb/cases/create.php" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl shadow-xs text-xs sm:text-sm font-semibold transition">
        <i class="fas fa-plus text-xs"></i> Register New TB Case
      </a>
      <a href="/HealthLogs/public/tb/dots/index.php" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl shadow-xs text-xs sm:text-sm font-semibold transition">
        <i class="fas fa-calendar-check text-xs"></i> Daily DOTS Calendar
      </a>
    </div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm flex items-center gap-2 shadow-xs">
    <i class="fas fa-check-circle text-emerald-600 text-base"></i>
    <span><?= h($successMsg) ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
  <div class="mb-6 bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm flex items-center gap-2 shadow-xs">
    <i class="fas fa-exclamation-circle text-red-600 text-base"></i>
    <span><?= h($errorMsg) ?></span>
  </div>
<?php endif; ?>

<!-- 4 Key Monitoring Metric Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  
  <!-- 1. Pending Medication Today -->
  <a href="?tab=pending" class="p-5 rounded-2xl border transition-all duration-200 block <?= $activeTab === 'pending' ? 'bg-amber-50/90 border-amber-300 ring-2 ring-amber-400/20 shadow-md' : 'bg-white/95 border-slate-200/80 shadow-xs hover:shadow-md' ?>">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-xs font-bold uppercase tracking-wider text-amber-700">Pending Medication Today</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-amber-900 mt-1 brand-font"><?= number_format($tbSummary['pending_today']) ?></div>
        <div class="text-xs text-amber-600 font-medium mt-0.5"><i class="fas fa-clock mr-1"></i>Due for today's dose</div>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
        <i class="fas fa-pills"></i>
      </div>
    </div>
  </a>

  <!-- 2. Medication Given Today -->
  <a href="?tab=given" class="p-5 rounded-2xl border transition-all duration-200 block <?= $activeTab === 'given' ? 'bg-emerald-50/90 border-emerald-300 ring-2 ring-emerald-400/20 shadow-md' : 'bg-white/95 border-slate-200/80 shadow-xs hover:shadow-md' ?>">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700">Medication Completed Today</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-emerald-900 mt-1 brand-font"><?= number_format($tbSummary['given_today']) ?></div>
        <div class="text-xs text-emerald-600 font-medium mt-0.5"><i class="fas fa-check-circle mr-1"></i>Doses logged today</div>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
        <i class="fas fa-calendar-check"></i>
      </div>
    </div>
  </a>

  <!-- 3. Active Enrolled Cases -->
  <div class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Active on Treatment</div>
      <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 brand-font"><?= number_format($tbSummary['active_cases']) ?></div>
      <div class="text-xs text-slate-500 mt-0.5">Active enrolled TB cases</div>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100 text-sky-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
      <i class="fas fa-lungs"></i>
    </div>
  </div>

  <!-- 4. Treatment Completed / Cured -->
  <a href="?tab=completed" class="p-5 rounded-2xl border transition-all duration-200 block <?= $activeTab === 'completed' ? 'bg-purple-50/90 border-purple-300 ring-2 ring-purple-400/20 shadow-md' : 'bg-white/95 border-slate-200/80 shadow-xs hover:shadow-md' ?>">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-xs font-bold uppercase tracking-wider text-purple-700">Treatment Completed / Cured</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-purple-900 mt-1 brand-font"><?= number_format($tbSummary['cured_completed']) ?></div>
        <div class="text-xs text-purple-600 font-medium mt-0.5"><i class="fas fa-medal mr-1"></i>Cured / Completed</div>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
        <i class="fas fa-shield-heart"></i>
      </div>
    </div>
  </a>
</div>

<!-- Navigation Tabs for Medication Monitoring -->
<div class="bg-white/95 backdrop-blur-xs rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
  <div class="border-b border-slate-200 px-4 sm:px-6 pt-3 flex flex-wrap gap-2 sm:gap-4">
    
    <!-- Tab 1: Pending Today -->
    <a href="?tab=pending" class="inline-flex items-center gap-2 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition-all <?= $activeTab === 'pending' ? 'border-amber-500 text-amber-700 bg-amber-50/50 rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
      <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
      <span>Pending Medication (Due Today)</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-extrabold <?= $activeTab === 'pending' ? 'bg-amber-200 text-amber-900' : 'bg-slate-100 text-slate-600' ?>">
        <?= count($pendingTodayCases) ?>
      </span>
    </a>

    <!-- Tab 2: Given Today -->
    <a href="?tab=given" class="inline-flex items-center gap-2 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition-all <?= $activeTab === 'given' ? 'border-emerald-500 text-emerald-700 bg-emerald-50/50 rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
      <i class="fas fa-check-double text-emerald-600"></i>
      <span>Completed Medication Today</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-extrabold <?= $activeTab === 'given' ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-100 text-slate-600' ?>">
        <?= count($givenTodayCases) ?>
      </span>
    </a>

    <!-- Tab 3: Completed Full Regimen -->
    <a href="?tab=completed" class="inline-flex items-center gap-2 py-3 px-3 sm:px-4 text-xs sm:text-sm font-bold border-b-2 transition-all <?= $activeTab === 'completed' ? 'border-purple-500 text-purple-700 bg-purple-50/50 rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
      <i class="fas fa-medal text-purple-600"></i>
      <span>Full Treatment Completed (Cured)</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-extrabold <?= $activeTab === 'completed' ? 'bg-purple-200 text-purple-900' : 'bg-slate-100 text-slate-600' ?>">
        <?= count($completedTreatmentCases) ?>
      </span>
    </a>
  </div>

  <div class="p-4 sm:p-6">
    
    <!-- ==================== TAB 1: PENDING MEDICATION TODAY ==================== -->
    <?php if ($activeTab === 'pending'): ?>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
            <i class="fas fa-pills text-amber-500"></i>
            Patients Pending Medication Today (<?= date('M d, Y') ?>)
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Active patients whose anti-TB medication has not yet been administered or supervised today.</p>
        </div>
      </div>

      <?php if (empty($pendingTodayCases)): ?>
        <div class="p-8 text-center bg-emerald-50/60 border border-emerald-200/80 rounded-2xl">
          <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-xl mb-2">
            <i class="fas fa-check"></i>
          </div>
          <div class="text-base font-bold text-emerald-900">All active patients have received their medication for today!</div>
          <p class="text-xs text-emerald-700 mt-1">All registered active TB patients have taken their medication today. Great job!</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
          <table class="w-full text-left text-sm min-w-[760px]">
            <thead>
              <tr class="bg-amber-50/80 border-y border-amber-200 text-amber-900 uppercase text-[11px] font-bold tracking-wider">
                <th class="py-3 px-3.5">Patient Name</th>
                <th class="py-3 px-3">Case No</th>
                <th class="py-3 px-3">Barangay & Contact</th>
                <th class="py-3 px-3">Regimen Category</th>
                <th class="py-3 px-3">Start Date / Phase</th>
                <th class="py-3 px-3">Doses Taken</th>
                <th class="py-3 px-3 text-right">Action / Give Meds</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php foreach ($pendingTodayCases as $c): ?>
                <?php 
                  $days = (int)($c['days_on_treatment'] ?? 0);
                  $phase = ($days <= 60) ? 'Intensive Phase (Mo. 1-2)' : 'Continuation Phase';
                  $estimatedTarget = ($c['treatment_category'] === 'category_2') ? 240 : 180;
                  $pct = min(100, round(((int)$c['doses_taken'] / max(1, $estimatedTarget)) * 100));
                ?>
                <tr class="hover:bg-amber-50/30 transition">
                  <td class="py-3.5 px-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-900"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></div>
                    <div class="text-[11px] text-slate-400 capitalize"><?= h($c['sex'] ?? 'Unknown') ?><?= !empty($c['birth_date']) ? ' • Age ' . (date_diff(date_create($c['birth_date']), date_create('today'))->y) : '' ?></div>
                  </td>
                  <td class="py-3.5 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                    <span class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs"><?= h($c['case_number']) ?></span>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <div class="font-medium text-slate-800">Brgy. <?= h($c['barangay']) ?></div>
                    <div class="text-xs text-slate-400"><i class="fas fa-phone text-[10px] mr-1"></i><?= h($c['contact_no'] ?: 'No Contact') ?></div>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-blue-50 border border-blue-200 text-blue-800">
                      <?= h(str_replace('_', ' ', $c['treatment_category'])) ?>
                    </span>
                    <div class="text-[11px] text-slate-400 capitalize mt-0.5"><?= h(str_replace('_', ' ', $c['tb_type'])) ?></div>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <div class="text-xs font-semibold text-slate-800"><?= h($c['treatment_start_date']) ?></div>
                    <div class="text-[11px] font-medium text-amber-700"><?= $phase ?> (Day <?= max(1, $days) ?>)</div>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="text-xs font-bold text-teal-700"><?= (int)$c['doses_taken'] ?> / <?= $estimatedTarget ?></div>
                      <div class="w-16 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-teal-600 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-3 text-right whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-1.5">
                      <!-- Quick 1-click Give Medicine Button with SweetAlert2 -->
                      <form method="POST" onsubmit="return confirmMarkGiven(event, this, '<?= htmlspecialchars(addslashes($c['first_name'] . ' ' . $c['last_name']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($c['case_number']), ENT_QUOTES) ?>');">
                        <input type="hidden" name="action" value="quick_dot_log">
                        <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="status" value="supervised">
                        <input type="hidden" name="remarks" value="Directly observed dose at health center">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition cursor-pointer">
                          <i class="fas fa-check text-[10px]"></i> Mark Given Today
                        </button>
                      </form>
                      <a href="/HealthLogs/public/tb/dots/index.php?case_id=<?= $c['id'] ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        DOTS Tracker
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    <!-- ==================== TAB 2: MEDICATION GIVEN TODAY ==================== -->
    <?php elseif ($activeTab === 'given'): ?>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
            <i class="fas fa-check-double text-emerald-600"></i>
            Patients Completed Medication Today (<?= date('M d, Y') ?>)
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Patients who have successfully received and recorded their daily anti-TB dose today.</p>
        </div>
      </div>

      <?php if (empty($givenTodayCases)): ?>
        <div class="p-8 text-center bg-slate-50 border border-slate-200 rounded-2xl text-slate-500 text-sm">
          No medication intakes recorded yet for today. Use the "Pending Medication Today" tab to log administered doses.
        </div>
      <?php else: ?>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
          <table class="w-full text-left text-sm min-w-[760px]">
            <thead>
              <tr class="bg-emerald-50/80 border-y border-emerald-200 text-emerald-900 uppercase text-[11px] font-bold tracking-wider">
                <th class="py-3 px-3.5">Patient Name</th>
                <th class="py-3 px-3">Case No</th>
                <th class="py-3 px-3">Barangay</th>
                <th class="py-3 px-3">Intake Status</th>
                <th class="py-3 px-3">Time & Supervising Staff</th>
                <th class="py-3 px-3">Total Doses Taken</th>
                <th class="py-3 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php foreach ($givenTodayCases as $c): ?>
                <tr class="hover:bg-emerald-50/30 transition">
                  <td class="py-3.5 px-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-900"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></div>
                    <div class="text-[11px] text-slate-400"><i class="fas fa-phone text-[10px] mr-1"></i><?= h($c['contact_no'] ?: 'No Contact') ?></div>
                  </td>
                  <td class="py-3.5 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                    <span class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs"><?= h($c['case_number']) ?></span>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap font-medium text-slate-800">
                    Brgy. <?= h($c['barangay']) ?>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 border border-emerald-200 text-emerald-800 capitalize">
                      <i class="fas fa-circle-check text-emerald-600"></i>
                      <?= h($c['today_status']) ?>
                    </span>
                    <?php if (!empty($c['today_remarks'])): ?>
                      <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs"><?= h($c['today_remarks']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <div class="text-xs font-semibold text-slate-800"><?= !empty($c['log_time']) ? date('h:i A', strtotime($c['log_time'])) : 'Today' ?></div>
                    <div class="text-[11px] text-slate-500 font-medium">By: <?= h($c['recorder_name'] ?: 'Health Worker') ?></div>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap font-bold text-teal-700">
                    <i class="fas fa-pills mr-1 text-teal-600"></i> <?= (int)$c['doses_taken'] ?> doses
                  </td>
                  <td class="py-3.5 px-3 text-right whitespace-nowrap">
                    <a href="/HealthLogs/public/tb/dots/index.php?case_id=<?= $c['id'] ?>" class="px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 font-semibold text-xs transition">
                      View Full Adherence Log
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    <!-- ==================== TAB 3: TREATMENT COMPLETED / CURED ==================== -->
    <?php elseif ($activeTab === 'completed'): ?>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
            <i class="fas fa-medal text-purple-600"></i>
            Patients Completed Full Treatment (Cured & Completed)
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">Patients who have successfully completed their full course of anti-TB treatment regimen.</p>
        </div>
      </div>

      <?php if (empty($completedTreatmentCases)): ?>
        <div class="p-8 text-center bg-slate-50 border border-slate-200 rounded-2xl text-slate-500 text-sm">
          No treatment-completed or cured cases recorded yet.
        </div>
      <?php else: ?>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
          <table class="w-full text-left text-sm min-w-[760px]">
            <thead>
              <tr class="bg-purple-50/80 border-y border-purple-200 text-purple-900 uppercase text-[11px] font-bold tracking-wider">
                <th class="py-3 px-3.5">Patient Name</th>
                <th class="py-3 px-3">Case No</th>
                <th class="py-3 px-3">Barangay</th>
                <th class="py-3 px-3">Treatment Outcome</th>
                <th class="py-3 px-3">Completion Date</th>
                <th class="py-3 px-3">Total Doses Administered</th>
                <th class="py-3 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php foreach ($completedTreatmentCases as $c): ?>
                <tr class="hover:bg-purple-50/30 transition">
                  <td class="py-3.5 px-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-900"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></div>
                    <div class="text-[11px] text-slate-400 capitalize"><?= h($c['sex'] ?? 'Unknown') ?></div>
                  </td>
                  <td class="py-3.5 px-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                    <span class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs"><?= h($c['case_number']) ?></span>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap font-medium text-slate-800">
                    Brgy. <?= h($c['barangay']) ?>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 border border-purple-200 text-purple-800 capitalize">
                      <i class="fas fa-medal text-purple-600"></i>
                      <?= h(str_replace('_', ' ', $c['treatment_outcome'] ?: 'Treatment Completed')) ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap font-semibold text-slate-800">
                    <?= !empty($c['treatment_outcome_date']) ? h($c['treatment_outcome_date']) : 'Completed' ?>
                  </td>
                  <td class="py-3.5 px-3 whitespace-nowrap font-bold text-teal-700">
                    <i class="fas fa-check mr-1 text-emerald-600"></i> <?= (int)$c['doses_taken'] ?> doses completed
                  </td>
                  <td class="py-3.5 px-3 text-right whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-1.5">
                      <a href="/HealthLogs/public/tb/cases/edit.php?id=<?= $c['id'] ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        View Details
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<!-- Navigation Module Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
  <a class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs block hover:shadow-md hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/cases/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 flex items-center justify-center text-lg">
        <i class="fas fa-folder-medical"></i>
      </span>
      <div>
        <div class="text-xs text-slate-400 font-bold uppercase">Case Registry</div>
        <div class="text-base font-bold text-slate-900 brand-font">TB Case Registry</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Enrolled patient cases, treatment classifications, and regimens.</p>
  </a>

  <a class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs block hover:shadow-md hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/dots/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-teal-50 border border-teal-100 text-teal-700 flex items-center justify-center text-lg">
        <i class="fas fa-calendar-check"></i>
      </span>
      <div>
        <div class="text-xs text-slate-400 font-bold uppercase">Adherence Tracking</div>
        <div class="text-base font-bold text-slate-900 brand-font">Daily DOTS Tracker</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Log daily anti-TB drug intake (taken, supervised, or missed).</p>
  </a>

  <a class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs block hover:shadow-md hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/labs/index.php">
    <div class="flex items-center gap-3">
      <span class="h-11 w-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-700 flex items-center justify-center text-lg">
        <i class="fas fa-microscope"></i>
      </span>
      <div>
        <div class="text-xs text-slate-400 font-bold uppercase">Laboratory Logs</div>
        <div class="text-base font-bold text-slate-900 brand-font">Lab Examinations</div>
      </div>
    </div>
    <p class="text-xs text-slate-500 mt-3">Record Sputum Smear, GeneXpert, and Chest X-ray results.</p>
  </a>
</div>

<script>
function confirmMarkGiven(event, form, patientName, caseNumber) {
  event.preventDefault();
  
  if (typeof Swal === 'undefined') {
    if (confirm('Confirm medication intake for ' + patientName + ' today?')) {
      form.submit();
    }
    return false;
  }

  Swal.fire({
    title: 'Confirm Medication Intake',
    html: `
      <div class="text-left bg-slate-50 p-4 rounded-xl border border-slate-200/80 mb-3 text-sm">
        <div class="flex justify-between items-center mb-1">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Patient Case</span>
          <span class="text-xs font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">${caseNumber}</span>
        </div>
        <div class="text-base font-bold text-slate-900 mb-2">${patientName}</div>
        <div class="text-xs text-slate-600 flex items-center gap-1.5 py-0.5">
          <i class="fas fa-calendar-day text-teal-600 w-4"></i>
          <span>Log Date: <strong><?= date('F d, Y') ?></strong></span>
        </div>
        <div class="text-xs text-slate-600 flex items-center gap-1.5 py-0.5">
          <i class="fas fa-user-check text-emerald-600 w-4"></i>
          <span>Intake Mode: <strong>Directly Observed (Supervised)</strong></span>
        </div>
      </div>
      <p class="text-xs text-slate-500 text-center">Are you sure you want to mark today's anti-TB dose as administered?</p>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#059669', // Emerald-600
    cancelButtonColor: '#64748b',  // Slate-500
    confirmButtonText: '<i class="fas fa-check mr-1.5"></i> Yes, Mark Given',
    cancelButtonText: 'Cancel',
    focusConfirm: true,
    reverseButtons: true,
    customClass: {
      popup: 'rounded-2xl shadow-2xl border border-slate-100',
      confirmButton: 'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-xs',
      cancelButton: 'px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Saving...',
        text: 'Recording daily dose intake',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      form.submit();
    }
  });

  return false;
}

<?php if (!empty($successMsg)): ?>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: '<?= addslashes($successMsg) ?>',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true
    });
  }
});
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'error',
      title: 'Action Failed',
      text: '<?= addslashes($errorMsg) ?>',
      confirmButtonColor: '#0f172a'
    });
  }
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>

