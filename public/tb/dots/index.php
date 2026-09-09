<?php
$pageTitle = 'Daily DOTS Tracker';
require __DIR__ . '/../../partials/header.php';

$case_id = (int)($_GET['case_id'] ?? 0);
$errors = [];
$successMsg = '';

// Handle form submission to log a DOTS intake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_case_id = (int)($_POST['tb_case_id'] ?? 0);
    $log_date = trim($_POST['log_date'] ?? '');
    $status = trim($_POST['status'] ?? 'taken');
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
                'status' => $status,
                'remarks' => $remarks !== '' ? $remarks : null,
                'recorded_by' => $recorded_by,
            ]);
            $successMsg = 'DOTS intake logged successfully for ' . date('M d, Y', strtotime($log_date)) . '!';
            $case_id = $posted_case_id;
        } catch (Throwable $e) {
            $errors[] = 'Failed to log DOTS intake: ' . $e->getMessage();
        }
    }
}

// Fetch all active TB cases for selector with today's intake status
$activeCases = [];
try {
    $activeCases = $pdo->query("
        SELECT c.id, c.case_number, p.first_name, p.last_name, p.barangay,
               (SELECT d.status FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.log_date = CURRENT_DATE() LIMIT 1) AS today_status,
               (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        WHERE c.status = 'active'
        ORDER BY p.last_name ASC, p.first_name ASC
    ")->fetchAll();
    if ($case_id <= 0 && !empty($activeCases)) {
        $case_id = (int)$activeCases[0]['id'];
    }
} catch (Throwable $e) {}

// Selected case details
$selectedCase = null;
$dotLogs = [];
$totalTaken = 0;
$totalMissed = 0;

if ($case_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, p.first_name, p.last_name, p.barangay, p.contact_no, p.sex, p.birth_date,
                   (SELECT d.status FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.log_date = CURRENT_DATE() LIMIT 1) AS today_status,
                   (SELECT d.remarks FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.log_date = CURRENT_DATE() LIMIT 1) AS today_remarks
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

<div class="bg-white/90 backdrop-blur-md p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-semibold uppercase tracking-wider text-teal-700 flex items-center gap-1.5 mb-1">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Directly Observed Therapy Short-Course (DOTS)
      </div>
      <div class="text-2xl font-bold text-slate-900 brand-font">Daily DOTS Intake Tracker</div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Select an active TB patient to record or review daily medication adherence.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (!empty($activeCases)): ?>
        <form method="GET" class="w-full sm:w-auto flex items-center gap-2">
          <select name="case_id" onchange="this.form.submit()" class="w-full sm:w-auto border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold bg-white shadow-2xs">
            <?php foreach ($activeCases as $ac): ?>
              <?php $isDoneToday = in_array($ac['today_status'], ['taken', 'supervised']); ?>
              <option value="<?= $ac['id'] ?>" <?= (int)$ac['id'] === $case_id ? 'selected' : '' ?>>
                <?= $isDoneToday ? '✓ [TAKEN TODAY]' : '⏳ [DUE TODAY]' ?> <?= h($ac['case_number']) ?> - <?= h($ac['last_name'] . ', ' . $ac['first_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
      <a href="/HealthLogs/public/tb.php" class="inline-flex items-center gap-1 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-xl transition">
        <i class="fas fa-arrow-left text-xs"></i> TB Module
      </a>
    </div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm flex items-center gap-2 shadow-xs">
    <i class="fas fa-check-circle text-emerald-600 text-base"></i>
    <span><?= h($successMsg) ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="mt-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm space-y-1 shadow-xs">
    <?php foreach ($errors as $err): ?>
      <p><i class="fas fa-circle-exclamation text-red-600 mr-1"></i><?= h($err) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($selectedCase): ?>
  <?php 
    $targetDoses = ($selectedCase['treatment_category'] === 'category_2') ? 240 : 180;
    $takenToday = in_array($selectedCase['today_status'] ?? '', ['taken', 'supervised']);
  ?>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
    <!-- Patient Card -->
    <div class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs sm:col-span-2 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between">
          <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Enrolled Patient Details</div>
          <?php if ($takenToday): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 border border-emerald-200 text-emerald-800">
              <i class="fas fa-check-circle text-emerald-600"></i> Completed Today
            </span>
          <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 border border-amber-200 text-amber-800">
              <i class="fas fa-clock text-amber-600"></i> Pending Dose Today
            </span>
          <?php endif; ?>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-slate-900 brand-font mt-2"><?= h($selectedCase['last_name'] . ', ' . $selectedCase['first_name']) ?></div>
        <div class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-2 sm:gap-4">
          <span>Case No: <strong class="font-mono text-slate-900"><?= h($selectedCase['case_number']) ?></strong></span>
          <span>&bull;</span>
          <span>Brgy. <?= h($selectedCase['barangay']) ?></span>
          <span>&bull;</span>
          <span>Started: <?= h($selectedCase['treatment_start_date']) ?></span>
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div class="mt-4 pt-3 border-t border-slate-100">
        <div class="flex justify-between text-xs font-bold mb-1">
          <span class="text-slate-600">Treatment Regimen Progress (<?= h(str_replace('_', ' ', $selectedCase['treatment_category'])) ?>)</span>
          <span class="text-teal-700"><?= $totalTaken ?> / <?= $targetDoses ?> doses (<?= min(100, round(($totalTaken / $targetDoses) * 100)) ?>%)</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
          <div class="bg-gradient-to-r from-teal-500 to-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, round(($totalTaken / $targetDoses) * 100)) ?>%"></div>
        </div>
      </div>
    </div>

    <!-- Stats summary -->
    <div class="bg-white/95 p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-around text-center">
      <div>
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Doses Taken</div>
        <div class="text-3xl font-extrabold text-teal-600 mt-1 brand-font"><?= $totalTaken ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Administered / observed</div>
      </div>
      <div class="w-full h-px bg-slate-100 my-2"></div>
      <div>
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Doses Missed</div>
        <div class="text-3xl font-extrabold text-rose-600 mt-1 brand-font"><?= $totalMissed ?></div>
        <div class="text-xs text-slate-500 mt-0.5">Untaken days recorded</div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Log Form -->
    <div class="lg:col-span-1 bg-white p-4 sm:p-5 rounded-xl shadow h-fit">
      <div class="text-sm text-slate-500">Daily Entry</div>
      <div class="text-lg font-semibold mb-4">Log Dose Intake</div>

      <form method="POST" class="space-y-3">
        <input type="hidden" name="tb_case_id" value="<?= $selectedCase['id'] ?>" />

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Intake Date</label>
          <input type="date" name="log_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Dose Status</label>
          <select name="status" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
            <option value="supervised">Supervised (DOTS Observed)</option>
            <option value="taken">Self-Administered / Taken</option>
            <option value="missed">Missed / Untaken</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Remarks</label>
          <textarea name="remarks" rows="2" placeholder="Clinical notes or side effects..." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
        </div>

        <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow">Save Intake Log</button>
      </form>
    </div>

    <!-- History Table -->
    <div class="lg:col-span-2 bg-white p-4 sm:p-5 rounded-xl shadow">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-slate-500">History</div>
          <div class="text-lg font-semibold">Medication Intake Logs</div>
        </div>
        <span class="text-xs text-slate-500"><?= count($dotLogs) ?> records</span>
      </div>

      <?php if (empty($dotLogs)): ?>
        <div class="text-sm text-slate-500 mt-4">No medication logs recorded yet for this case.</div>
      <?php else: ?>
        <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
          <table class="w-full text-left text-sm min-w-[500px]">
            <thead>
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
                  <td class="py-3 px-3 font-medium text-slate-900 whitespace-nowrap"><?= h($l['log_date']) ?></td>
                  <td class="py-3 px-3 whitespace-nowrap">
                    <?php if ($l['status'] === 'supervised'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Supervised</span>
                    <?php elseif ($l['status'] === 'taken'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Taken</span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Missed</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-3 text-xs text-slate-600"><?= h($l['remarks'] ?? '—') ?></td>
                  <td class="py-3 px-3 text-xs text-slate-500 whitespace-nowrap"><?= h($l['recorder_name'] ?? 'System') ?></td>
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
    <p class="text-sm">No active TB cases available to log DOTS.</p>
    <a href="/HealthLogs/public/tb/cases/create.php" class="mt-2 inline-block text-sm font-medium text-slate-900 underline">Register a new case &rarr;</a>
  </div>
<?php endif; ?>

<?php if (!empty($successMsg)): ?>
<script>
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
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
