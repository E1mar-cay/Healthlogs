<?php
$pageTitle = 'TB Lab Examinations';
require __DIR__ . '/../../partials/header.php';

$case_id = (int)($_GET['case_id'] ?? 0);
$errors = [];
$successMsg = '';

// Handle form submission to record a new lab test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_case_id = (int)($_POST['tb_case_id'] ?? 0);
    $test_date = trim($_POST['test_date'] ?? '');
    $test_type = trim($_POST['test_type'] ?? 'sputum_smear');
    $timing = trim($_POST['timing'] ?? 'baseline');
    $result = trim($_POST['result'] ?? '');
    $laboratory_name = trim($_POST['laboratory_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $recorded_by = $_SESSION['user_id'] ?? null;

    if ($posted_case_id <= 0) {
        $errors[] = 'Please select a TB case.';
    }
    if (empty($test_date)) {
        $errors[] = 'Test date is required.';
    }
    if (empty($result)) {
        $errors[] = 'Test result is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tb_lab_examinations (tb_case_id, test_date, test_type, timing, result, laboratory_name, notes, recorded_by)
                VALUES (:tb_case_id, :test_date, :test_type, :timing, :result, :laboratory_name, :notes, :recorded_by)
            ");
            $stmt->execute([
                'tb_case_id' => $posted_case_id,
                'test_date' => $test_date,
                'test_type' => $test_type,
                'timing' => $timing,
                'result' => $result,
                'laboratory_name' => $laboratory_name !== '' ? $laboratory_name : null,
                'notes' => $notes !== '' ? $notes : null,
                'recorded_by' => $recorded_by,
            ]);
            $successMsg = 'Lab examination result recorded successfully!';
            $case_id = $posted_case_id;
        } catch (Throwable $e) {
            $errors[] = 'Failed to record lab result: ' . $e->getMessage();
        }
    }
}

// Fetch cases for selector
$allCases = [];
try {
    $allCases = $pdo->query("
        SELECT c.id, c.case_number, p.first_name, p.last_name, c.status
        FROM tb_cases c
        JOIN patients p ON p.id = c.patient_id
        ORDER BY c.created_at DESC
    ")->fetchAll();
} catch (Throwable $e) {}

// Query lab results
$query = "
    SELECT l.*, c.case_number, p.first_name, p.last_name, u.full_name AS recorder_name
    FROM tb_lab_examinations l
    JOIN tb_cases c ON c.id = l.tb_case_id
    JOIN patients p ON p.id = c.patient_id
    LEFT JOIN users u ON u.id = l.recorded_by
    WHERE 1=1
";
$params = [];
if ($case_id > 0) {
    $query .= " AND l.tb_case_id = :case_id";
    $params['case_id'] = $case_id;
}
$query .= " ORDER BY l.test_date DESC, l.id DESC";

$labTests = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $labTests = $stmt->fetchAll();
} catch (Throwable $e) {}
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Laboratory Care</div>
      <div class="text-2xl font-semibold">TB Lab Examinations</div>
      <p class="text-sm text-slate-500 mt-1">Sputum Smear Microscopy, GeneXpert MTB/RIF, and Chest X-ray monitoring.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <form method="GET" class="w-full sm:w-auto flex items-center gap-2">
        <select name="case_id" onchange="this.form.submit()" class="w-full sm:w-auto border rounded-lg px-3 py-2 text-sm font-medium bg-white">
          <option value="0">-- All Registered Patients --</option>
          <?php foreach ($allCases as $ac): ?>
            <option value="<?= $ac['id'] ?>" <?= (int)$ac['id'] === $case_id ? 'selected' : '' ?>>
              <?= h($ac['case_number']) ?> - <?= h($ac['last_name'] . ', ' . $ac['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <a href="/HealthLogs/public/tb.php" class="text-sm font-medium text-slate-600 hover:text-slate-900 underline ml-1">Back to Module</a>
    </div>
  </div>
</div>

<?php if (!empty($successMsg)): ?>
  <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl text-sm">
    &check; <?= h($successMsg) ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="mt-6 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm space-y-1">
    <?php foreach ($errors as $err): ?>
      <p>&bull; <?= h($err) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
  <!-- Form -->
  <div class="lg:col-span-1 bg-white p-4 sm:p-5 rounded-xl shadow h-fit">
    <div class="text-sm text-slate-500">Lab Record</div>
    <div class="text-lg font-semibold mb-4">Add Examination Result</div>

    <form method="POST" class="space-y-3">
      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">TB Patient Case *</label>
        <select name="tb_case_id" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
          <option value="">-- Choose TB Case --</option>
          <?php foreach ($allCases as $ac): ?>
            <option value="<?= $ac['id'] ?>" <?= (int)$ac['id'] === $case_id ? 'selected' : '' ?>>
              <?= h($ac['case_number']) ?> - <?= h($ac['last_name'] . ', ' . $ac['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Test Date *</label>
        <input type="date" name="test_date" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Test Type *</label>
        <select name="test_type" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
          <option value="sputum_smear">Sputum Smear Microscopy (AFB)</option>
          <option value="genexpert">GeneXpert MTB/RIF</option>
          <option value="chest_xray">Chest X-Ray</option>
          <option value="culture">Mycobacterial Culture</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Monitoring Timing *</label>
        <select name="timing" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
          <option value="baseline">Baseline (Pre-treatment)</option>
          <option value="month_2">Month 2 (End of Intensive Phase)</option>
          <option value="month_5">Month 5 (Continuation Phase)</option>
          <option value="month_6">Month 6 (End of Regimen)</option>
          <option value="end_of_treatment">End of Treatment</option>
          <option value="other">Other Follow-up</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Result Summary *</label>
        <input type="text" name="result" placeholder="e.g. Negative, 1+, 2+, MTB Detected" required class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Testing Laboratory</label>
        <input type="text" name="laboratory_name" placeholder="e.g. Health Center Lab" class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
        <textarea name="notes" rows="2" placeholder="Details or resistance info..." class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition shadow">Save Lab Result</button>
    </form>
  </div>

  <!-- Table -->
  <div class="lg:col-span-2 bg-white p-4 sm:p-5 rounded-xl shadow">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Log History</div>
        <div class="text-lg font-semibold">Laboratory Examinations History</div>
      </div>
      <span class="text-xs text-slate-500"><?= count($labTests) ?> tests</span>
    </div>

    <?php if (empty($labTests)): ?>
      <div class="text-sm text-slate-500 mt-4">No laboratory test results recorded.</div>
    <?php else: ?>
      <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
        <table class="w-full text-left text-sm min-w-[550px]">
          <thead>
            <tr class="border-b text-slate-500 uppercase text-xs">
              <th class="py-2.5 px-3">Date / Patient</th>
              <th class="py-2.5 px-3">Test Type</th>
              <th class="py-2.5 px-3">Timing</th>
              <th class="py-2.5 px-3">Result</th>
              <th class="py-2.5 px-3">Laboratory</th>
            </tr>
          </thead>
          <tbody class="divide-y text-slate-700">
            <?php foreach ($labTests as $lt): ?>
              <tr>
                <td class="py-3 px-3 font-medium text-slate-900 whitespace-nowrap">
                  <?= h($lt['last_name'] . ', ' . $lt['first_name']) ?>
                  <div class="text-xs text-slate-500 font-mono"><?= h($lt['case_number']) ?> &bull; <?= h($lt['test_date']) ?></div>
                </td>
                <td class="py-3 px-3 uppercase text-xs font-semibold text-slate-800 whitespace-nowrap"><?= h(str_replace('_', ' ', $lt['test_type'])) ?></td>
                <td class="py-3 px-3 capitalize text-xs whitespace-nowrap"><?= h(str_replace('_', ' ', $lt['timing'])) ?></td>
                <td class="py-3 px-3 font-medium text-slate-900">
                  <?= h($lt['result']) ?>
                  <?php if (!empty($lt['notes'])): ?>
                    <div class="text-xs text-slate-400"><?= h($lt['notes']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-3 text-xs text-slate-500 whitespace-nowrap"><?= h($lt['laboratory_name'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
