<?php
$pageTitle = 'TB Case Registry';
require __DIR__ . '/../../partials/header.php';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT c.*, p.first_name, p.last_name, p.contact_no, p.barangay, p.sex, p.birth_date,
           (SELECT COUNT(*) FROM tb_dot_logs d WHERE d.tb_case_id = c.id AND d.status IN ('taken','supervised')) AS doses_taken
    FROM tb_cases c
    JOIN patients p ON p.id = c.patient_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (c.case_number LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR p.barangay LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $query .= " AND c.status = :status";
    $params['status'] = $statusFilter;
}

$query .= " ORDER BY c.created_at DESC";

$cases = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cases = $stmt->fetchAll();
} catch (Throwable $e) {
    // Graceful error handle
}
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Program Registry</div>
      <div class="text-2xl font-semibold">TB Case Registry</div>
      <p class="text-sm text-slate-500 mt-1">Enrolled patient cases under National Tuberculosis Control Program (NTP).</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">NTP DOTS</span>
      <a href="/HealthLogs/public/tb/cases/create.php" class="bg-slate-900 text-white px-4 py-2 rounded shadow text-sm font-medium hover:bg-slate-800 transition">New TB Case</a>
      <a href="/HealthLogs/public/tb.php" class="text-sm text-slate-600 underline">Back to Module</a>
    </div>
  </div>
</div>

<form method="GET" class="mt-6 bg-white rounded shadow p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
  <input name="search" value="<?= h($search) ?>" class="w-full border rounded px-3 py-2 md:col-span-2" placeholder="Search case no, patient name, or barangay..." />
  <select name="status" class="w-full border rounded px-3 py-2">
    <option value="">All statuses</option>
    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
    <option value="discontinued" <?= $statusFilter === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
  </select>
  <div class="md:col-span-4 flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded text-sm font-medium" type="submit">Apply Filter</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
      <a href="/HealthLogs/public/tb/cases/index.php" class="px-4 py-2 rounded border border-slate-300 text-slate-700 text-sm">Clear</a>
    <?php endif; ?>
  </div>
</form>

<div class="bg-white rounded shadow p-5 mt-6">
  <div class="flex items-center justify-between">
    <div>
      <div class="text-sm text-slate-500">Registry List</div>
      <div class="text-lg font-semibold">Registered Cases (<?= count($cases) ?>)</div>
    </div>
  </div>

  <?php if (empty($cases)): ?>
    <div class="text-sm text-slate-500 mt-4">No TB cases found. Try adjusting search filters or register a new case.</div>
  <?php else: ?>
    <div class="overflow-x-auto mt-4">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b text-slate-500 uppercase text-xs">
            <th class="py-2.5 px-3">Case No</th>
            <th class="py-2.5 px-3">Patient Name</th>
            <th class="py-2.5 px-3">Barangay</th>
            <th class="py-2.5 px-3">Classification</th>
            <th class="py-2.5 px-3">Regimen</th>
            <th class="py-2.5 px-3">Reg. Date</th>
            <th class="py-2.5 px-3">Status</th>
            <th class="py-2.5 px-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php foreach ($cases as $c): ?>
            <tr>
              <td class="py-3 px-3 font-mono font-medium text-slate-900"><?= h($c['case_number']) ?></td>
              <td class="py-3 px-3 font-medium text-slate-900"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></td>
              <td class="py-3 px-3"><?= h($c['barangay']) ?></td>
              <td class="py-3 px-3 capitalize">
                <?= h(str_replace('_', ' ', $c['tb_type'])) ?>
                <div class="text-xs text-slate-400 capitalize"><?= h(str_replace('_', ' ', $c['case_definition'])) ?></div>
              </td>
              <td class="py-3 px-3 font-medium uppercase"><?= h(str_replace('_', ' ', $c['treatment_category'])) ?></td>
              <td class="py-3 px-3"><?= h($c['registration_date']) ?></td>
              <td class="py-3 px-3">
                <?php if ($c['status'] === 'active'): ?>
                  <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                <?php elseif ($c['status'] === 'completed'): ?>
                  <span class="px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">Completed</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">Discontinued</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 text-right space-x-1.5">
                <a href="/HealthLogs/public/tb/dots/index.php?case_id=<?= $c['id'] ?>" class="text-xs bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1 rounded font-medium">DOTS Log</a>
                <a href="/HealthLogs/public/tb/labs/index.php?case_id=<?= $c['id'] ?>" class="text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 px-2.5 py-1 rounded font-medium">Labs</a>
                <a href="/HealthLogs/public/tb/cases/edit.php?id=<?= $c['id'] ?>" class="text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 px-2.5 py-1 rounded font-medium">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
