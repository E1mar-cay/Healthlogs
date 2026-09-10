<?php
$pageTitle = 'Family Planning Client Registry';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$search = trim($_GET['search'] ?? '');
$methodFilter = trim($_GET['method'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$barangayFilter = trim($_GET['barangay'] ?? '');

$barangays = [];
try {
    $barangays = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

$query = "
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.contact_no, p.barangay, p.birth_date, p.sex,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
           (SELECT COUNT(*) FROM fp_visits v WHERE v.fp_record_id = r.id) AS total_visits,
           (SELECT MAX(visit_date) FROM fp_visits v WHERE v.fp_record_id = r.id) AS last_visit_date,
           (SELECT next_appointment_date FROM fp_visits v WHERE v.fp_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS next_appointment_date
    FROM fp_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (r.client_code LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR r.partner_name LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($methodFilter !== '') {
    $query .= " AND r.method_accepted = :method";
    $params['method'] = $methodFilter;
}

if ($statusFilter !== 'all') {
    $query .= " AND r.status = :status";
    $params['status'] = $statusFilter;
}

if ($barangayFilter !== '') {
    $query .= " AND p.barangay = :barangay";
    $params['barangay'] = $barangayFilter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

function fp_format_method(?string $m): string {
    $map = [
        'pills_coc' => 'Pills (COC)',
        'pills_pop' => 'Pills (POP)',
        'injectable_dmpa' => 'DMPA Injectable',
        'implant' => 'Subdermal Implant',
        'iud_interval' => 'IUD (Interval)',
        'iud_postpartum' => 'IUD (Postpartum)',
        'condom' => 'Condom',
        'btl' => 'BTL',
        'nsv' => 'NSV',
        'natural_lam' => 'Natural (LAM)',
        'natural_sdm' => 'Natural (SDM)',
        'natural_stm' => 'Natural (STM)',
    ];
    return $map[$m] ?? ucwords(str_replace('_', ' ', (string)$m));
}
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Family Planning Clients</div>
  <div class="flex items-center gap-2">
    <a href="/HealthLogs/public/family_planning/tcl.php" class="px-3 py-2 rounded border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition">View TCL</a>
    <button type="button" onclick="openEnrollModal()" class="bg-slate-900 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-slate-800 transition">+ Enroll Client</button>
  </div>
</div>

<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="search" value="<?= h($search) ?>" class="w-full border rounded px-3 py-2 text-sm" placeholder="Search client code, patient, partner..." />
  <select name="method" class="w-full md:w-52 border rounded px-3 py-2 text-sm">
    <option value="">All methods</option>
    <option value="pills_coc" <?= $methodFilter === 'pills_coc' ? 'selected' : '' ?>>Pills (COC)</option>
    <option value="pills_pop" <?= $methodFilter === 'pills_pop' ? 'selected' : '' ?>>Pills (POP)</option>
    <option value="injectable_dmpa" <?= $methodFilter === 'injectable_dmpa' ? 'selected' : '' ?>>DMPA Injectable</option>
    <option value="implant" <?= $methodFilter === 'implant' ? 'selected' : '' ?>>Subdermal Implant</option>
    <option value="iud_interval" <?= $methodFilter === 'iud_interval' ? 'selected' : '' ?>>IUD (Interval)</option>
    <option value="iud_postpartum" <?= $methodFilter === 'iud_postpartum' ? 'selected' : '' ?>>IUD (Postpartum)</option>
    <option value="condom" <?= $methodFilter === 'condom' ? 'selected' : '' ?>>Condom</option>
    <option value="btl" <?= $methodFilter === 'btl' ? 'selected' : '' ?>>BTL</option>
    <option value="natural_lam" <?= $methodFilter === 'natural_lam' ? 'selected' : '' ?>>Natural (LAM)</option>
  </select>
  <select name="status" class="w-full md:w-40 border rounded px-3 py-2 text-sm">
    <option value="all">All statuses</option>
    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    <option value="dropped_out" <?= $statusFilter === 'dropped_out' ? 'selected' : '' ?>>Dropped Out</option>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded text-xs font-semibold hover:bg-slate-800 transition" type="submit">Search</button>
    <a class="px-4 py-2 rounded border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition" href="/HealthLogs/public/family_planning/records/index.php">Clear</a>
  </div>
</form>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Client Code</th>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Barangay</th>
        <th class="text-left px-4 py-2">Client Type</th>
        <th class="text-left px-4 py-2">Method Accepted</th>
        <th class="text-left px-4 py-2">Visits</th>
        <th class="text-left px-4 py-2">Next Due</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($clients)): ?>
        <tr><td class="px-4 py-4" colspan="9">No Family Planning client records found.</td></tr>
      <?php else: ?>
        <?php foreach ($clients as $c): ?>
          <tr class="border-t">
            <td class="px-4 py-2 font-mono font-medium"><?= h($c['client_code']) ?></td>
            <td class="px-4 py-2 font-medium"><?= h($c['last_name'] . ', ' . $c['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($c['barangay'] ?: '—') ?></td>
            <td class="px-4 py-2 capitalize"><?= h(str_replace('_', ' ', $c['client_type'])) ?></td>
            <td class="px-4 py-2"><?= fp_format_method($c['method_accepted']) ?></td>
            <td class="px-4 py-2"><?= (int)$c['total_visits'] ?></td>
            <td class="px-4 py-2"><?= !empty($c['next_appointment_date']) ? date('M d, Y', strtotime($c['next_appointment_date'])) : '—' ?></td>
            <td class="px-4 py-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs <?= $c['status'] === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                <?= ucfirst(h($c['status'])) ?>
              </span>
            </td>
            <td class="px-4 py-2">
              <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $c['id'] ?>" class="text-blue-600 hover:underline mr-2">Visits</a>
              <a href="/HealthLogs/public/family_planning/records/edit.php?id=<?= $c['id'] ?>" class="text-slate-600 hover:underline">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../_enroll_modal.php'; ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
