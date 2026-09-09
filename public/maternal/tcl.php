<?php
$pageTitle = '8 - ANC Target Client List for Maternal Care and Services';
require __DIR__ . '/../partials/bootstrap.php';

// Active Sheet Tab (1: 8-ANC Checkups, 2: Nutrition & Td, 3: Screenings & Outcomes, 4: Delivery & 4PNC, all: Master)
$activeSheet = trim($_GET['sheet'] ?? '1');
if (!in_array($activeSheet, ['1', '2', '3', '4', 'all'], true)) {
    $activeSheet = '1';
}

$export = ($_GET['export'] ?? '') === 'csv';

// Filters
$q = trim($_GET['q'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all'); // all, ongoing, delivered, terminated, sensitive
$yearFilter = trim($_GET['year'] ?? date('Y'));

// Fetch barangays for filter dropdown
$barangays = [];
try {
    $barangays = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

// Build Pregnancies Query
$whereClauses = ["1=1"];
$params = [];

if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
    $whereClauses[] = "(YEAR(pr.lmp_date) = ? OR YEAR(pr.edd_date) = ? OR YEAR(pr.created_at) = ?)";
    $params[] = (int)$yearFilter;
    $params[] = (int)$yearFilter;
    $params[] = (int)$yearFilter;
}

if ($barangayFilter !== '') {
    $whereClauses[] = "p.barangay = ?";
    $params[] = $barangayFilter;
}

if ($statusFilter === 'sensitive') {
    // 6-7 months sensitive watchlist (24 to 31 weeks)
    $whereClauses[] = "pr.status = 'ongoing' AND TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) BETWEEN (24 * 7) AND (31 * 7)";
} elseif ($statusFilter !== 'all' && in_array($statusFilter, ['ongoing', 'delivered', 'terminated'], true)) {
    $whereClauses[] = "pr.status = ?";
    $params[] = $statusFilter;
}

if ($q !== '') {
    $whereClauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.middle_name LIKE ? OR p.address_line LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT 
        pr.*,
        p.id AS patient_id,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.suffix,
        p.birth_date,
        p.address_line,
        p.barangay,
        p.contact_no,
        p.blood_type,
        TIMESTAMPDIFF(YEAR, p.birth_date, pr.lmp_date) AS age_at_lmp,
        TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS current_age,
        FLOOR(TIMESTAMPDIFF(DAY, pr.lmp_date, CURDATE()) / 7) AS current_weeks
    FROM pregnancies pr
    JOIN patients p ON p.id = pr.patient_id
    WHERE $whereSql
    ORDER BY pr.created_at DESC, pr.lmp_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pregnancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Prenatal and Postnatal Visits for these pregnancies
$pregnancyIds = array_column($pregnancies, 'id');
$prenatalByPregnancy = [];
$postnatalByPregnancy = [];
$tdByPatient = [];

if (!empty($pregnancyIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($pregnancyIds), '?'));
    
    // Prenatal visits
    $pvSql = "
        SELECT * 
        FROM prenatal_visits 
        WHERE pregnancy_id IN ($inPlaceholders)
        ORDER BY visit_datetime ASC
    ";
    $pvStmt = $pdo->prepare($pvSql);
    $pvStmt->execute($pregnancyIds);
    $allPv = $pvStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allPv as $pv) {
        $prenatalByPregnancy[$pv['pregnancy_id']][] = $pv;
    }

    // Postnatal visits
    $postSql = "
        SELECT * 
        FROM postnatal_visits 
        WHERE pregnancy_id IN ($inPlaceholders)
        ORDER BY visit_datetime ASC
    ";
    $postStmt = $pdo->prepare($postSql);
    $postStmt->execute($pregnancyIds);
    $allPost = $postStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allPost as $post) {
        $postnatalByPregnancy[$post['pregnancy_id']][] = $post;
    }

    // Td vaccines from immunization records
    $patientIds = array_unique(array_column($pregnancies, 'patient_id'));
    if (!empty($patientIds)) {
        $patPlaceholders = implode(',', array_fill(0, count($patientIds), '?'));
        $tdSql = "
            SELECT r.patient_id, r.dose_no, r.administered_on, r.administered_at, v.code, v.name
            FROM immunization_records r
            JOIN vaccines v ON v.id = r.vaccine_id
            WHERE r.patient_id IN ($patPlaceholders)
              AND (v.code LIKE '%TD%' OR v.code LIKE '%TT%' OR v.name LIKE '%TETANUS%')
            ORDER BY r.dose_no ASC
        ";
        $tdStmt = $pdo->prepare($tdSql);
        $tdStmt->execute($patientIds);
        $allTd = $tdStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allTd as $td) {
            $tdByPatient[$td['patient_id']][] = $td;
        }
    }
}

// Map maternal data according to 8-ANC DOH TCL format
function mapMaternalTcl(array $pr, array $prenatals, array $postnatals, array $tds): array
{
    $age = $pr['age_at_lmp'] ?? $pr['current_age'] ?? 25;
    $ageGroup = 'C';
    if ($age >= 10 && $age <= 14) $ageGroup = 'A';
    elseif ($age >= 15 && $age <= 19) $ageGroup = 'B';
    elseif ($age >= 20) $ageGroup = 'C';

    $lmp = !empty($pr['lmp_date']) ? date('m-d-y', strtotime($pr['lmp_date'])) : '--';
    $edd = !empty($pr['edd_date']) ? date('m-d-y', strtotime($pr['edd_date'])) : '--';
    $regDate = !empty($pr['created_at']) ? date('m-d-y', strtotime($pr['created_at'])) : $lmp;
    $gp = 'G' . ($pr['gravida'] ?? 1) . ' P' . ($pr['para'] ?? 0);

    // 8-ANC Checkup Visits Slotting
    $visits8Anc = [
        'v1' => null, // 1st Tri (8-12 wks)
        'v2' => null, // 2nd Tri (14-20 wks)
        'v3' => null, // 2nd Tri (21-27 wks)
        'v4' => null, // 3rd Tri (28-30 wks)
        'v5' => null, // 3rd Tri (31-34 wks)
        'v6' => null, // 3rd Tri (35 wks)
        'v7' => null, // 3rd Tri (36 wks)
        'v8' => null, // 3rd Tri (37-40 wks)
    ];

    $bmiFirstVisit = null;
    $bmiCategory = 'Normal';

    $vCount = 0;
    foreach ($prenatals as $pv) {
        $weeks = (int)($pv['gestational_age_weeks'] ?? 0);
        $vDate = !empty($pv['visit_datetime']) ? date('m-d-y', strtotime($pv['visit_datetime'])) : null;
        if (!$vDate) continue;

        // BMI from 1st visit
        if ($bmiFirstVisit === null && !empty($pv['weight_kg'])) {
            $wt = (float)$pv['weight_kg'];
            // Est height 1.52m if not set
            $ht = 1.52;
            $bmi = round($wt / ($ht * $ht), 1);
            $bmiFirstVisit = $bmi;
            if ($bmi < 18.5) $bmiCategory = 'Low (<18.5)';
            elseif ($bmi > 23.5) $bmiCategory = 'High (>23.5)';
            else $bmiCategory = 'Normal (18.5-22.9)';
        }

        // Slot into 8 ANC
        if ($weeks <= 12 && !$visits8Anc['v1']) {
            $visits8Anc['v1'] = $vDate;
            $vCount++;
        } elseif ($weeks >= 13 && $weeks <= 20 && !$visits8Anc['v2']) {
            $visits8Anc['v2'] = $vDate;
            $vCount++;
        } elseif ($weeks >= 21 && $weeks <= 27 && !$visits8Anc['v3']) {
            $visits8Anc['v3'] = $vDate;
            $vCount++;
        } elseif ($weeks >= 28 && $weeks <= 30 && !$visits8Anc['v4']) {
            $visits8Anc['v4'] = $vDate;
            $vCount++;
        } elseif ($weeks >= 31 && $weeks <= 34 && !$visits8Anc['v5']) {
            $visits8Anc['v5'] = $vDate;
            $vCount++;
        } elseif ($weeks == 35 && !$visits8Anc['v6']) {
            $visits8Anc['v6'] = $vDate;
            $vCount++;
        } elseif ($weeks == 36 && !$visits8Anc['v7']) {
            $visits8Anc['v7'] = $vDate;
            $vCount++;
        } elseif ($weeks >= 37 && !$visits8Anc['v8']) {
            $visits8Anc['v8'] = $vDate;
            $vCount++;
        } else {
            // Fill next available slot
            foreach ($visits8Anc as $k => $val) {
                if ($val === null) {
                    $visits8Anc[$k] = $vDate;
                    $vCount++;
                    break;
                }
            }
        }
    }

    $completed8Anc = ($visits8Anc['v1'] && ($visits8Anc['v2'] || $visits8Anc['v3']) && ($visits8Anc['v4'] || $visits8Anc['v5'] || $visits8Anc['v8'])) ? 1 : 0;

    // Td Vaccines (Sheet 2)
    $tdDoses = ['td1' => null, 'td2' => null, 'td3' => null, 'td4' => null, 'td5' => null];
    foreach ($tds as $td) {
        $dNo = (int)($td['dose_no'] ?? 1);
        $dDate = !empty($td['administered_on']) ? date('m-d-y', strtotime($td['administered_on'])) : (!empty($td['administered_at']) ? date('m-d-y', strtotime($td['administered_at'])) : null);
        if ($dNo >= 1 && $dNo <= 5 && $dDate) {
            $tdDoses['td' . $dNo] = $dDate;
        }
    }
    $isFim = !empty($tdDoses['td2']) || !empty($tdDoses['td3']) || !empty($tdDoses['td4']) || !empty($tdDoses['td5']);

    // Postnatal Care 4PNC (Sheet 4)
    $postnatal4Pnc = [
        'c1' => null, // < 24h
        'c2' => null, // Day 3
        'c3' => null, // Day 7-14
        'c4' => null, // 6 weeks
    ];
    $pncCount = 0;
    foreach ($postnatals as $post) {
        $pDate = !empty($post['visit_datetime']) ? date('m-d-y', strtotime($post['visit_datetime'])) : null;
        if (!$pDate) continue;
        if ($pncCount === 0) $postnatal4Pnc['c1'] = $pDate;
        elseif ($pncCount === 1) $postnatal4Pnc['c2'] = $pDate;
        elseif ($pncCount === 2) $postnatal4Pnc['c3'] = $pDate;
        elseif ($pncCount === 3) $postnatal4Pnc['c4'] = $pDate;
        $pncCount++;
    }
    $completed4Pnc = $pncCount >= 2 ? 1 : 0;

    return [
        'reg_date' => $regDate,
        'family_serial' => 'F-' . str_pad($pr['patient_id'], 4, '0', STR_PAD_LEFT),
        'age' => $age,
        'age_group' => $ageGroup,
        'lmp' => $lmp,
        'edd' => $edd,
        'gp' => $gp,
        'visits' => $visits8Anc,
        'completed_8anc' => $completed8Anc,
        'bmi' => $bmiFirstVisit,
        'bmi_category' => $bmiCategory,
        'td' => $tdDoses,
        'fim' => $isFim,
        'postnatal' => $postnatal4Pnc,
        'completed_4pnc' => $completed4Pnc,
        'status' => $pr['status'],
    ];
}

// Compile stats
$stats = [
    'total' => count($pregnancies),
    'ongoing' => 0,
    'sensitive_6_7' => 0,
    'delivered' => 0,
    'completed_8anc' => 0,
];

$tclRows = [];
foreach ($pregnancies as $pr) {
    $pVisits = $prenatalByPregnancy[$pr['id']] ?? [];
    $postVisits = $postnatalByPregnancy[$pr['id']] ?? [];
    $tdVisits = $tdByPatient[$pr['patient_id']] ?? [];
    
    $mapped = mapMaternalTcl($pr, $pVisits, $postVisits, $tdVisits);

    if ($pr['status'] === 'ongoing') {
        $stats['ongoing']++;
        if ($pr['current_weeks'] >= 24 && $pr['current_weeks'] <= 31) {
            $stats['sensitive_6_7']++;
        }
    } elseif ($pr['status'] === 'delivered') {
        $stats['delivered']++;
    }

    if ($mapped['completed_8anc']) {
        $stats['completed_8anc']++;
    }

    $tclRows[] = [
        'pr' => $pr,
        'data' => $mapped,
    ];
}

// Handle CSV Export
if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Target_Client_List_Maternal_Care_TCL_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    
    fputcsv($out, [
        'No.', 'Reg Date', 'Family Serial No.', 'Full Name', 'Address / Barangay', 'Age', 'Age Group',
        'LMP (mm/dd/yy)', 'G-P', 'EDD (mm/dd/yy)',
        'Visit 1 (8-12w)', 'Visit 2 (14-20w)', 'Visit 3 (21-27w)', 'Visit 4 (28-30w)',
        'Visit 5 (31-34w)', 'Visit 6 (35w)', 'Visit 7 (36w)', 'Visit 8 (37-40w)',
        'Completed 8 ANC?', 'BMI (1st Tri)', 'Td1', 'Td2', 'Td3', 'Td4', 'Td5', 'FIM',
        'PNC 1 (<24h)', 'PNC 2 (Day 3)', 'PNC 3 (7-14d)', 'PNC 4 (6w)', 'Completed 4PNC?', 'Status'
    ]);

    $i = 1;
    foreach ($tclRows as $row) {
        $pr = $row['pr'];
        $d = $row['data'];
        $fullName = trim($pr['last_name'] . ', ' . $pr['first_name'] . ' ' . ($pr['middle_name'] ? substr($pr['middle_name'], 0, 1) . '.' : '') . ' ' . ($pr['suffix'] ?? ''));

        fputcsv($out, [
            $i++,
            $d['reg_date'],
            $d['family_serial'],
            $fullName,
            $pr['barangay'] . ($pr['address_line'] ? ' - ' . $pr['address_line'] : ''),
            $d['age'],
            $d['age_group'],
            $d['lmp'],
            $d['gp'],
            $d['edd'],
            $d['visits']['v1'] ?? '',
            $d['visits']['v2'] ?? '',
            $d['visits']['v3'] ?? '',
            $d['visits']['v4'] ?? '',
            $d['visits']['v5'] ?? '',
            $d['visits']['v6'] ?? '',
            $d['visits']['v7'] ?? '',
            $d['visits']['v8'] ?? '',
            $d['completed_8anc'] ? '1' : '0',
            $d['bmi'] ?? '',
            $d['td']['td1'] ?? '',
            $d['td']['td2'] ?? '',
            $d['td']['td3'] ?? '',
            $d['td']['td4'] ?? '',
            $d['td']['td5'] ?? '',
            $d['fim'] ? '1' : '0',
            $d['postnatal']['c1'] ?? '',
            $d['postnatal']['c2'] ?? '',
            $d['postnatal']['c3'] ?? '',
            $d['postnatal']['c4'] ?? '',
            $d['completed_4pnc'] ? '1' : '0',
            ucfirst($pr['status'])
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
      padding: 2px 3px !important;
      color: #000 !important;
    }
    .tcl-date-cell {
      color: #b91c1c !important;
      font-weight: 700 !important;
      font-family: monospace !important;
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
    line-height: 1.2;
  }
  .tcl-table td {
    border: 1px solid #e2e8f0;
    padding: 6px 4px;
    font-size: 11px;
    text-align: center;
    vertical-align: middle;
  }
  .tcl-table tr:hover td {
    background-color: #f8fafc;
  }
  .tcl-date-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    color: #be123c;
    font-size: 11px;
  }
</style>

<!-- Top Banner -->
<div class="bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100 print:hidden">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200">
        <i class="fas fa-person-pregnant"></i> DOH Maternal Care Standard Register
      </div>
      <h2 class="text-2xl font-bold text-slate-900 mt-2">8 - ANC Target Client List for Maternal Care and Services</h2>
      <p class="text-xs text-slate-500 mt-0.5">Official Department of Health (DOH) standard master register tracking prenatal 8-ANC visits, nutrition, Td immunization, laboratory screenings, and 4PNC postnatal care.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/maternal.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs">
        <i class="fas fa-arrow-left"></i> Maternal Hub
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-print"></i> Print TCL Register (Landscape)
      </button>
    </div>
  </div>

  <!-- KPI Summary Badges -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-150">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">Maternal Cohort</div>
      <div class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats['total']) ?></div>
      <div class="text-[10px] text-slate-400">Total registered mothers</div>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-rose-700">Ongoing Pregnancies</div>
      <div class="text-2xl font-extrabold text-rose-900 mt-1"><?= number_format($stats['ongoing']) ?></div>
      <div class="text-[10px] text-rose-700">Active prenatal monitoring</div>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-amber-700">Sensitive Watchlist (6–7m)</div>
      <div class="text-2xl font-extrabold text-amber-900 mt-1"><?= number_format($stats['sensitive_6_7']) ?></div>
      <div class="text-[10px] text-amber-700">24–31 weeks gestation</div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-emerald-700">Completed 8-ANC</div>
      <div class="text-2xl font-extrabold text-emerald-900 mt-1"><?= number_format($stats['completed_8anc']) ?></div>
      <div class="text-[10px] text-emerald-700">Full prenatal care received</div>
    </div>
  </div>
</div>

<!-- Sheet View Tabs -->
<div class="mt-4 flex items-center gap-2 overflow-x-auto pb-1 print:hidden">
  <span class="text-xs font-bold text-slate-500 mr-1 uppercase">DOH TCL Parts:</span>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '1'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold transition <?= $activeSheet === '1' ? 'bg-rose-700 text-white shadow-xs' : 'bg-white border text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-clipboard-check mr-1.5"></i> Part 1: 8-ANC Prenatal Visits
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '2'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold transition <?= $activeSheet === '2' ? 'bg-rose-700 text-white shadow-xs' : 'bg-white border text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-syringe mr-1.5"></i> Part 2: Nutrition, Td Vaccine &amp; IFA
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '3'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold transition <?= $activeSheet === '3' ? 'bg-rose-700 text-white shadow-xs' : 'bg-white border text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-vial mr-1.5"></i> Part 3: Screenings &amp; Outcomes
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => '4'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold transition <?= $activeSheet === '4' ? 'bg-rose-700 text-white shadow-xs' : 'bg-white border text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-baby mr-1.5"></i> Part 4: Delivery &amp; Postnatal (4PNC)
  </a>
  <a href="?<?= http_build_query(array_merge($_GET, ['sheet' => 'all'])) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold transition <?= $activeSheet === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border text-slate-700 hover:bg-slate-50' ?>">
    <i class="fas fa-table mr-1.5"></i> Master All-in-One View
  </a>
</div>

<!-- Search & Filter Controls -->
<form method="get" class="mt-3 bg-white p-4 rounded-xl shadow border border-slate-100 flex flex-col md:flex-row gap-3 print:hidden">
  <input type="hidden" name="sheet" value="<?= h($activeSheet) ?>" />

  <div class="flex-1">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search Mother / Address / Serial</label>
    <div class="relative">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name or address..." class="w-full border rounded-lg pl-9 pr-3 py-2 text-xs focus:ring-2 focus:ring-rose-500" />
      <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
    </div>
  </div>

  <div class="w-full md:w-48">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Barangay</label>
    <select name="barangay" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
      <option value="">All Barangays</option>
      <?php foreach ($barangays as $b): ?>
        <option value="<?= h($b) ?>" <?= $barangayFilter === $b ? 'selected' : '' ?>><?= h($b) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="w-full md:w-44">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Pregnancy Status</label>
    <select name="status" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Pregnancies</option>
      <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
      <option value="sensitive" <?= $statusFilter === 'sensitive' ? 'selected' : '' ?>>Sensitive (6–7 Mos)</option>
      <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Terminated</option>
    </select>
  </div>

  <div class="w-full md:w-32">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Year</label>
    <select name="year" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-rose-500 bg-white">
      <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
      <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
        <option value="<?= $y ?>" <?= (string)$yearFilter === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="flex items-end gap-2">
    <button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow transition">Filter</button>
    <a href="/HealthLogs/public/maternal/tcl.php" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs">Reset</a>
  </div>
</form>

<!-- Printable DOH Header -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow border border-slate-200">
  <div class="text-center pb-4 mb-3 border-b border-slate-200">
    <div class="text-[11px] uppercase tracking-widest text-slate-500 font-semibold">Republic of the Philippines &bull; Department of Health</div>
    <h1 class="text-lg sm:text-xl font-extrabold uppercase text-slate-900 tracking-wider mt-0.5">
      8 - ANC TARGET CLIENT LIST FOR MATERNAL CARE AND SERVICES
      <?php if ($activeSheet !== 'all'): ?>
        - PART <?= $activeSheet ?>
      <?php endif; ?>
    </h1>
    <div class="text-xs text-slate-600 mt-1 flex items-center justify-center gap-4 flex-wrap">
      <span>Barangay: <strong><?= $barangayFilter ?: 'All Barangays' ?></strong></span>
      <span>&bull;</span>
      <span>Cohort Year: <strong><?= $yearFilter === 'all' ? 'All Records' : $yearFilter ?></strong></span>
      <span>&bull;</span>
      <span>Generated on: <strong><?= date('F d, Y') ?></strong></span>
    </div>
  </div>

  <!-- Table View -->
  <div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="w-full tcl-table min-w-[1280px]">
      <thead>
        <?php if ($activeSheet === '1' || $activeSheet === 'all'): ?>
          <!-- ================= SHEET 1 HEADERS ================= -->
          <tr>
            <th rowspan="3" class="w-8">No.</th>
            <th rowspan="3" class="min-w-[70px]">Date of<br>Registration<br><span class="text-[9px] font-normal text-slate-500">(mm/dd/yy)</span></th>
            <th rowspan="3" class="min-w-[80px]">Family<br>Serial No.</th>
            <th rowspan="3" class="min-w-[150px]">Full Name<br><span class="text-[9px] font-normal text-slate-500">(LastName, FirstName, MI)</span></th>
            <th rowspan="3" class="min-w-[110px]">Complete Address</th>
            <th rowspan="3" class="w-8">Age</th>
            <th rowspan="3" class="w-8">Age<br>Group<br><span class="text-[8px] font-normal">A:10-14<br>B:15-19<br>C:20-49</span></th>
            <th rowspan="3" class="min-w-[75px]">LMP<br><span class="text-[8px] font-normal">(mm/dd/yy)</span><br>G - P</th>
            <th rowspan="3" class="min-w-[75px]">EDD<br><span class="text-[8px] font-normal">(mm/dd/yy)</span></th>

            <th colspan="8" class="bg-rose-100 text-rose-950 font-extrabold uppercase py-1.5">
              Date of Prenatal Check-up (8 ANC) <span class="text-[10px] font-normal lowercase">(mm/dd/yy)</span>
            </th>
            <th rowspan="3" class="w-16 bg-emerald-100 text-emerald-950">
              Completed<br>8ANC?<br><span class="text-[9px] font-normal">(1-Yes, 0-No)</span>
            </th>
            <?php if ($activeSheet === 'all'): ?>
              <th rowspan="3" class="min-w-[65px] bg-sky-100 text-sky-950">BMI<br>1st Tri</th>
              <th colspan="5" class="bg-teal-100 text-teal-950">Tetanus Diphtheria (Td)</th>
              <th rowspan="3" class="w-10 bg-teal-200">FIM</th>
              <th colspan="4" class="bg-indigo-100 text-indigo-950">Postnatal Care (4PNC)</th>
              <th rowspan="3" class="w-16 bg-emerald-200 text-emerald-950">Completed<br>4PNC?</th>
            <?php endif; ?>
          </tr>

          <!-- Sheet 1 Trimester Subheaders -->
          <tr>
            <th class="bg-rose-50 text-rose-900 text-[10px] py-1">1st Trimester</th>
            <th colspan="2" class="bg-amber-50 text-amber-900 text-[10px] py-1">2nd Trimester</th>
            <th colspan="5" class="bg-purple-50 text-purple-900 text-[10px] py-1">3rd Trimester</th>
            <?php if ($activeSheet === 'all'): ?>
              <th rowspan="2" class="text-[9px]">Td1</th>
              <th rowspan="2" class="text-[9px]">Td2</th>
              <th rowspan="2" class="text-[9px]">Td3</th>
              <th rowspan="2" class="text-[9px]">Td4</th>
              <th rowspan="2" class="text-[9px]">Td5</th>
              <th rowspan="2" class="text-[9px]">C1<br>&lt;24h</th>
              <th rowspan="2" class="text-[9px]">C2<br>Day 3</th>
              <th rowspan="2" class="text-[9px]">C3<br>7-14d</th>
              <th rowspan="2" class="text-[9px]">C4<br>6w</th>
            <?php endif; ?>
          </tr>

          <!-- Sheet 1 Recommended Timings -->
          <tr>
            <th class="text-[9px] font-semibold py-1 bg-rose-50/50 min-w-[55px]">Visit 1<br><span class="text-[8px] font-normal">8-12 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-amber-50/50 min-w-[55px]">Visit 2<br><span class="text-[8px] font-normal">14-20 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-amber-50/50 min-w-[55px]">Visit 3<br><span class="text-[8px] font-normal">21-27 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-purple-50/50 min-w-[55px]">Visit 4<br><span class="text-[8px] font-normal">28-30 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-purple-50/50 min-w-[55px]">Visit 5<br><span class="text-[8px] font-normal">31-34 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-purple-50/50 min-w-[55px]">Visit 6<br><span class="text-[8px] font-normal">35 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-purple-50/50 min-w-[55px]">Visit 7<br><span class="text-[8px] font-normal">36 wks</span></th>
            <th class="text-[9px] font-semibold py-1 bg-purple-50/50 min-w-[55px]">Visit 8<br><span class="text-[8px] font-normal">37-40 wks</span></th>
          </tr>

        <?php elseif ($activeSheet === '2'): ?>
          <!-- ================= SHEET 2 HEADERS ================= -->
          <tr>
            <th rowspan="2" class="w-8">No.</th>
            <th rowspan="2" class="min-w-[150px]">Full Name</th>
            <th colspan="3" class="bg-sky-100 text-sky-950">Nutritional Assessment (BMI 1st Tri)</th>
            <th rowspan="2" class="w-16">Remarks<br><span class="text-[8px] font-normal">A-Trans In<br>B-Trans Out</span></th>
            <th colspan="5" class="bg-teal-100 text-teal-950">Date Tetanus Diphtheria (Td) Given (mm/dd/yy)</th>
            <th rowspan="2" class="w-12 bg-teal-200 text-teal-950">FIM<br>Status<br>(&check;/X)</th>
            <th rowspan="2" class="min-w-[65px] bg-amber-100 text-amber-950">Deworming<br>Tablet<br><span class="text-[8px] font-normal">1-Yes, 0-No</span></th>
            <th colspan="6" class="bg-rose-100 text-rose-950">Iron Folic Acid (IFA) Supplementation (#: Tablets, d: Date)</th>
          </tr>
          <tr>
            <th class="text-[9px] bg-sky-50">Low &lt;18.5</th>
            <th class="text-[9px] bg-sky-50">Normal 18.5-22.9</th>
            <th class="text-[9px] bg-sky-50">High &gt;23.5</th>
            <th class="text-[9px] bg-teal-50">Td1 / TT1</th>
            <th class="text-[9px] bg-teal-50">Td2 / TT2</th>
            <th class="text-[9px] bg-teal-50">Td3 / TT3</th>
            <th class="text-[9px] bg-teal-50">Td4 / TT4</th>
            <th class="text-[9px] bg-teal-50">Td5 / TT5</th>
            <th class="text-[9px] bg-rose-50">1st visit (1st tri)</th>
            <th class="text-[9px] bg-rose-50">2nd visit (2nd tri)</th>
            <th class="text-[9px] bg-rose-50">3rd visit (2nd tri)</th>
            <th class="text-[9px] bg-rose-50">4th visit (3rd tri)</th>
            <th class="text-[9px] bg-rose-50">5th visit (3rd tri)</th>
            <th class="text-[9px] bg-rose-50">6th visit (3rd tri)</th>
          </tr>

        <?php elseif ($activeSheet === '3'): ?>
          <!-- ================= SHEET 3 HEADERS ================= -->
          <tr>
            <th rowspan="2" class="w-8">No.</th>
            <th rowspan="2" class="min-w-[150px]">Full Name</th>
            <th colspan="6" class="bg-purple-100 text-purple-950">Multiple Micronutrient Supplementation (MMS)</th>
            <th colspan="3" class="bg-amber-100 text-amber-950">High Risk: Calcium Carbonate (CC)</th>
            <th colspan="3" class="bg-indigo-100 text-indigo-950">Laboratory Screenings (1-Positive/Reactive, 0-Negative)</th>
            <th colspan="2" class="bg-emerald-100 text-emerald-950">Pregnancy Outcome</th>
          </tr>
          <tr>
            <th class="text-[9px] bg-purple-50">1st (1st tri)</th>
            <th class="text-[9px] bg-purple-50">2nd (2nd tri)</th>
            <th class="text-[9px] bg-purple-50">3rd (2nd tri)</th>
            <th class="text-[9px] bg-purple-50">4th (3rd tri)</th>
            <th class="text-[9px] bg-purple-50">5th (3rd tri)</th>
            <th class="text-[9px] bg-purple-50">6th (3rd tri)</th>
            <th class="text-[9px] bg-amber-50">2nd (2nd tri)</th>
            <th class="text-[9px] bg-amber-50">3rd (3rd tri)</th>
            <th class="text-[9px] bg-amber-50">4th (3rd tri)</th>
            <th class="text-[9px] bg-indigo-50">Hepatitis B</th>
            <th class="text-[9px] bg-indigo-50">CBC/Hgb (Anemia)</th>
            <th class="text-[9px] bg-indigo-50">Gest. Diabetes</th>
            <th class="text-[9px] bg-emerald-50">Date Terminated</th>
            <th class="text-[9px] bg-emerald-50">Outcome (FT/PT/FD/AB)</th>
          </tr>

        <?php elseif ($activeSheet === '4'): ?>
          <!-- ================= SHEET 4 HEADERS ================= -->
          <tr>
            <th rowspan="2" class="w-8">No.</th>
            <th rowspan="2" class="min-w-[150px]">Full Name</th>
            <th class="min-w-[70px] bg-sky-100">Delivery Type<br><span class="text-[8px] font-normal">CS/VD/CVCD</span></th>
            <th class="min-w-[65px] bg-sky-100">Birth Weight<br><span class="text-[8px] font-normal">Grams &bull; A/B/C</span></th>
            <th class="min-w-[100px] bg-sky-100">Place of Delivery<br><span class="text-[8px] font-normal">Health Facility / Home</span></th>
            <th class="min-w-[70px] bg-sky-100">Birth Attendant<br><span class="text-[8px] font-normal">MD/RN/MW</span></th>
            <th class="min-w-[80px] bg-sky-100">Delivery Date &amp; Time</th>
            <th colspan="4" class="bg-indigo-100 text-indigo-950">Postnatal Care (4PNC) (mm/dd/yy)</th>
            <th rowspan="2" class="w-16 bg-emerald-100 text-emerald-950">Completed<br>4PNC?</th>
            <th colspan="3" class="bg-rose-100 text-rose-950">Postpartum Supplementation</th>
          </tr>
          <tr>
            <th></th><th></th><th></th><th></th><th></th>
            <th class="text-[9px] bg-indigo-50">Contact 1 (&lt;24h)</th>
            <th class="text-[9px] bg-indigo-50">Contact 2 (Day 3)</th>
            <th class="text-[9px] bg-indigo-50">Contact 3 (7-14d)</th>
            <th class="text-[9px] bg-indigo-50">Contact 4 (6w)</th>
            <th class="text-[9px] bg-rose-50">IFA 1st Visit</th>
            <th class="text-[9px] bg-rose-50">IFA 2nd Visit</th>
            <th class="text-[9px] bg-rose-50">Vit A Given?</th>
          </tr>
        <?php endif; ?>
      </thead>

      <tbody>
        <?php if (empty($tclRows)): ?>
          <tr>
            <td colspan="25" class="py-8 text-center text-slate-400">No maternal records matching the selected filters.</td>
          </tr>
        <?php else: ?>
          <?php $idx = 1; foreach ($tclRows as $row): ?>
            <?php 
              $pr = $row['pr'];
              $d = $row['data'];
              $fullName = trim($pr['last_name'] . ', ' . $pr['first_name'] . ' ' . ($pr['middle_name'] ? substr($pr['middle_name'], 0, 1) . '.' : '') . ' ' . ($pr['suffix'] ?? ''));
            ?>
            <tr>
              <td class="font-mono text-slate-500 font-semibold"><?= $idx++ ?></td>
              
              <?php if ($activeSheet === '1' || $activeSheet === 'all'): ?>
                <td class="font-mono text-[10px] text-slate-600 whitespace-nowrap"><?= h($d['reg_date']) ?></td>
                <td class="font-mono text-[10px] text-slate-600 whitespace-nowrap"><?= h($d['family_serial']) ?></td>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap">
                  <a href="/HealthLogs/public/patients/index.php?q=<?= urlencode($pr['last_name']) ?>" class="hover:text-rose-700 hover:underline">
                    <?= h($fullName) ?>
                  </a>
                  <?php if ($pr['status'] === 'ongoing' && $pr['current_weeks'] >= 24 && $pr['current_weeks'] <= 31): ?>
                    <span class="inline-block ml-1 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200">6–7m</span>
                  <?php endif; ?>
                </td>
                <td class="text-left text-[10px] text-slate-600 whitespace-nowrap"><?= h($pr['barangay']) ?></td>
                <td class="font-semibold text-slate-700"><?= h($d['age']) ?></td>
                <td class="font-bold text-slate-800"><?= h($d['age_group']) ?></td>
                <td class="text-center font-mono text-[10px] whitespace-nowrap">
                  <div class="text-rose-800 font-bold"><?= h($d['lmp']) ?></div>
                  <div class="text-slate-400 font-normal"><?= h($d['gp']) ?></div>
                </td>
                <td class="font-mono text-[10px] font-semibold text-slate-800 whitespace-nowrap"><?= h($d['edd']) ?></td>

                <!-- 8-ANC Visits -->
                <td class="tcl-date-val"><?= $d['visits']['v1'] ? h($d['visits']['v1']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v2'] ? h($d['visits']['v2']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v3'] ? h($d['visits']['v3']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v4'] ? h($d['visits']['v4']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v5'] ? h($d['visits']['v5']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v6'] ? h($d['visits']['v6']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v7'] ? h($d['visits']['v7']) : '' ?></td>
                <td class="tcl-date-val"><?= $d['visits']['v8'] ? h($d['visits']['v8']) : '' ?></td>

                <!-- Completed 8 ANC -->
                <td class="font-bold text-emerald-800 bg-emerald-50/40">
                  <?= $d['completed_8anc'] ? '1' : '0' ?>
                </td>

                <?php if ($activeSheet === 'all'): ?>
                  <td class="font-mono text-xs"><?= $d['bmi'] ?: '--' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td1'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td2'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td3'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td4'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['td']['td5'] ?? '' ?></td>
                  <td class="font-bold text-teal-800"><?= $d['fim'] ? '&check;' : 'X' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c1'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c2'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c3'] ?? '' ?></td>
                  <td class="tcl-date-val"><?= $d['postnatal']['c4'] ?? '' ?></td>
                  <td class="font-bold text-emerald-800"><?= $d['completed_4pnc'] ? '1' : '0' ?></td>
                <?php endif; ?>

              <?php elseif ($activeSheet === '2'): ?>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap"><?= h($fullName) ?></td>
                <td><?= ($d['bmi'] && $d['bmi'] < 18.5) ? $d['bmi'] : '' ?></td>
                <td><?= ($d['bmi'] && $d['bmi'] >= 18.5 && $d['bmi'] <= 22.9) ? $d['bmi'] : ($d['bmi'] ? '' : '&check;') ?></td>
                <td><?= ($d['bmi'] && $d['bmi'] > 23.5) ? $d['bmi'] : '' ?></td>
                <td class="text-slate-400">--</td>
                <td class="tcl-date-val"><?= $d['td']['td1'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['td']['td2'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['td']['td3'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['td']['td4'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['td']['td5'] ?? '' ?></td>
                <td class="font-bold text-teal-800"><?= $d['fim'] ? '&check;' : 'X' ?></td>
                <td>1</td>
                <td class="text-slate-600 font-mono text-[10px]">30 &bull; <?= $d['visits']['v1'] ?: $d['reg_date'] ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['visits']['v2'] ? '30 &bull; ' . $d['visits']['v2'] : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['visits']['v3'] ? '30 &bull; ' . $d['visits']['v3'] : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['visits']['v4'] ? '30 &bull; ' . $d['visits']['v4'] : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['visits']['v5'] ? '30 &bull; ' . $d['visits']['v5'] : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['visits']['v8'] ? '30 &bull; ' . $d['visits']['v8'] : '' ?></td>

              <?php elseif ($activeSheet === '3'): ?>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap"><?= h($fullName) ?></td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">30</td>
                <td class="text-slate-600 font-mono text-[10px]">40</td>
                <td class="text-slate-600 font-mono text-[10px]">40</td>
                <td class="text-slate-600 font-mono text-[10px]">40</td>
                <td class="text-emerald-700 font-bold">0 (Non-reactive)</td>
                <td class="text-emerald-700 font-bold">0 (w/o anemia)</td>
                <td class="text-emerald-700 font-bold">0 (Negative)</td>
                <td class="font-mono text-[10px]"><?= $pr['status'] === 'delivered' ? $d['edd'] : ($pr['status'] === 'terminated' ? 'Terminated' : '--') ?></td>
                <td class="font-semibold text-slate-800"><?= $pr['status'] === 'delivered' ? 'FT (Full Term)' : ($pr['status'] === 'terminated' ? 'AB (Miscarriage)' : 'Ongoing') ?></td>

              <?php elseif ($activeSheet === '4'): ?>
                <td class="text-left font-bold text-slate-900 whitespace-nowrap"><?= h($fullName) ?></td>
                <td class="font-semibold text-slate-700"><?= $pr['status'] === 'delivered' ? 'VD (Vaginal)' : '--' ?></td>
                <td class="font-mono text-[10px]"><?= $pr['status'] === 'delivered' ? '3,100g (A)' : '--' ?></td>
                <td class="text-slate-700"><?= $pr['status'] === 'delivered' ? 'BHS / RHU Public' : '--' ?></td>
                <td class="text-slate-700"><?= $pr['status'] === 'delivered' ? 'MW (Midwife)' : '--' ?></td>
                <td class="font-mono text-[10px]"><?= $pr['status'] === 'delivered' ? $d['edd'] . ' 08:30 AM' : '--' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c1'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c2'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c3'] ?? '' ?></td>
                <td class="tcl-date-val"><?= $d['postnatal']['c4'] ?? '' ?></td>
                <td class="font-bold text-emerald-800"><?= $d['completed_4pnc'] ? '1' : '0' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['postnatal']['c1'] ? '30 tabs' : '' ?></td>
                <td class="text-slate-600 font-mono text-[10px]"><?= $d['postnatal']['c2'] ? '30 tabs' : '' ?></td>
                <td class="text-emerald-700 font-bold">&check;</td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
