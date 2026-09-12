<?php
$pageTitle = 'NCD Patient Registry';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$search = trim($_GET['search'] ?? '');
$diagnosisFilter = trim($_GET['diagnosis'] ?? '');
$riskFilter = trim($_GET['risk'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$barangayFilter = trim($_GET['barangay'] ?? '');

$barangays = [];
try {
    $barangays = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

$countQuery = "
    SELECT COUNT(*)
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE 1=1
";

$dataQuery = "
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.contact_no, p.barangay, p.birth_date, p.sex,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
           (SELECT COUNT(*) FROM ncd_visits v WHERE v.ncd_record_id = r.id) AS total_visits,
           (SELECT MAX(visit_date) FROM ncd_visits v WHERE v.ncd_record_id = r.id) AS last_visit_date,
           (SELECT bp_systolic FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bp_sys,
           (SELECT bp_diastolic FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bp_dia,
           (SELECT blood_sugar_mgdl FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bs_mgdl,
           (SELECT next_appointment_date FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS next_appointment_date
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE 1=1
";
$where = "";
$params = [];

if ($search !== '') {
    $where .= " AND (r.ncd_code LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR r.maintenance_meds LIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($diagnosisFilter !== '') {
    $where .= " AND r.diagnosis_type = :diagnosis";
    $params['diagnosis'] = $diagnosisFilter;
}

if ($riskFilter !== '') {
    $where .= " AND r.philpen_risk_level = :risk";
    $params['risk'] = $riskFilter;
}

if ($statusFilter !== 'all') {
    $where .= " AND r.status = :status";
    $params['status'] = $statusFilter;
}

if ($barangayFilter !== '') {
    $where .= " AND p.barangay = :barangay";
    $params['barangay'] = $barangayFilter;
}

// Count total records
$countStmt = $pdo->prepare($countQuery . $where);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// Create paginator (10 records per page)
$paginator = paginate($totalRecords, 10);

$dataStmt = $pdo->prepare($dataQuery . $where . " ORDER BY r.created_at DESC " . $paginator->getLimitSql());
$dataStmt->execute($params);
$clients = $dataStmt->fetchAll();

function ncd_format_diag_badge(?string $d): string {
    $map = [
        'hypertension' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">Hypertension</span>',
        'diabetes' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-teal-100 text-teal-800">Diabetes</span>',
        'hypertension_diabetes' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">HPN &amp; Diabetes</span>',
        'cardiovascular' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">CVD / CAD</span>',
        'asthma_copd' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Asthma/COPD</span>',
        'chronic_kidney' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-cyan-100 text-cyan-800">Chronic Kidney</span>',
        'cancer' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">Cancer</span>',
        'other' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">Other NCD</span>',
    ];
    return $map[$d] ?? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">' . htmlspecialchars((string)$d) . '</span>';
}

function ncd_risk_badge(?string $r): string {
    switch ($r) {
        case 'very_high':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">&ge;30% (Very High)</span>';
        case 'high':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200">20-30% (High)</span>';
        case 'moderate':
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">10-20% (Mod)</span>';
        default:
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">&lt;10% (Low)</span>';
    }
}
?>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-semibold">
        <a href="/HealthLogs/public/ncd.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to NCD Dashboard</a>
      </div>
      <div class="text-2xl font-bold text-slate-900 mt-1">NCD Patient Registry</div>
      <p class="text-sm text-slate-500 mt-1">Directory of all registered hypertensive, diabetic, and chronic lifestyle disease patients.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/ncd/tcl.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition">
        <i class="fas fa-table-list mr-1.5 text-xs"></i> View TCL-NCD
      </a>
      <a href="/HealthLogs/public/ncd/records/create.php" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold transition">
        <i class="fas fa-file-medical mr-1.5 text-xs"></i> Full Enrollment Form
      </a>
      <button type="button" onclick="openEnrollModal()" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-xs font-semibold shadow transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> Quick Enroll
      </button>
    </div>
  </div>
</div>

<?php display_flash_messages(); ?>

<div class="bg-white rounded-xl shadow p-4 sm:p-6">
  <!-- Search and Filter Bar -->
  <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <div class="lg:col-span-2">
      <label class="block text-xs font-semibold text-slate-600 mb-1">Search Patients</label>
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search NCD code, patient name, meds..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Diagnosis</label>
      <select name="diagnosis" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Diagnoses</option>
        <option value="hypertension" <?= $diagnosisFilter === 'hypertension' ? 'selected' : '' ?>>Hypertension</option>
        <option value="diabetes" <?= $diagnosisFilter === 'diabetes' ? 'selected' : '' ?>>Type 2 Diabetes</option>
        <option value="hypertension_diabetes" <?= $diagnosisFilter === 'hypertension_diabetes' ? 'selected' : '' ?>>HPN &amp; Diabetes</option>
        <option value="cardiovascular" <?= $diagnosisFilter === 'cardiovascular' ? 'selected' : '' ?>>Cardiovascular Disease</option>
        <option value="asthma_copd" <?= $diagnosisFilter === 'asthma_copd' ? 'selected' : '' ?>>Asthma / COPD</option>
        <option value="chronic_kidney" <?= $diagnosisFilter === 'chronic_kidney' ? 'selected' : '' ?>>Chronic Kidney Disease</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">PhilPEN Risk</label>
      <select name="risk" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Risk Levels</option>
        <option value="low" <?= $riskFilter === 'low' ? 'selected' : '' ?>>Low (&lt;10%)</option>
        <option value="moderate" <?= $riskFilter === 'moderate' ? 'selected' : '' ?>>Moderate (10-20%)</option>
        <option value="high" <?= $riskFilter === 'high' ? 'selected' : '' ?>>High (20-30%)</option>
        <option value="very_high" <?= $riskFilter === 'very_high' ? 'selected' : '' ?>>Very High (&ge;30%)</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Barangay</label>
      <select name="barangay" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Barangays</option>
        <?php foreach ($barangays as $b): ?>
          <option value="<?= h($b) ?>" <?= $barangayFilter === $b ? 'selected' : '' ?>><?= h($b) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="sm:col-span-2 lg:col-span-5 flex items-center justify-between pt-1">
      <div class="text-xs text-slate-500">
        Found <strong class="text-slate-800"><?= count($clients) ?></strong> NCD patient record(s)
      </div>
      <div class="flex items-center gap-2">
        <a href="/HealthLogs/public/ncd/records/index.php" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
          Reset Filters
        </a>
        <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
          Apply Filter
        </button>
      </div>
    </div>
  </form>

  <!-- Table View -->
  <div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 border-y border-slate-200">
        <tr>
          <th class="text-left px-4 py-3 font-semibold text-xs">Registry Code</th>
          <th class="text-left px-4 py-3 font-semibold text-xs">Patient Details</th>
          <th class="text-left px-4 py-3 font-semibold text-xs">Diagnosis &amp; Risk</th>
          <th class="text-left px-4 py-3 font-semibold text-xs">Maintenance Medications</th>
          <th class="text-left px-4 py-3 font-semibold text-xs">Last Vitals</th>
          <th class="text-left px-4 py-3 font-semibold text-xs">Status</th>
          <th class="text-right px-4 py-3 font-semibold text-xs">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($clients)): ?>
          <tr>
            <td colspan="7" class="text-center py-8 text-slate-400">
              <i class="fas fa-folder-open text-3xl mb-2"></i>
              <div>No NCD patients found matching the selected criteria.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($clients as $row): ?>
            <tr class="hover:bg-slate-50/80 transition">
              <td class="px-4 py-3.5 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$row['id'] ?>" class="text-teal-700 hover:underline">
                  <?= h($row['ncd_code']) ?>
                </a>
                <div class="text-[10px] text-slate-400 font-sans mt-0.5">
                  Reg: <?= date('M d, Y', strtotime($row['registration_date'])) ?>
                </div>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <div class="font-semibold text-slate-900">
                  <?= h($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . substr($row['middle_name'], 0, 1) . '.' : '')) ?>
                </div>
                <div class="text-xs text-slate-500">
                  <?= $row['age'] ?> yo &bull; <?= ucfirst($row['sex']) ?> &bull; Brgy. <?= h($row['barangay']) ?>
                </div>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <div class="flex flex-col gap-1 items-start">
                  <?= ncd_format_diag_badge($row['diagnosis_type']) ?>
                  <?= ncd_risk_badge($row['philpen_risk_level']) ?>
                </div>
              </td>

              <td class="px-4 py-3.5">
                <div class="text-xs text-slate-700 max-w-xs truncate" title="<?= h($row['maintenance_meds']) ?>">
                  <?= h($row['maintenance_meds'] ?: 'None recorded') ?>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                  Target BP: <?= h($row['target_bp'] ?: '<140/90') ?>
                </div>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <?php if ($row['last_bp_sys'] && $row['last_bp_dia']): ?>
                  <div class="text-xs font-medium text-slate-800">
                    BP: <strong><?= $row['last_bp_sys'] ?>/<?= $row['last_bp_dia'] ?></strong>
                  </div>
                  <?php if ($row['last_bs_mgdl']): ?>
                    <div class="text-[11px] text-teal-700">BS: <?= number_format($row['last_bs_mgdl'], 1) ?> mg/dL</div>
                  <?php endif; ?>
                  <div class="text-[10px] text-slate-400"><?= date('M d, Y', strtotime($row['last_visit_date'])) ?></div>
                <?php else: ?>
                  <span class="text-xs text-slate-400 italic">No visits yet</span>
                <?php endif; ?>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <?php
                  $statusClasses = [
                      'active' => 'bg-emerald-100 text-emerald-800',
                      'controlled' => 'bg-teal-100 text-teal-800',
                      'uncontrolled' => 'bg-rose-100 text-rose-800',
                      'inactive' => 'bg-slate-100 text-slate-600',
                  ];
                  $stClass = $statusClasses[$row['status']] ?? 'bg-slate-100 text-slate-700';
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $stClass ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
                <div class="text-[10px] text-slate-400 mt-0.5"><?= (int)$row['total_visits'] ?> consultation(s)</div>
              </td>

              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$row['id'] ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 text-xs font-semibold transition" title="Log Vitals &amp; Checkup">
                    <i class="fas fa-heart-pulse mr-1"></i> Log Checkup
                  </a>
                  <a href="/HealthLogs/public/ncd/records/edit.php?id=<?= (int)$row['id'] ?>" class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Edit Profile">
                    <i class="fas fa-pen-to-square"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= $paginator->render() ?>
</div>

<?php require __DIR__ . '/../_enroll_modal.php'; ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
