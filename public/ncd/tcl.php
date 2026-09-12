<?php
$pageTitle = 'Target Client List for Non-Communicable Diseases (TCL-NCD)';
require __DIR__ . '/../partials/bootstrap.php';

$export = ($_GET['export'] ?? '') === 'csv';
$q = trim($_GET['q'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$diagnosisFilter = trim($_GET['diagnosis'] ?? '');
$riskFilter = trim($_GET['risk'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$yearFilter = trim($_GET['year'] ?? date('Y'));

// Fetch barangays for filter dropdown
$barangays = [];
try {
    $barangays = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

// Query NCD Records
$whereClauses = ["1=1"];
$params = [];

if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
    $whereClauses[] = "YEAR(r.registration_date) = ?";
    $params[] = (int)$yearFilter;
}

if ($barangayFilter !== '') {
    $whereClauses[] = "p.barangay = ?";
    $params[] = $barangayFilter;
}

if ($diagnosisFilter !== '') {
    $whereClauses[] = "r.diagnosis_type = ?";
    $params[] = $diagnosisFilter;
}

if ($riskFilter !== '') {
    $whereClauses[] = "r.philpen_risk_level = ?";
    $params[] = $riskFilter;
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "r.status = ?";
    $params[] = $statusFilter;
}

if ($q !== '') {
    $whereClauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.middle_name LIKE ? OR r.ncd_code LIKE ? OR r.maintenance_meds LIKE ?)";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $whereClauses);

// Fetch summary stats for KPI cards
$stats = [
    'total' => 0,
    'hpn' => 0,
    'dm' => 0,
    'high_risk' => 0,
    'controlled' => 0,
    'due_overdue' => 0,
];

try {
    $statSql = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN r.diagnosis_type IN ('hypertension', 'hypertension_diabetes') THEN 1 ELSE 0 END) AS hpn,
            SUM(CASE WHEN r.diagnosis_type IN ('diabetes', 'hypertension_diabetes') THEN 1 ELSE 0 END) AS dm,
            SUM(CASE WHEN r.philpen_risk_level IN ('high', 'very_high') THEN 1 ELSE 0 END) AS high_risk,
            SUM(CASE WHEN r.status = 'controlled' THEN 1 ELSE 0 END) AS controlled
        FROM ncd_records r
        JOIN patients p ON p.id = r.patient_id
        WHERE {$whereSql}
    ";
    $statStmt = $pdo->prepare($statSql);
    $statStmt->execute($params);
    $statRow = $statStmt->fetch(PDO::FETCH_ASSOC);
    if ($statRow) {
        $stats['total'] = (int)($statRow['total'] ?? 0);
        $stats['hpn'] = (int)($statRow['hpn'] ?? 0);
        $stats['dm'] = (int)($statRow['dm'] ?? 0);
        $stats['high_risk'] = (int)($statRow['high_risk'] ?? 0);
        $stats['controlled'] = (int)($statRow['controlled'] ?? 0);
    }
} catch (Throwable $e) {}

$sql = "
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.birth_date, p.barangay, p.address_line, p.contact_no, p.sex,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age,
           (SELECT COUNT(*) FROM ncd_visits v WHERE v.ncd_record_id = r.id) AS total_visits,
           (SELECT MAX(visit_date) FROM ncd_visits v WHERE v.ncd_record_id = r.id) AS last_visit_date,
           (SELECT bp_systolic FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bp_sys,
           (SELECT bp_diastolic FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bp_dia,
           (SELECT blood_sugar_mgdl FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bs_mgdl,
           (SELECT blood_sugar_type FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bs_type,
           (SELECT weight_kg FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_weight_kg,
           (SELECT bmi FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS last_bmi,
           (SELECT next_appointment_date FROM ncd_visits v WHERE v.ncd_record_id = r.id ORDER BY visit_date DESC, id DESC LIMIT 1) AS next_appointment_date
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE {$whereSql}
    ORDER BY r.registration_date DESC, r.id DESC
";

$countQuery = "
    SELECT COUNT(*)
    FROM ncd_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE {$whereSql}
";

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();

// If exporting to CSV, fetch all matching rows without limit
if ($export) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
} else {
    // 15 records per page
    $tclPaginator = paginate($totalRecords, 15);
    $stmt = $pdo->prepare($sql . " " . $tclPaginator->getLimitSql());
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}

function tcl_ncd_diag_label(?string $d): string {
    $map = [
        'hypertension' => 'Hypertension (HPN)',
        'diabetes' => 'Type 2 Diabetes (DM)',
        'hypertension_diabetes' => 'HPN & Diabetes',
        'cardiovascular' => 'Cardiovascular Disease',
        'asthma_copd' => 'Asthma / COPD',
        'chronic_kidney' => 'Chronic Kidney Disease',
        'cancer' => 'Cancer Registry',
        'other' => 'Other NCD',
    ];
    return $map[$d] ?? ucwords(str_replace('_', ' ', (string)$d));
}

// Handle CSV Export
if ($export) {
    $filename = 'TCL_NCD_' . ($barangayFilter ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $barangayFilter) . '_' : '') . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    fputcsv($out, ['PHILIPPINE DEPARTMENT OF HEALTH - TARGET CLIENT LIST FOR NON-COMMUNICABLE DISEASES (TCL-NCD)']);
    fputcsv($out, ['Generated: ' . date('Y-m-d H:i:s'), 'Barangay: ' . ($barangayFilter ?: 'All Barangays'), 'Year: ' . $yearFilter]);
    fputcsv($out, []);

    fputcsv($out, [
        'No.',
        'NCD Code',
        'Date Registered',
        'Last Name',
        'First Name',
        'Middle Name',
        'Sex',
        'Age',
        'Date of Birth',
        'Barangay',
        'Contact No',
        'Primary Diagnosis',
        'Date Diagnosed',
        'PhilPEN Risk Level',
        'Smoker History',
        'Alcohol History',
        'Target BP',
        'Target FBS',
        'Maintenance Medications',
        'Last BP (mmHg)',
        'Last Blood Sugar (mg/dL)',
        'Last BMI',
        'Total Visits',
        'Last Visit Date',
        'Next Appointment',
        'Outcome Status',
    ]);

    foreach ($records as $i => $r) {
        $bp = ($r['last_bp_sys'] && $r['last_bp_dia']) ? "{$r['last_bp_sys']}/{$r['last_bp_dia']}" : 'N/A';
        $bs = $r['last_bs_mgdl'] ? "{$r['last_bs_mgdl']} (" . strtoupper($r['last_bs_type']) . ")" : 'N/A';

        fputcsv($out, [
            $i + 1,
            $r['ncd_code'],
            $r['registration_date'],
            $r['last_name'],
            $r['first_name'],
            $r['middle_name'],
            ucfirst($r['sex']),
            $r['age'],
            $r['birth_date'],
            $r['barangay'],
            $r['contact_no'] ?: 'N/A',
            tcl_ncd_diag_label($r['diagnosis_type']),
            $r['date_diagnosed'],
            ucfirst($r['philpen_risk_level']) . ' Risk',
            ucfirst($r['is_smoker']),
            ucfirst($r['is_alcohol_drinker']),
            $r['target_bp'] ?: '<140/90',
            $r['target_fbs'] ?: '<126 mg/dL',
            $r['maintenance_meds'] ?: 'None',
            $bp,
            $bs,
            $r['last_bmi'] ?: 'N/A',
            $r['total_visits'],
            $r['last_visit_date'] ?: 'N/A',
            $r['next_appointment_date'] ?: 'N/A',
            ucfirst($r['status']),
        ]);
    }

    fclose($out);
    exit;
}

require __DIR__ . '/../partials/header.php';
?>

<style>
  @media print {
    @page {
      size: landscape;
      margin: 8mm 6mm;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 9px !important;
    }
    .print\:hidden, #appSidebar, .app-topbar, header, nav {
      display: none !important;
    }
    .app-main {
      padding: 0 !important;
      margin: 0 !important;
    }
    .app-content {
      padding: 0 !important;
      max-width: 100% !important;
    }
    .tcl-table {
      width: 100% !important;
      border-collapse: collapse !important;
      font-size: 8px !important;
    }
    .tcl-table th, .tcl-table td {
      border: 1px solid #000 !important;
      padding: 3px 4px !important;
      color: #000 !important;
    }
    .tcl-header {
      display: block !important;
      text-align: center;
      margin-bottom: 8px;
    }
  }

  .tcl-table th {
    background-color: #f1f5f9;
    color: #1e293b;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #cbd5e1;
    font-size: 11px;
    line-height: 1.25;
    padding: 6px 4px;
  }
  .tcl-table td {
    border: 1px solid #e2e8f0;
    padding: 6px 5px;
    font-size: 11px;
    vertical-align: middle;
  }
  .tcl-table tr:hover td {
    background-color: #f8fafc;
  }
  .signatory-box {
    page-break-inside: avoid;
  }
</style>

<!-- Top Action Banner -->
<div class="bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100 print:hidden">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
        <i class="fas fa-heart-pulse text-indigo-600"></i> DOH PhilPEN Standard Register
      </div>
      <h2 class="text-2xl font-bold text-slate-900 mt-2">Target Client List for Non-Communicable Diseases (TCL-NCD)</h2>
      <p class="text-xs text-slate-500 mt-0.5">Official Department of Health (DOH) standard master register tracking Hypertension, Diabetes, CVD, Asthma, and lifestyle risk factors.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/ncd.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition">
        <i class="fas fa-arrow-left"></i> NCD Hub
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition">
        <i class="fas fa-file-csv text-emerald-600"></i> Export CSV
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-print"></i> Print TCL-NCD Register (Landscape)
      </button>
    </div>
  </div>

  <!-- KPI Summary Cards -->
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mt-5 pt-4 border-t border-slate-100">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">Registered Cohort</div>
      <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($stats['total']) ?></div>
      <div class="text-[10px] text-slate-400">Total enrolled NCD clients</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">Hypertension</div>
      <div class="text-2xl font-extrabold text-indigo-700 mt-1"><?= number_format($stats['hpn']) ?></div>
      <div class="text-[10px] text-slate-500">HPN &amp; combined cases</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">Type 2 Diabetes</div>
      <div class="text-2xl font-extrabold text-teal-700 mt-1"><?= number_format($stats['dm']) ?></div>
      <div class="text-[10px] text-slate-500">DM &amp; combined clients</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">High Risk (&ge;20%)</div>
      <div class="text-2xl font-extrabold text-rose-700 mt-1"><?= number_format($stats['high_risk']) ?></div>
      <div class="text-[10px] text-slate-500">PhilPEN high CVD risk</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 col-span-2 sm:col-span-1">
      <div class="text-[11px] uppercase font-bold text-slate-500">Controlled Cases</div>
      <div class="text-2xl font-extrabold text-emerald-700 mt-1"><?= number_format($stats['controlled']) ?></div>
      <div class="text-[10px] text-slate-500">Target BP / FBS achieved</div>
    </div>
  </div>
</div>

<!-- Search & Filter Controls -->
<form method="get" class="mt-4 bg-white p-4 rounded-xl shadow border border-slate-100 flex flex-col md:flex-row gap-3 print:hidden">
  <div class="flex-1">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search Patient / Code / Meds</label>
    <div class="relative">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name, NCD code, medications..." class="w-full border rounded-lg pl-9 pr-3 py-2 text-xs focus:ring-2 focus:ring-slate-400" />
      <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
    </div>
  </div>

  <div class="w-full md:w-44">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Barangay</label>
    <select name="barangay" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="">All Barangays</option>
      <?php foreach ($barangays as $b): ?>
        <option value="<?= h($b) ?>" <?= $barangayFilter === $b ? 'selected' : '' ?>><?= h($b) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="w-full md:w-40">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Diagnosis</label>
    <select name="diagnosis" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="">All Diagnoses</option>
      <option value="hypertension" <?= $diagnosisFilter === 'hypertension' ? 'selected' : '' ?>>Hypertension</option>
      <option value="diabetes" <?= $diagnosisFilter === 'diabetes' ? 'selected' : '' ?>>Type 2 Diabetes</option>
      <option value="hypertension_diabetes" <?= $diagnosisFilter === 'hypertension_diabetes' ? 'selected' : '' ?>>HPN &amp; Diabetes</option>
      <option value="cardiovascular" <?= $diagnosisFilter === 'cardiovascular' ? 'selected' : '' ?>>Cardiovascular</option>
      <option value="asthma_copd" <?= $diagnosisFilter === 'asthma_copd' ? 'selected' : '' ?>>Asthma / COPD</option>
      <option value="chronic_kidney" <?= $diagnosisFilter === 'chronic_kidney' ? 'selected' : '' ?>>Chronic Kidney</option>
    </select>
  </div>

  <div class="w-full md:w-36">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">PhilPEN Risk</label>
    <select name="risk" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="">All Risk Levels</option>
      <option value="low" <?= $riskFilter === 'low' ? 'selected' : '' ?>>Low (&lt;10%)</option>
      <option value="moderate" <?= $riskFilter === 'moderate' ? 'selected' : '' ?>>Moderate (10-20%)</option>
      <option value="high" <?= $riskFilter === 'high' ? 'selected' : '' ?>>High (20-30%)</option>
      <option value="very_high" <?= $riskFilter === 'very_high' ? 'selected' : '' ?>>Very High (&ge;30%)</option>
    </select>
  </div>

  <div class="w-full md:w-32">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Year</label>
    <select name="year" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-slate-400 bg-white">
      <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
      <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
        <option value="<?= $y ?>" <?= (string)$yearFilter === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="flex items-end gap-2">
    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow transition">Filter</button>
    <a href="/HealthLogs/public/ncd/tcl.php" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs">Reset</a>
  </div>
</form>

<!-- Printable DOH Header & Official TCL Table -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow border border-slate-200">
  <div class="text-center pb-4 mb-3 border-b border-slate-200">
    <div class="text-[11px] uppercase tracking-widest text-slate-500 font-semibold">Republic of the Philippines &bull; Department of Health</div>
    <h1 class="text-lg sm:text-xl font-extrabold uppercase text-slate-900 tracking-wider mt-0.5">TARGET CLIENT LIST FOR NON-COMMUNICABLE DISEASES (TCL-NCD)</h1>
    <div class="text-xs text-slate-600 mt-1 flex items-center justify-center gap-4 flex-wrap">
      <span>Barangay: <strong><?= $barangayFilter ?: 'All Barangays' ?></strong></span>
      <span>&bull;</span>
      <span>Calendar Year: <strong><?= $yearFilter === 'all' ? 'All Records' : $yearFilter ?></strong></span>
      <span>&bull;</span>
      <span>Date Generated: <strong><?= date('F d, Y') ?></strong></span>
    </div>
  </div>

  <!-- Official DOH TCL Multi-Header Format Table -->
  <div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="w-full tcl-table min-w-[1360px]">
      <thead>
        <!-- Master Group Row 1 -->
        <tr>
          <th rowspan="2" class="w-8">No.</th>
          <th rowspan="2" class="min-w-[100px]">NCD Code</th>
          <th rowspan="2" class="min-w-[75px]">Date Reg.<br><span class="text-[9px] font-normal text-slate-500">(mm/dd/yy)</span></th>
          
          <th colspan="4" class="bg-slate-100 text-slate-800 font-extrabold uppercase tracking-wide py-1">
            I. Client / Patient Profile
          </th>
          
          <th colspan="4" class="bg-indigo-50 text-indigo-900 font-extrabold uppercase tracking-wide py-1">
            II. PhilPEN Assessment &amp; Diagnosis
          </th>
          
          <th colspan="3" class="bg-slate-100 text-slate-800 font-extrabold uppercase tracking-wide py-1">
            III. Target &amp; Prescribed Regimen
          </th>
          
          <th colspan="4" class="bg-teal-50 text-teal-900 font-extrabold uppercase tracking-wide py-1">
            IV. Latest Clinical Vitals Log
          </th>
          
          <th colspan="3" class="bg-slate-100 text-slate-800 font-extrabold uppercase tracking-wide py-1">
            V. Status &amp; Follow-up
          </th>
        </tr>

        <!-- Sub-Header Row 2 -->
        <tr>
          <!-- Patient Profile -->
          <th class="min-w-[150px] text-left px-2">Full Name</th>
          <th class="w-12">Age</th>
          <th class="w-10">Sex</th>
          <th class="min-w-[120px]">Barangay / Contact</th>

          <!-- PhilPEN Assessment -->
          <th class="min-w-[130px]">Primary Diagnosis</th>
          <th class="min-w-[75px]">Diagnosed</th>
          <th class="min-w-[90px]">CVD Risk Level</th>
          <th class="min-w-[90px]">Lifestyle Factors</th>

          <!-- Target & Regimen -->
          <th class="min-w-[75px]">Target BP</th>
          <th class="min-w-[85px]">Target FBS</th>
          <th class="min-w-[150px] text-left px-2">Maintenance Medications</th>

          <!-- Vitals -->
          <th class="min-w-[85px]">BP (mmHg)</th>
          <th class="min-w-[85px]">Blood Sugar</th>
          <th class="min-w-[75px]">BMI</th>
          <th class="min-w-[75px]">Last Visit</th>

          <!-- Status & Actions -->
          <th class="min-w-[85px]">Next Refill</th>
          <th class="min-w-[75px]">Status</th>
          <th class="w-16 print:hidden">Action</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($records)): ?>
          <tr>
            <td colspan="20" class="text-center py-12 text-slate-400">
              <i class="fas fa-folder-open text-3xl mb-2"></i>
              <div>No TCL-NCD records found matching the active filter criteria.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($records as $idx => $r): ?>
            <?php
              $isOverdue = (!empty($r['next_appointment_date']) && $r['next_appointment_date'] < date('Y-m-d'));
            ?>
            <tr class="hover:bg-slate-50 transition">
              <!-- No. -->
              <td class="text-center font-mono text-slate-400 font-bold"><?= $idx + 1 ?></td>

              <!-- NCD Code -->
              <td class="font-mono font-bold text-teal-700 whitespace-nowrap text-center">
                <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$r['id'] ?>" class="hover:underline">
                  <?= h($r['ncd_code']) ?>
                </a>
              </td>

              <!-- Date Reg. -->
              <td class="text-center font-mono text-[10px] text-slate-600 whitespace-nowrap">
                <?= date('m/d/y', strtotime($r['registration_date'])) ?>
              </td>

              <!-- Patient Full Name -->
              <td class="text-left px-2 font-semibold text-slate-900 whitespace-nowrap">
                <?= h($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . substr($r['middle_name'], 0, 1) . '.' : '')) ?>
              </td>

              <!-- Age -->
              <td class="text-center font-medium text-slate-700"><?= $r['age'] ?></td>

              <!-- Sex -->
              <td class="text-center font-bold text-slate-700"><?= strtoupper(substr($r['sex'], 0, 1)) ?></td>

              <!-- Barangay & Contact -->
              <td class="text-center text-[10px] text-slate-600 whitespace-nowrap">
                <div class="font-medium">Brgy. <?= h($r['barangay']) ?></div>
                <div class="text-slate-400 font-mono"><?= h($r['contact_no'] ?: '—') ?></div>
              </td>

              <!-- Diagnosis -->
              <td class="text-center font-semibold text-slate-800 whitespace-nowrap">
                <?= tcl_ncd_diag_label($r['diagnosis_type']) ?>
              </td>

              <!-- Date Diagnosed -->
              <td class="text-center font-mono text-[10px] text-slate-500 whitespace-nowrap">
                <?= date('m/d/y', strtotime($r['date_diagnosed'])) ?>
              </td>

              <!-- PhilPEN CVD Risk Level -->
              <td class="text-center whitespace-nowrap">
                <?php if ($r['philpen_risk_level'] === 'very_high'): ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">&ge;30% (Very High)</span>
                <?php elseif ($r['philpen_risk_level'] === 'high'): ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">20-30% (High)</span>
                <?php elseif ($r['philpen_risk_level'] === 'moderate'): ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">10-20% (Mod)</span>
                <?php else: ?>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">&lt;10% (Low)</span>
                <?php endif; ?>
              </td>

              <!-- Lifestyle -->
              <td class="text-center text-[10px] text-slate-600 whitespace-nowrap">
                <span>Smoke: <strong class="<?= $r['is_smoker'] === 'current' ? 'text-rose-600 font-bold' : '' ?>"><?= ucfirst(substr($r['is_smoker'], 0, 1)) ?></strong></span>
                <span class="text-slate-300">|</span>
                <span>Alc: <strong class="<?= $r['is_alcohol_drinker'] === 'heavy' ? 'text-rose-600 font-bold' : '' ?>"><?= ucfirst(substr($r['is_alcohol_drinker'], 0, 1)) ?></strong></span>
              </td>

              <!-- Target BP -->
              <td class="text-center font-mono font-medium text-slate-700 text-[10px]">
                <?= h($r['target_bp'] ?: '<140/90') ?>
              </td>

              <!-- Target FBS -->
              <td class="text-center font-mono font-medium text-slate-700 text-[10px]">
                <?= h($r['target_fbs'] ?: '<126 mg/dL') ?>
              </td>

              <!-- Maintenance Medications -->
              <td class="text-left px-2 text-[10px] text-slate-700 max-w-xs truncate" title="<?= h($r['maintenance_meds']) ?>">
                <?= h($r['maintenance_meds'] ?: 'None recorded') ?>
              </td>

              <!-- Last BP -->
              <td class="text-center font-mono font-bold whitespace-nowrap">
                <?php if ($r['last_bp_sys'] && $r['last_bp_dia']): ?>
                  <span class="<?= ($r['last_bp_sys'] >= 140 || $r['last_bp_dia'] >= 90) ? 'text-rose-600 font-extrabold' : 'text-emerald-700' ?>">
                    <?= $r['last_bp_sys'] ?>/<?= $r['last_bp_dia'] ?>
                  </span>
                <?php else: ?>
                  <span class="text-slate-300 italic">—</span>
                <?php endif; ?>
              </td>

              <!-- Last Blood Sugar -->
              <td class="text-center font-mono whitespace-nowrap text-[10px]">
                <?php if ($r['last_bs_mgdl']): ?>
                  <span class="font-bold <?= $r['last_bs_mgdl'] >= 126 ? 'text-amber-700' : 'text-teal-700' ?>">
                    <?= number_format($r['last_bs_mgdl'], 1) ?>
                  </span>
                  <span class="text-[9px] text-slate-400">(<?= strtoupper($r['last_bs_type']) ?>)</span>
                <?php else: ?>
                  <span class="text-slate-300 italic">—</span>
                <?php endif; ?>
              </td>

              <!-- Last BMI -->
              <td class="text-center font-mono text-[10px]">
                <?= $r['last_bmi'] ? '<strong>' . $r['last_bmi'] . '</strong>' : '<span class="text-slate-300">—</span>' ?>
              </td>

              <!-- Last Visit Date -->
              <td class="text-center font-mono text-[10px] text-slate-600 whitespace-nowrap">
                <?= $r['last_visit_date'] ? date('m/d/y', strtotime($r['last_visit_date'])) : '<span class="text-slate-300">—</span>' ?>
              </td>

              <!-- Next Appointment / Refill -->
              <td class="text-center font-mono text-[10px] whitespace-nowrap">
                <?php if (!empty($r['next_appointment_date'])): ?>
                  <span class="font-bold <?= $isOverdue ? 'text-rose-600' : 'text-slate-800' ?>">
                    <?= date('m/d/y', strtotime($r['next_appointment_date'])) ?>
                  </span>
                  <?php if ($isOverdue): ?>
                    <span class="text-[9px] text-rose-500 font-extrabold block">OVERDUE</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-slate-300">—</span>
                <?php endif; ?>
              </td>

              <!-- Status -->
              <td class="text-center whitespace-nowrap">
                <?php
                  $stColors = [
                      'active' => 'bg-emerald-100 text-emerald-800',
                      'controlled' => 'bg-teal-100 text-teal-800',
                      'uncontrolled' => 'bg-rose-100 text-rose-800',
                      'inactive' => 'bg-slate-100 text-slate-600',
                  ];
                  $color = $stColors[$r['status']] ?? 'bg-slate-100 text-slate-700';
                ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $color ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>

              <!-- Action -->
              <td class="text-center whitespace-nowrap print:hidden">
                <a href="/HealthLogs/public/ncd/visits/index.php?record_id=<?= (int)$r['id'] ?>" class="inline-flex items-center px-2 py-1 rounded bg-teal-50 text-teal-700 hover:bg-teal-100 font-bold text-[10px] transition" title="Log Clinical Vitals &amp; Checkup">
                  <i class="fas fa-heart-pulse mr-1"></i> Log
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php if (isset($tclPaginator)): ?>
    <div class="print:hidden">
      <?= $tclPaginator->render() ?>
    </div>
  <?php endif; ?>

  <!-- Official DOH Signatories Block (Visible in Print or Export) -->
  <div class="signatory-box mt-10 pt-6 border-t-2 border-slate-900 grid grid-cols-3 gap-8 text-center text-xs">
    <div>
      <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-8">Prepared by:</div>
      <div class="font-bold uppercase text-slate-900 border-b border-slate-900 pb-1">
        <?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Barangay Health Worker') ?>
      </div>
      <div class="text-[10px] text-slate-500 mt-1">Barangay Health Worker / Clinic Nurse</div>
    </div>
    <div>
      <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-8">Verified by:</div>
      <div class="font-bold uppercase text-slate-900 border-b border-slate-900 pb-1">
        _____________________________________
      </div>
      <div class="text-[10px] text-slate-500 mt-1">Supervising Public Health Nurse (PHN)</div>
    </div>
    <div>
      <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-8">Approved by:</div>
      <div class="font-bold uppercase text-slate-900 border-b border-slate-900 pb-1">
        _____________________________________
      </div>
      <div class="text-[10px] text-slate-500 mt-1">Municipal Health Officer / Physician (MHO)</div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
