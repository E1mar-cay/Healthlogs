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

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-semibold">
        <a href="/HealthLogs/public/family_planning.php" class="text-purple-600 hover:underline">&larr; Back to FP Dashboard</a>
      </div>
      <div class="text-2xl font-bold text-slate-900 mt-1">Family Planning Client Registry</div>
      <p class="text-sm text-slate-500 mt-1">Directory of all registered family planning clients, current methods, and service histories.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/family_planning/tcl.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition">
        <i class="fas fa-table-list mr-1.5 text-xs"></i> View TCL
      </a>
      <button type="button" onclick="openEnrollModal()" class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-700 text-white hover:bg-purple-800 text-xs font-semibold shadow transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> Enroll Client
      </button>
    </div>
  </div>
</div>

<?php display_flash_messages(); ?>

<div class="bg-white rounded-xl shadow p-4 sm:p-6">
  <!-- Search and Filter Bar -->
  <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <div class="lg:col-span-2">
      <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search client code, patient, partner..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Method</label>
      <select name="method" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Methods</option>
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
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
      <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="dropped_out" <?= $statusFilter === 'dropped_out' ? 'selected' : '' ?>>Dropped Out</option>
      </select>
    </div>

    <div class="flex items-end gap-2">
      <button type="submit" class="w-full bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
        Filter
      </button>
      <?php if ($search !== '' || $methodFilter !== '' || $statusFilter !== 'all' || $barangayFilter !== ''): ?>
        <a href="?" class="px-3 py-2 border rounded-lg text-xs text-slate-600 hover:bg-slate-50 transition">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="text-xs text-slate-500 mb-3 font-medium">
    Showing <strong><?= count($clients) ?></strong> client record(s)
  </div>

  <?php if (empty($clients)): ?>
    <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
      <i class="fas fa-folder-open text-3xl text-slate-400 mb-2"></i>
      <p class="text-sm font-semibold text-slate-700">No Family Planning client records found.</p>
      <p class="text-xs text-slate-500 mt-1">Enroll your first client to begin tracking contraceptive services.</p>
      <button type="button" onclick="openEnrollModal()" class="inline-block mt-3 px-4 py-2 rounded-lg bg-purple-700 text-white text-xs font-semibold hover:bg-purple-800 transition">Enroll New Client</button>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-sm min-w-[760px]">
        <thead>
          <tr class="border-b bg-slate-50 text-slate-500 uppercase text-xs">
            <th class="py-3 px-3">Client Code</th>
            <th class="py-3 px-3">Patient Name</th>
            <th class="py-3 px-3">Barangay</th>
            <th class="py-3 px-3">Client Type</th>
            <th class="py-3 px-3">Method Accepted</th>
            <th class="py-3 px-3">Total Visits</th>
            <th class="py-3 px-3">Next Due</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php foreach ($clients as $c): ?>
            <tr class="hover:bg-slate-50/80 transition">
              <td class="py-3 px-3 font-mono font-semibold text-purple-700 whitespace-nowrap">
                <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $c['id'] ?>" class="hover:underline">
                  <?= h($c['client_code']) ?>
                </a>
              </td>
              <td class="py-3 px-3 whitespace-nowrap font-medium text-slate-900">
                <?= h($c['last_name'] . ', ' . $c['first_name']) ?>
                <div class="text-xs text-slate-400 font-normal"><?= (int)$c['age'] ?> yrs old &bull; <?= h($c['contact_no'] ?: 'No phone') ?></div>
              </td>
              <td class="py-3 px-3 whitespace-nowrap text-xs text-slate-600"><?= h($c['barangay'] ?: '—') ?></td>
              <td class="py-3 px-3 whitespace-nowrap text-xs capitalize"><?= h(str_replace('_', ' ', $c['client_type'])) ?></td>
              <td class="py-3 px-3 whitespace-nowrap text-xs font-semibold text-slate-800"><?= fp_format_method($c['method_accepted']) ?></td>
              <td class="py-3 px-3 whitespace-nowrap text-xs text-center font-bold text-slate-700"><?= (int)$c['total_visits'] ?></td>
              <td class="py-3 px-3 whitespace-nowrap text-xs font-mono">
                <?= !empty($c['next_appointment_date']) ? date('M d, Y', strtotime($c['next_appointment_date'])) : '—' ?>
              </td>
              <td class="py-3 px-3 whitespace-nowrap">
                <?php if ($c['status'] === 'active'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                <?php elseif ($c['status'] === 'dropped_out'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Dropped Out</span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $c['id'] ?>" class="text-xs bg-purple-50 text-purple-700 hover:bg-purple-100 px-2.5 py-1.5 rounded-lg font-semibold transition" title="Log visit / view visits">
                    <i class="fas fa-history mr-1"></i> Visits
                  </a>
                  <a href="/HealthLogs/public/family_planning/records/edit.php?id=<?= $c['id'] ?>" class="text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 px-2.5 py-1.5 rounded-lg font-semibold transition" title="Edit Profile">
                    <i class="fas fa-edit"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../_enroll_modal.php'; ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
