<?php
$pageTitle = 'Target Client List for Child Immunization - 2';
require __DIR__ . '/../partials/bootstrap.php';

// Handle CSV Export
$export = ($_GET['export'] ?? '') === 'csv';

// Filters
$q = trim($_GET['q'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$yearFilter = trim($_GET['year'] ?? date('Y'));
$statusFilter = trim($_GET['status'] ?? 'all'); // all, fic, cic, pending

// Fetch barangays for filter dropdown
$barangays = [];
try {
    $barangays = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}

// Query eligible children (e.g. aged 0 to 5, prioritized for 0-23 months)
$whereClauses = ["p.status = 'active'"];
$params = [];

// Filter children up to 5 years old or matching birth year
if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
    $whereClauses[] = "YEAR(p.birth_date) = ?";
    $params[] = (int)$yearFilter;
} else {
    $whereClauses[] = "p.birth_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";
}

if ($barangayFilter !== '') {
    $whereClauses[] = "p.barangay = ?";
    $params[] = $barangayFilter;
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

$childrenSql = "
    SELECT 
        p.id,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.suffix,
        p.sex,
        p.birth_date,
        p.address_line,
        p.barangay,
        p.contact_no,
        p.created_at AS registration_date,
        TIMESTAMPDIFF(MONTH, p.birth_date, CURDATE()) AS age_months,
        DATEDIFF(CURDATE(), p.birth_date) AS age_days
    FROM patients p
    WHERE $whereSql
    ORDER BY p.birth_date DESC, p.last_name ASC
";

$stmtChildren = $pdo->prepare($childrenSql);
$stmtChildren->execute($params);
$children = $stmtChildren->fetchAll(PDO::FETCH_ASSOC);

// Fetch all immunization records for these children
$childIds = array_column($children, 'id');
$recordsByChild = [];

if (!empty($childIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($childIds), '?'));
    $recSql = "
        SELECT 
            r.patient_id,
            r.dose_no,
            r.administered_on,
            r.administered_at,
            r.notes,
            v.name AS vaccine_name,
            v.code AS vaccine_code
        FROM immunization_records r
        JOIN vaccines v ON v.id = r.vaccine_id
        WHERE r.patient_id IN ($inPlaceholders)
        ORDER BY r.administered_on ASC, r.dose_no ASC
    ";
    $stmtRec = $pdo->prepare($recSql);
    $stmtRec->execute($childIds);
    $allRecords = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allRecords as $r) {
        $recordsByChild[$r['patient_id']][] = $r;
    }
}

// Function to map immunization doses according to DOH TCL-2 rules
function mapChildTclDoses(array $child, array $records): array
{
    $birthDate = !empty($child['birth_date']) ? new DateTime($child['birth_date']) : null;

    $doses = [
        'bcg_0_28' => null,
        'bcg_29_1yr' => null,
        'hepab_24h' => null,
        'hepab_more_24h' => null,
        'penta_1' => null,
        'penta_2' => null,
        'penta_3' => null,
        'opv_1' => null,
        'opv_2' => null,
        'opv_3' => null,
        'ipv_1' => null,
        'ipv_2' => null,
        'pcv_1' => null,
        'pcv_2' => null,
        'pcv_3' => null,
        'mmr_1' => null,
        'mmr_2' => null,
        'fic_date' => null,
        'cic_date' => null,
        'remarks' => '',
    ];

    $latestDateForFic = null;
    $hasBcg = false;
    $pentaCount = 0;
    $opvCount = 0;
    $mmrCount = 0;
    $notes = [];

    foreach ($records as $rec) {
        $vName = strtoupper($rec['vaccine_name'] ?? '');
        $vCode = strtoupper($rec['vaccine_code'] ?? '');
        $doseNo = (int)($rec['dose_no'] ?? 1);
        $adminDateStr = !empty($rec['administered_on']) ? $rec['administered_on'] : (!empty($rec['administered_at']) ? substr($rec['administered_at'], 0, 10) : null);
        if (!$adminDateStr) continue;

        $adminDate = new DateTime($adminDateStr);
        $formattedDate = $adminDate->format('m-d-y');

        $ageDaysAtAdmin = $birthDate ? $birthDate->diff($adminDate)->days : 0;
        $ageMonthsAtAdmin = $birthDate ? ($birthDate->diff($adminDate)->y * 12 + $birthDate->diff($adminDate)->m) : 0;

        // BCG
        if (str_contains($vCode, 'BCG') || str_contains($vName, 'BCG')) {
            $hasBcg = true;
            if ($ageDaysAtAdmin <= 28) {
                $doses['bcg_0_28'] = $formattedDate;
            } else {
                $doses['bcg_29_1yr'] = $formattedDate;
            }
            $latestDateForFic = max($latestDateForFic ?? $adminDateStr, $adminDateStr);
        }
        // Hepa B
        elseif (str_contains($vCode, 'HEPB') || str_contains($vName, 'HEPATITIS B') || str_contains($vName, 'HEPA B')) {
            if ($ageDaysAtAdmin <= 1) { // within 24h
                $doses['hepab_24h'] = $formattedDate;
            } else {
                $doses['hepab_more_24h'] = $formattedDate;
            }
        }
        // Pentavalent (DPT-HiB-HepB)
        elseif (str_contains($vCode, 'PENTA') || str_contains($vName, 'PENTAVALENT') || str_contains($vName, 'DPT')) {
            if ($doseNo === 1 && !$doses['penta_1']) {
                $doses['penta_1'] = $formattedDate;
                $pentaCount++;
            } elseif ($doseNo === 2 && !$doses['penta_2']) {
                $doses['penta_2'] = $formattedDate;
                $pentaCount++;
            } elseif ($doseNo >= 3 && !$doses['penta_3']) {
                $doses['penta_3'] = $formattedDate;
                $pentaCount++;
            }
            $latestDateForFic = max($latestDateForFic ?? $adminDateStr, $adminDateStr);
        }
        // OPV
        elseif (str_contains($vCode, 'OPV') || str_contains($vName, 'ORAL POLIO')) {
            if ($doseNo === 1 && !$doses['opv_1']) {
                $doses['opv_1'] = $formattedDate;
                $opvCount++;
            } elseif ($doseNo === 2 && !$doses['opv_2']) {
                $doses['opv_2'] = $formattedDate;
                $opvCount++;
            } elseif ($doseNo >= 3 && !$doses['opv_3']) {
                $doses['opv_3'] = $formattedDate;
                $opvCount++;
            }
            $latestDateForFic = max($latestDateForFic ?? $adminDateStr, $adminDateStr);
        }
        // IPV
        elseif (str_contains($vCode, 'IPV') || str_contains($vName, 'INACTIVATED POLIO')) {
            if ($doseNo === 1 && !$doses['ipv_1']) {
                $doses['ipv_1'] = $formattedDate;
            } elseif ($doseNo >= 2 && !$doses['ipv_2']) {
                $doses['ipv_2'] = $formattedDate;
            }
        }
        // PCV
        elseif (str_contains($vCode, 'PCV') || str_contains($vName, 'PNEUMOCOCCAL')) {
            if ($doseNo === 1 && !$doses['pcv_1']) {
                $doses['pcv_1'] = $formattedDate;
            } elseif ($doseNo === 2 && !$doses['pcv_2']) {
                $doses['pcv_2'] = $formattedDate;
            } elseif ($doseNo >= 3 && !$doses['pcv_3']) {
                $doses['pcv_3'] = $formattedDate;
            }
        }
        // MMR / MR
        elseif (str_contains($vCode, 'MMR') || str_contains($vName, 'MEASLES') || str_contains($vCode, 'MR')) {
            if ($doseNo === 1 && !$doses['mmr_1']) {
                $doses['mmr_1'] = $formattedDate;
                $mmrCount++;
            } elseif ($doseNo >= 2 && !$doses['mmr_2']) {
                $doses['mmr_2'] = $formattedDate;
                $mmrCount++;
            }
            $latestDateForFic = max($latestDateForFic ?? $adminDateStr, $adminDateStr);
        }

        if (!empty($rec['notes'])) {
            $notes[] = $rec['notes'];
        }
    }

    // Evaluation of FIC (0-12 mos) and CIC (13-23 mos)
    // DOH FIC standard: 1 dose BCG, 3 doses DPT-HiB-HepB, 3 doses OPV, and at least 1-2 doses MMR before 12 months
    $isCompletePrimary = $hasBcg && ($pentaCount >= 3) && ($opvCount >= 3) && ($mmrCount >= 1);

    if ($isCompletePrimary && $latestDateForFic && $birthDate) {
        $finalAdminDate = new DateTime($latestDateForFic);
        $ageAtCompletionMonths = ($birthDate->diff($finalAdminDate)->y * 12) + $birthDate->diff($finalAdminDate)->m;
        
        if ($ageAtCompletionMonths <= 12) {
            $doses['fic_date'] = $finalAdminDate->format('m-d-y');
        } elseif ($ageAtCompletionMonths <= 23) {
            $doses['cic_date'] = $finalAdminDate->format('m-d-y');
        }
    }

    // Build Remarks
    if ($doses['fic_date']) {
        $doses['remarks'] = 'FIC Completed (' . $doses['fic_date'] . ')';
    } elseif ($doses['cic_date']) {
        $doses['remarks'] = 'CIC Completed (' . $doses['cic_date'] . ')';
    } elseif (!$hasBcg) {
        $doses['remarks'] = 'Due for BCG';
    } elseif ($pentaCount < 3) {
        $doses['remarks'] = 'Due for Penta ' . ($pentaCount + 1);
    } elseif ($opvCount < 3) {
        $doses['remarks'] = 'Due for OPV ' . ($opvCount + 1);
    } elseif ($mmrCount < 1) {
        $doses['remarks'] = 'Due for MMR 1 (9 mos)';
    } elseif ($mmrCount < 2) {
        $doses['remarks'] = 'Due for MMR 2 (12 mos)';
    } else {
        $doses['remarks'] = !empty($notes) ? implode('; ', array_slice($notes, 0, 2)) : 'On Schedule';
    }

    return $doses;
}

// Process and compile rows
$tclRows = [];
$stats = [
    'total_children' => count($children),
    'fic_count' => 0,
    'cic_count' => 0,
    'pending_count' => 0,
];

foreach ($children as $c) {
    $cRecords = $recordsByChild[$c['id']] ?? [];
    $doseData = mapChildTclDoses($c, $cRecords);

    $isFic = !empty($doseData['fic_date']);
    $isCic = !empty($doseData['cic_date']);

    if ($isFic) $stats['fic_count']++;
    elseif ($isCic) $stats['cic_count']++;
    else $stats['pending_count']++;

    // Apply status filter
    if ($statusFilter === 'fic' && !$isFic) continue;
    if ($statusFilter === 'cic' && !$isCic) continue;
    if ($statusFilter === 'pending' && ($isFic || $isCic)) continue;

    $tclRows[] = [
        'child' => $c,
        'doses' => $doseData,
    ];
}

// Handle CSV Export
if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Target_Client_List_Child_Immunization_TCL2_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    
    // Header Row 1
    fputcsv($out, [
        'No.', 'Child Full Name', 'Date of Birth (mm/dd/yyyy)', 'Sex', 'Barangay / Address',
        'BCG (0-28 days)', 'BCG (29d - 1yr)',
        'Hepa B (within 24h)', 'Hepa B (>24h - 14d)',
        'DPT-HiB-HepB 1 (1.5m)', 'DPT-HiB-HepB 2 (2.5m)', 'DPT-HiB-HepB 3 (3.5m)',
        'OPV 1 (1.5m)', 'OPV 2 (2.5m)', 'OPV 3 (3.5m)',
        'IPV 1 (1.5m)', 'IPV 2 (2.5m)',
        'PCV 1 (1.5m)', 'PCV 2 (2.5m)', 'PCV 3 (3.5m)',
        'MMR 1 (9m)', 'MMR 2 (12m)',
        'FIC (0-12 mos)', 'CIC (13-23 mos)', 'Remarks / Action Taken'
    ]);

    $i = 1;
    foreach ($tclRows as $row) {
        $c = $row['child'];
        $d = $row['doses'];
        $fullName = trim($c['last_name'] . ', ' . $c['first_name'] . ' ' . ($c['middle_name'] ? substr($c['middle_name'], 0, 1) . '.' : '') . ' ' . ($c['suffix'] ?? ''));
        
        fputcsv($out, [
            $i++,
            $fullName,
            $c['birth_date'],
            strtoupper(substr($c['sex'] ?? 'M', 0, 1)),
            $c['barangay'] . ($c['address_line'] ? ' - ' . $c['address_line'] : ''),
            $d['bcg_0_28'] ?? '',
            $d['bcg_29_1yr'] ?? '',
            $d['hepab_24h'] ?? '',
            $d['hepab_more_24h'] ?? '',
            $d['penta_1'] ?? '',
            $d['penta_2'] ?? '',
            $d['penta_3'] ?? '',
            $d['opv_1'] ?? '',
            $d['opv_2'] ?? '',
            $d['opv_3'] ?? '',
            $d['ipv_1'] ?? '',
            $d['ipv_2'] ?? '',
            $d['pcv_1'] ?? '',
            $d['pcv_2'] ?? '',
            $d['pcv_3'] ?? '',
            $d['mmr_1'] ?? '',
            $d['mmr_2'] ?? '',
            $d['fic_date'] ?? '',
            $d['cic_date'] ?? '',
            $d['remarks'] ?? '',
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
    .tcl-header {
      display: block !important;
      text-align: center;
      margin-bottom: 8px;
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

<!-- Top Action Banner -->
<div class="bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100 print:hidden">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200">
        <i class="fas fa-syringe"></i> DOH EPI Standard Register
      </div>
      <h2 class="text-2xl font-bold text-slate-900 mt-2">Target Client List for Child Immunization - 2</h2>
      <p class="text-xs text-slate-500 mt-0.5">Official Department of Health (DOH) standard master register tracking child immunization doses from birth to 23 months.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/immunization.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs">
        <i class="fas fa-arrow-left"></i> Module Hub
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-file-csv"></i> Export CSV
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-print"></i> Print TCL-2 Register (Landscape)
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-slate-150">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
      <div class="text-[11px] uppercase font-bold text-slate-500">Registered Cohort</div>
      <div class="text-2xl font-extrabold text-slate-800 mt-1"><?= number_format($stats['total_children']) ?></div>
      <div class="text-[10px] text-slate-400">Total children in registry</div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-emerald-700">Fully Immunized (FIC)</div>
      <div class="text-2xl font-extrabold text-emerald-900 mt-1"><?= number_format($stats['fic_count']) ?></div>
      <div class="text-[10px] text-emerald-700">Completed ≤ 12 months</div>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-blue-700">Completely Immunized (CIC)</div>
      <div class="text-2xl font-extrabold text-blue-900 mt-1"><?= number_format($stats['cic_count']) ?></div>
      <div class="text-[10px] text-blue-700">Completed 13-23 months</div>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5">
      <div class="text-[11px] uppercase font-bold text-amber-700">In Progress / Due</div>
      <div class="text-2xl font-extrabold text-amber-900 mt-1"><?= number_format($stats['pending_count']) ?></div>
      <div class="text-[10px] text-amber-700">Pending upcoming doses</div>
    </div>
  </div>
</div>

<!-- Search & Filter Controls -->
<form method="get" class="mt-4 bg-white p-4 rounded-xl shadow border border-slate-100 flex flex-col md:flex-row gap-3 print:hidden">
  <div class="flex-1">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search Child / Mother / Address</label>
    <div class="relative">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name or address..." class="w-full border rounded-lg pl-9 pr-3 py-2 text-xs focus:ring-2 focus:ring-teal-500" />
      <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
    </div>
  </div>

  <div class="w-full md:w-48">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Barangay</label>
    <select name="barangay" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-teal-500 bg-white">
      <option value="">All Barangays</option>
      <?php foreach ($barangays as $b): ?>
        <option value="<?= h($b) ?>" <?= $barangayFilter === $b ? 'selected' : '' ?>><?= h($b) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="w-full md:w-36">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Birth Year</label>
    <select name="year" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-teal-500 bg-white">
      <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
      <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
        <option value="<?= $y ?>" <?= (string)$yearFilter === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>

  <div class="w-full md:w-40">
    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Immunization Status</label>
    <select name="status" class="w-full border rounded-lg px-2.5 py-2 text-xs focus:ring-2 focus:ring-teal-500 bg-white">
      <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Children</option>
      <option value="fic" <?= $statusFilter === 'fic' ? 'selected' : '' ?>>FIC (Fully Immunized)</option>
      <option value="cic" <?= $statusFilter === 'cic' ? 'selected' : '' ?>>CIC (Completely Immunized)</option>
      <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Incomplete / Due</option>
    </select>
  </div>

  <div class="flex items-end gap-2">
    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow transition">Filter</button>
    <a href="/HealthLogs/public/immunization/tcl.php" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs">Reset</a>
  </div>
</form>

<!-- Printable DOH Header (visible on print or export layout) -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow border border-slate-200">
  <div class="text-center pb-4 mb-3 border-b border-slate-200">
    <div class="text-[11px] uppercase tracking-widest text-slate-500 font-semibold">Republic of the Philippines &bull; Department of Health</div>
    <h1 class="text-lg sm:text-xl font-extrabold uppercase text-slate-900 tracking-wider mt-0.5">TARGET CLIENT LIST FOR CHILD IMMUNIZATION - 2</h1>
    <div class="text-xs text-slate-600 mt-1 flex items-center justify-center gap-4 flex-wrap">
      <span>Barangay: <strong><?= $barangayFilter ?: 'All Barangays' ?></strong></span>
      <span>&bull;</span>
      <span>Birth Year: <strong><?= $yearFilter === 'all' ? 'All Records' : $yearFilter ?></strong></span>
      <span>&bull;</span>
      <span>Date Generated: <strong><?= date('F d, Y') ?></strong></span>
    </div>
  </div>

  <!-- Official DOH TCL-2 Format Table -->
  <div class="overflow-x-auto -mx-4 sm:mx-0">
    <table class="w-full tcl-table min-w-[1280px]">
      <thead>
        <!-- Master Header Row 1 -->
        <tr>
          <th rowspan="3" class="w-8">No.</th>
          <th rowspan="3" class="min-w-[160px]">Child Full Name</th>
          <th rowspan="3" class="min-w-[75px]">Date of Birth<br><span class="text-[9px] font-normal text-slate-500">(mm/dd/yy)</span></th>
          <th rowspan="3" class="w-8">Sex</th>
          <th rowspan="3" class="min-w-[120px]">Address / Barangay</th>
          
          <th colspan="17" class="bg-slate-200 text-slate-900 font-extrabold uppercase tracking-wide py-1.5">
            Immunization <span class="text-[10px] font-normal lowercase">(mm/dd/yy)</span>
          </th>
          
          <th rowspan="3" class="w-16 bg-emerald-100/70 text-emerald-950">
            FIC<br><span class="text-[9px] font-medium">(0-12 mos)</span>
          </th>
          <th rowspan="3" class="w-16 bg-blue-100/70 text-blue-950">
            CIC<br><span class="text-[9px] font-medium">(13-23 mos)</span>
          </th>
          <th rowspan="3" class="min-w-[140px]">Remarks / Action Taken</th>
        </tr>

        <!-- Master Header Row 2: Vaccine Categories -->
        <tr>
          <th colspan="2" class="bg-teal-50 text-teal-900">BCG</th>
          <th colspan="2" class="bg-blue-50 text-blue-900">Hepa B</th>
          <th colspan="3" class="bg-amber-50 text-amber-900">DPT-HiB-HepB</th>
          <th colspan="3" class="bg-emerald-50 text-emerald-900">OPV</th>
          <th colspan="2" class="bg-indigo-50 text-indigo-900">IPV</th>
          <th colspan="3" class="bg-cyan-50 text-cyan-900">PCV</th>
          <th colspan="2" class="bg-rose-50 text-rose-900">MMR</th>
        </tr>

        <!-- Master Header Row 3: Timings & Doses -->
        <tr>
          <!-- BCG -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-teal-50/50 min-w-[55px]">within<br>0-28 days</th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-teal-50/50 min-w-[55px]">29 days to<br>1 year old</th>
          <!-- Hepa B -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-blue-50/50 min-w-[55px]">within 24h<br>after birth</th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-blue-50/50 min-w-[55px]">&gt;24 hrs up<br>to 14 days</th>
          <!-- DPT-HiB-HepB -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-amber-50/50 min-w-[50px]">1<sup>st</sup> dose<br><span class="text-[8px] font-normal">1 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-amber-50/50 min-w-[50px]">2<sup>nd</sup> dose<br><span class="text-[8px] font-normal">2 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-amber-50/50 min-w-[50px]">3<sup>rd</sup> dose<br><span class="text-[8px] font-normal">3 &frac12; mos</span></th>
          <!-- OPV -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-emerald-50/50 min-w-[50px]">1<sup>st</sup> dose<br><span class="text-[8px] font-normal">1 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-emerald-50/50 min-w-[50px]">2<sup>nd</sup> dose<br><span class="text-[8px] font-normal">2 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-emerald-50/50 min-w-[50px]">3<sup>rd</sup> dose<br><span class="text-[8px] font-normal">3 &frac12; mos</span></th>
          <!-- IPV -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-indigo-50/50 min-w-[50px]">1<sup>st</sup> dose<br><span class="text-[8px] font-normal">1 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-indigo-50/50 min-w-[50px]">2<sup>nd</sup> dose<br><span class="text-[8px] font-normal">2 &frac12; mos</span></th>
          <!-- PCV -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-cyan-50/50 min-w-[50px]">1<sup>st</sup> dose<br><span class="text-[8px] font-normal">1 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-cyan-50/50 min-w-[50px]">2<sup>nd</sup> dose<br><span class="text-[8px] font-normal">2 &frac12; mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-cyan-50/50 min-w-[50px]">3<sup>rd</sup> dose<br><span class="text-[8px] font-normal">3 &frac12; mos</span></th>
          <!-- MMR -->
          <th class="text-[9px] font-semibold py-1 px-1 bg-rose-50/50 min-w-[50px]">1<sup>st</sup> dose<br><span class="text-[8px] font-normal">9 mos</span></th>
          <th class="text-[9px] font-semibold py-1 px-1 bg-rose-50/50 min-w-[50px]">2<sup>nd</sup> dose<br><span class="text-[8px] font-normal">12 mos</span></th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($tclRows)): ?>
          <tr>
            <td colspan="25" class="py-8 text-center text-slate-400">No child immunization records matching the selected filters.</td>
          </tr>
        <?php else: ?>
          <?php $idx = 1; foreach ($tclRows as $row): ?>
            <?php 
              $c = $row['child'];
              $d = $row['doses'];
              $fullName = trim($c['last_name'] . ', ' . $c['first_name'] . ' ' . ($c['middle_name'] ? substr($c['middle_name'], 0, 1) . '.' : '') . ' ' . ($c['suffix'] ?? ''));
              $bDateFormatted = !empty($c['birth_date']) ? date('m-d-y', strtotime($c['birth_date'])) : '--';
              $sexInitial = strtoupper(substr($c['sex'] ?? 'M', 0, 1));
            ?>
            <tr>
              <td class="font-mono text-slate-500 font-semibold"><?= $idx++ ?></td>
              <td class="text-left font-bold text-slate-900 whitespace-nowrap">
                <a href="/HealthLogs/public/patients/view.php?id=<?= $c['id'] ?>" class="hover:text-teal-700 hover:underline">
                  <?= h($fullName) ?>
                </a>
              </td>
              <td class="font-mono text-slate-700 whitespace-nowrap"><?= h($bDateFormatted) ?></td>
              <td class="font-semibold text-slate-600"><?= h($sexInitial) ?></td>
              <td class="text-left text-[10px] text-slate-600 whitespace-nowrap">
                <?= h($c['barangay']) ?>
              </td>

              <!-- BCG -->
              <td class="tcl-date-val"><?= $d['bcg_0_28'] ? h($d['bcg_0_28']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['bcg_29_1yr'] ? h($d['bcg_29_1yr']) : '' ?></td>

              <!-- Hepa B -->
              <td class="tcl-date-val"><?= $d['hepab_24h'] ? h($d['hepab_24h']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['hepab_more_24h'] ? h($d['hepab_more_24h']) : '' ?></td>

              <!-- DPT-HiB-HepB -->
              <td class="tcl-date-val"><?= $d['penta_1'] ? h($d['penta_1']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['penta_2'] ? h($d['penta_2']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['penta_3'] ? h($d['penta_3']) : '' ?></td>

              <!-- OPV -->
              <td class="tcl-date-val"><?= $d['opv_1'] ? h($d['opv_1']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['opv_2'] ? h($d['opv_2']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['opv_3'] ? h($d['opv_3']) : '' ?></td>

              <!-- IPV -->
              <td class="tcl-date-val"><?= $d['ipv_1'] ? h($d['ipv_1']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['ipv_2'] ? h($d['ipv_2']) : '' ?></td>

              <!-- PCV -->
              <td class="tcl-date-val"><?= $d['pcv_1'] ? h($d['pcv_1']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['pcv_2'] ? h($d['pcv_2']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['pcv_3'] ? h($d['pcv_3']) : '' ?></td>

              <!-- MMR -->
              <td class="tcl-date-val"><?= $d['mmr_1'] ? h($d['mmr_1']) : '' ?></td>
              <td class="tcl-date-val"><?= $d['mmr_2'] ? h($d['mmr_2']) : '' ?></td>

              <!-- FIC / CIC -->
              <td class="font-bold text-emerald-800 bg-emerald-50/40">
                <?= $d['fic_date'] ? h($d['fic_date']) : '' ?>
              </td>
              <td class="font-bold text-blue-800 bg-blue-50/40">
                <?= $d['cic_date'] ? h($d['cic_date']) : '' ?>
              </td>

              <!-- Remarks -->
              <td class="text-left text-[10px] text-slate-700 whitespace-nowrap">
                <?= h($d['remarks']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
