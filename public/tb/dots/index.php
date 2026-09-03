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

// Fetch all active TB cases for selector
$activeCases = [];
try {
    $activeCases = $pdo->query("
        SELECT c.id, c.case_number, p.first_name, p.last_name, p.barangay
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

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Adherence Monitoring</div>
      <div class="text-2xl font-semibold">Daily DOTS Tracker</div>
      <p class="text-sm text-slate-500 mt-1">Directly Observed Therapy Short-Course daily adherence log.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (!empty($activeCases)): ?>
        <form method="GET" class="flex items-center gap-2">
          <select name="case_id" onchange="this.form.submit()" class="border rounded px-3 py-1.5 text-sm font-medium">
            <?php foreach ($activeCases as $ac): ?>
              <option value="<?= $ac['id'] ?>" <?= (int)$ac['id'] === $case_id ? 'selected' : '' ?>>
                <?= h($ac['case_number']) ?> - <?= h($ac['last_name'] . ', ' . $ac['first_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
      <a href="/HealthLogs/public/tb.php" class="text-sm text-slate-600 underline">Back to Dashboard</a>
    </div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded text-sm">
    &check; <?= h($successMsg) ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="mt-6 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded text-sm space-y-1">
    <?php foreach ($errors as $err): ?>
      <p>&bull; <?= h($err) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($selectedCase): ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
    <div class="bg-white p-5 rounded shadow">
      <div class="text-xs uppercase tracking-widest text-slate-500">Patient Details</div>
      <div class="text-xl font-semibold mt-1"><?= h($selectedCase['last_name'] . ', ' . $selectedCase['first_name']) ?></div>
      <div class="text-sm text-slate-500 mt-1">
        Case No: <span class="font-mono font-medium text-slate-900"><?= h($selectedCase['case_number']) ?></span> &bull; Brgy. <?= h($selectedCase['barangay']) ?>
      </div>
      <div class="text-xs text-slate-400 mt-2">Started: <?= h($selectedCase['treatment_start_date']) ?></div>
    </div>

    <div class="bg-white p-5 rounded shadow flex items-center justify-around text-center">
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-500">Doses Taken</div>
        <div class="text-2xl font-semibold text-teal-600 mt-1"><?= $totalTaken ?></div>
      </div>
      <div class="w-px h-10 bg-slate-200"></div>
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-500">Doses Missed</div>
        <div class="text-2xl font-semibold text-rose-600 mt-1"><?= $totalMissed ?></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <!-- Log Form -->
    <div class="lg:col-span-1 bg-white p-5 rounded shadow h-fit">
      <div class="text-sm text-slate-500">Daily Entry</div>
      <div class="text-lg font-semibold mb-4">Log Dose Intake</div>

      <form method="POST" class="space-y-3">
        <input type="hidden" name="tb_case_id" value="<?= $selectedCase['id'] ?>" />

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Intake Date</label>
          <input type="date" name="log_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required class="w-full border rounded px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Dose Status</label>
          <select name="status" required class="w-full border rounded px-3 py-2 text-sm">
            <option value="supervised">Supervised (DOTS Observed)</option>
            <option value="taken">Self-Administered / Taken</option>
            <option value="missed">Missed / Untaken</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700 mb-1">Remarks</label>
          <textarea name="remarks" rows="2" placeholder="Clinical notes or side effects..." class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded text-sm font-medium hover:bg-slate-800 transition">Save Intake Log</button>
      </form>
    </div>

    <!-- History Table -->
    <div class="lg:col-span-2 bg-white p-5 rounded shadow">
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
        <div class="overflow-x-auto mt-4">
          <table class="w-full text-left text-sm">
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
                  <td class="py-3 px-3 font-medium text-slate-900"><?= h($l['log_date']) ?></td>
                  <td class="py-3 px-3">
                    <?php if ($l['status'] === 'supervised'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Supervised</span>
                    <?php elseif ($l['status'] === 'taken'): ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Taken</span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Missed</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-3 text-xs text-slate-600"><?= h($l['remarks'] ?? '—') ?></td>
                  <td class="py-3 px-3 text-xs text-slate-500"><?= h($l['recorder_name'] ?? 'System') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="mt-6 bg-white p-8 rounded shadow text-center text-slate-500">
    <p class="text-sm">No active TB cases available to log DOTS.</p>
    <a href="/HealthLogs/public/tb/cases/create.php" class="mt-2 inline-block text-sm font-medium text-slate-900 underline">Register a new case &rarr;</a>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
