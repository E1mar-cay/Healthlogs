<?php
$pageTitle = 'NCD Patient Registry';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$search = trim($_GET['search'] ?? '');
$diagnosisFilter = trim($_GET['diagnosis'] ?? '');
$riskFilter = trim($_GET['risk'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$barangayFilter = trim($_GET['barangay'] ?? '');

$puroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

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
  $where .= " AND (r.ncd_code LIKE :ncd_code_search OR p.first_name LIKE :first_name_search OR p.last_name LIKE :last_name_search OR r.maintenance_meds LIKE :maintenance_search)";
  $searchValue = "%{$search}%";
  $params['ncd_code_search'] = $searchValue;
  $params['first_name_search'] = $searchValue;
  $params['last_name_search'] = $searchValue;
  $params['maintenance_search'] = $searchValue;
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

// Create paginator (20 records per page)
$paginator = paginate($totalRecords, 20);

$dataStmt = $pdo->prepare($dataQuery . $where . " ORDER BY r.created_at DESC " . $paginator->getLimitSql());
$dataStmt->execute($params);
$clients = $dataStmt->fetchAll();
$dashboardStats = $pdo->query("
    SELECT
        COUNT(*) AS total_clients,
        SUM(diagnosis_type IN ('hypertension', 'hypertension_diabetes')) AS hypertension_count,
        SUM(diagnosis_type IN ('diabetes', 'hypertension_diabetes')) AS diabetes_count,
        SUM(philpen_risk_level IN ('high', 'very_high')) AS high_risk_count
    FROM ncd_records
    WHERE status IN ('active', 'controlled', 'uncontrolled')
")->fetch(PDO::FETCH_ASSOC);

function ncd_format_diag_badge(?string $d): string {
    $class = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200';
    $map = [
        'hypertension' => 'Hypertension',
        'diabetes' => 'Diabetes',
        'hypertension_diabetes' => 'HPN &amp; Diabetes',
        'cardiovascular' => 'CVD / CAD',
        'asthma_copd' => 'Asthma/COPD',
        'chronic_kidney' => 'Chronic Kidney',
        'cancer' => 'Cancer',
        'other' => 'Other NCD',
    ];
    return '<span class="' . $class . '">' . ($map[$d] ?? htmlspecialchars((string)$d)) . '</span>';
}

function ncd_risk_badge(?string $r): string {
    $label = match ($r) {
        'very_high' => '&ge;30% (Very High)',
        'high' => '20-30% (High)',
        'moderate' => '10-20% (Mod)',
        default => '&lt;10% (Low)',
    };
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white text-slate-600 border border-slate-300">' . $label . '</span>';
}
?>

<div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 mb-5">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Non-Communicable Disease Program</div>
      <div class="text-2xl font-semibold text-slate-900 mt-1">Patient Registry</div>
      <p class="text-sm text-slate-500 mt-1">Manage registered patients and review their diagnoses, risk, and latest visits.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/ncd/tcl.php" class="inline-flex items-center px-3.5 py-2 rounded-xl border border-teal-200 bg-teal-50/70 text-teal-800 hover:bg-teal-100 text-xs font-semibold transition">
        <i class="fas fa-table-list mr-1.5 text-xs text-teal-600"></i> Target Client List (TCL)
      </a>
      <a href="/HealthLogs/public/ncd/visits/index.php" class="inline-flex items-center px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold transition">
        <i class="fas fa-heart-pulse mr-1.5 text-xs"></i> Visits &amp; Vitals
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" onclick="openEnrollModal()" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 text-xs font-semibold transition">
          <i class="fas fa-user-plus mr-1.5 text-xs"></i> Enroll Patient
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php display_flash_messages(); ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Registered Patients</div>
    <div class="mt-1 text-2xl font-semibold text-slate-900"><?= number_format((int)$dashboardStats['total_clients']) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Hypertension</div>
    <div class="mt-1 text-2xl font-semibold text-slate-900"><?= number_format((int)$dashboardStats['hypertension_count']) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">Diabetes</div>
    <div class="mt-1 text-2xl font-semibold text-slate-900"><?= number_format((int)$dashboardStats['diabetes_count']) ?></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="text-xs text-slate-500">High Risk</div>
    <div class="mt-1 text-2xl font-semibold text-slate-900"><?= number_format((int)$dashboardStats['high_risk_count']) ?></div>
  </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
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
      <label class="block text-xs font-semibold text-slate-600 mb-1">Purok (Brgy. Tangcul)</label>
      <select name="barangay" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Puroks</option>
        <?php foreach ($puroks as $p): ?>
          <option value="<?= h($p) ?>" <?= $barangayFilter === $p ? 'selected' : '' ?>><?= h($p) ?></option>
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
                <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$row['id'] ?>" class="text-slate-700 hover:text-slate-900 hover:underline">
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
                  <?= $row['age'] ?> yo &bull; <?= ucfirst($row['sex']) ?> &bull; <?= h($row['barangay'] ?: 'Brgy. Tangcul') ?>
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
                <?php if (!empty($row['target_bp'])): ?>
                  <div class="text-[11px] text-slate-500 mt-0.5">
                    BP: <?= h($row['target_bp']) ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($row['target_fbs'])): ?>
                  <div class="text-[11px] text-slate-500 mt-0.5">
                    FBS: <?= h($row['target_fbs']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <?php if ($row['last_bp_sys'] && $row['last_bp_dia']): ?>
                  <div class="text-xs font-medium text-slate-800">
                    BP: <strong><?= $row['last_bp_sys'] ?>/<?= $row['last_bp_dia'] ?></strong>
                  </div>
                  <?php if ($row['last_bs_mgdl']): ?>
                    <div class="text-[11px] text-slate-600">BS: <?= number_format($row['last_bs_mgdl'], 1) ?> mg/dL</div>
                  <?php endif; ?>
                  <div class="text-[10px] text-slate-400"><?= date('M d, Y', strtotime($row['last_visit_date'])) ?></div>
                <?php else: ?>
                  <span class="text-xs text-slate-400 italic">No visits yet</span>
                <?php endif; ?>
              </td>

              <td class="px-4 py-3.5 whitespace-nowrap">
                <?php
                  $stClass = 'bg-slate-100 text-slate-700 border border-slate-200';
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $stClass ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
                <div class="text-[10px] text-slate-400 mt-0.5"><?= (int)$row['total_visits'] ?> consultation(s)</div>
              </td>

              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$row['id'] ?>" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold transition" title="<?= can_manage_clinical_records() ? 'Log Vitals & Checkup' : 'View Checkup History' ?>">
                    <?= can_manage_clinical_records() ? 'Log Checkup' : 'Visits' ?>
                  </a>
                  <?php if (can_manage_clinical_records()): ?>
                    <a href="/HealthLogs/public/ncd/records/edit.php?id=<?= (int)$row['id'] ?>" class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Edit Profile">
                      <i class="fas fa-pen-to-square"></i>
                    </a>
                  <?php endif; ?>
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
