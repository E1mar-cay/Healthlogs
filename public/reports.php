<?php
$pageTitle = 'Reports';
require __DIR__ . '/partials/bootstrap.php';

$fromDate = trim((string)($_GET['from'] ?? ''));
$toDate = trim((string)($_GET['to'] ?? ''));
$barangayFilter = trim((string)($_GET['barangay'] ?? ''));
$sexFilter = trim((string)($_GET['sex'] ?? ''));
$ageGroupFilter = trim((string)($_GET['age_group'] ?? ''));
$exportType = trim((string)($_GET['export'] ?? ''));

$validSex = ['male', 'female'];
$validAgeGroups = ['0-4', '5-12', '13-17', '18-35', '36-59', '60+'];

$buildAgeWhere = function (string $column): string {
    return "CASE
        WHEN TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN 0 AND 4 THEN '0-4'
        WHEN TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN 5 AND 12 THEN '5-12'
        WHEN TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN 13 AND 17 THEN '13-17'
        WHEN TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN 18 AND 35 THEN '18-35'
        WHEN TIMESTAMPDIFF(YEAR, {$column}, CURDATE()) BETWEEN 36 AND 59 THEN '36-59'
        ELSE '60+'
    END";
};

$csvOutput = function (string $filename, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
};

$consultationForecast = [];
$admissionForecast = [];
$seasonalDiseaseRows = [];
$seasonalTopMonths = [];
$diseaseTrendMonths = [];
$diseaseTrendSeries = [];
$medicalRecordsRows = [];
$consultationRows = [];
$ageGenderRows = [];
$ageGroupLabels = [];
$maleCounts = [];
$femaleCounts = [];
$barangayOptions = [];

try {
    $barangayOptions = $pdo->query("SELECT DISTINCT barangay FROM patients WHERE barangay IS NOT NULL AND barangay <> '' ORDER BY barangay ASC")
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $barangayOptions = [];
}

// 1. Weekly Visits & Forecasting (Dynamic with Filters)
try {
    $weeklyWhere = ["1=1"];
    $weeklyParams = [];
    if ($fromDate !== '') {
        $weeklyWhere[] = "DATE(v.visit_datetime) >= ?";
        $weeklyParams[] = $fromDate;
    } else {
        $weeklyWhere[] = "v.visit_datetime >= DATE_SUB(CURDATE(), INTERVAL 140 DAY)";
    }
    if ($toDate !== '') {
        $weeklyWhere[] = "DATE(v.visit_datetime) <= ?";
        $weeklyParams[] = $toDate;
    }
    if ($barangayFilter !== '') {
        $weeklyWhere[] = "p.barangay = ?";
        $weeklyParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $weeklyWhere[] = "p.sex = ?";
        $weeklyParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $weeklyWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $weeklyParams[] = $ageGroupFilter;
    }

    $weeklySql = "SELECT
            YEARWEEK(v.visit_datetime, 1) AS week_key,
            COUNT(*) AS total_visits,
            SUM(CASE WHEN v.visit_type = 'general' THEN 1 ELSE 0 END) AS consultation_visits
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        WHERE " . implode(' AND ', $weeklyWhere) . "
        GROUP BY YEARWEEK(v.visit_datetime, 1)
        ORDER BY week_key ASC";
    $weeklyStmt = $pdo->prepare($weeklySql);
    $weeklyStmt->execute($weeklyParams);
    $weeklyRows = $weeklyStmt->fetchAll(PDO::FETCH_ASSOC);

    $consultationValues = array_map(fn($x) => (float)$x['consultation_visits'], $weeklyRows);
    $admissionValues = array_map(fn($x) => (float)$x['total_visits'], $weeklyRows);

    $simpleForecast = function (array $values, int $horizon = 4): array {
        if (empty($values)) {
            return [];
        }
        $recent = array_slice($values, -8);
        $older = array_slice($values, -16, 8);
        $recentAvg = count($recent) ? array_sum($recent) / count($recent) : 0.0;
        $olderAvg = count($older) ? array_sum($older) / count($older) : $recentAvg;
        $step = ($recentAvg - $olderAvg) / max(1, $horizon);

        $rows = [];
        for ($i = 1; $i <= $horizon; $i++) {
            $rows[] = max(0.0, $recentAvg + ($step * $i));
        }
        return $rows;
    };

    $consultationForecast = $simpleForecast($consultationValues, 4);
    $admissionForecast = $simpleForecast($admissionValues, 4);
} catch (Throwable $e) {
    $consultationForecast = [];
    $admissionForecast = [];
}

// 2. Seasonal Diseases (Dynamic with Filters)
try {
    $seasonalWhere = ["pc.diagnosed_on IS NOT NULL"];
    $seasonalParams = [];
    if ($fromDate !== '') {
        $seasonalWhere[] = "DATE(pc.diagnosed_on) >= ?";
        $seasonalParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $seasonalWhere[] = "DATE(pc.diagnosed_on) <= ?";
        $seasonalParams[] = $toDate;
    }
    if ($barangayFilter !== '') {
        $seasonalWhere[] = "p.barangay = ?";
        $seasonalParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $seasonalWhere[] = "p.sex = ?";
        $seasonalParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $seasonalWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $seasonalParams[] = $ageGroupFilter;
    }

    $seasonalSql = "SELECT
            MONTH(pc.diagnosed_on) AS month_no,
            DATE_FORMAT(pc.diagnosed_on, '%b') AS month_label,
            COUNT(*) AS total_cases
        FROM patient_conditions pc
        INNER JOIN patients p ON p.id = pc.patient_id
        WHERE " . implode(' AND ', $seasonalWhere) . "
        GROUP BY MONTH(pc.diagnosed_on), DATE_FORMAT(pc.diagnosed_on, '%b')
        ORDER BY MONTH(pc.diagnosed_on)";
    $seasonalStmt = $pdo->prepare($seasonalSql);
    $seasonalStmt->execute($seasonalParams);
    $seasonalDiseaseRows = $seasonalStmt->fetchAll(PDO::FETCH_ASSOC);

    $sortedSeason = $seasonalDiseaseRows;
    usort($sortedSeason, fn($a, $b) => (int)$b['total_cases'] <=> (int)$a['total_cases']);
    $seasonalTopMonths = array_slice($sortedSeason, 0, 3);
} catch (Throwable $e) {
    $seasonalDiseaseRows = [];
    $seasonalTopMonths = [];
}

// 3. Top 5 Diseases & Monthly Disease Trends (Dynamic with Filters)
try {
    $topDiseaseWhere = ["pc.diagnosed_on IS NOT NULL"];
    $topDiseaseParams = [];
    if ($fromDate !== '') {
        $topDiseaseWhere[] = "DATE(pc.diagnosed_on) >= ?";
        $topDiseaseParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $topDiseaseWhere[] = "DATE(pc.diagnosed_on) <= ?";
        $topDiseaseParams[] = $toDate;
    }
    if ($barangayFilter !== '') {
        $topDiseaseWhere[] = "p.barangay = ?";
        $topDiseaseParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $topDiseaseWhere[] = "p.sex = ?";
        $topDiseaseParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $topDiseaseWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $topDiseaseParams[] = $ageGroupFilter;
    }

    $topDiseaseSql = "SELECT pc.condition_name, COUNT(*) AS total_cases
        FROM patient_conditions pc
        INNER JOIN patients p ON p.id = pc.patient_id
        WHERE " . implode(' AND ', $topDiseaseWhere) . "
        GROUP BY pc.condition_name
        ORDER BY total_cases DESC
        LIMIT 5";
    $topDiseaseStmt = $pdo->prepare($topDiseaseSql);
    $topDiseaseStmt->execute($topDiseaseParams);
    $topDiseases = $topDiseaseStmt->fetchAll(PDO::FETCH_ASSOC);
    $topDiseaseNames = array_map(fn($d) => $d['condition_name'], $topDiseases);

    if (!empty($topDiseaseNames)) {
        $trendWhere = [
            "pc.diagnosed_on IS NOT NULL",
            "pc.condition_name IN (" . implode(',', array_fill(0, count($topDiseaseNames), '?')) . ")"
        ];
        $trendParams = $topDiseaseNames;
        if ($fromDate !== '') {
            $trendWhere[] = "DATE(pc.diagnosed_on) >= ?";
            $trendParams[] = $fromDate;
        } else {
            $trendWhere[] = "pc.diagnosed_on >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }
        if ($toDate !== '') {
            $trendWhere[] = "DATE(pc.diagnosed_on) <= ?";
            $trendParams[] = $toDate;
        }
        if ($barangayFilter !== '') {
            $trendWhere[] = "p.barangay = ?";
            $trendParams[] = $barangayFilter;
        }
        if (in_array($sexFilter, $validSex, true)) {
            $trendWhere[] = "p.sex = ?";
            $trendParams[] = $sexFilter;
        }
        if (in_array($ageGroupFilter, $validAgeGroups, true)) {
            $trendWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
            $trendParams[] = $ageGroupFilter;
        }

        $trendSql = "SELECT
                DATE_FORMAT(pc.diagnosed_on, '%Y-%m') AS ym,
                pc.condition_name,
                COUNT(*) AS total_cases
            FROM patient_conditions pc
            INNER JOIN patients p ON p.id = pc.patient_id
            WHERE " . implode(' AND ', $trendWhere) . "
            GROUP BY DATE_FORMAT(pc.diagnosed_on, '%Y-%m'), pc.condition_name
            ORDER BY ym ASC";
        $trendStmt = $pdo->prepare($trendSql);
        $trendStmt->execute($trendParams);
        $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        $monthMap = [];
        foreach ($trendRows as $row) {
            $monthMap[$row['ym']] = true;
        }
        $diseaseTrendMonths = array_keys($monthMap);
        sort($diseaseTrendMonths);

        foreach ($topDiseaseNames as $name) {
            $diseaseTrendSeries[$name] = array_fill(0, count($diseaseTrendMonths), 0);
        }
        $monthIndex = array_flip($diseaseTrendMonths);
        foreach ($trendRows as $row) {
            if (isset($diseaseTrendSeries[$row['condition_name']], $monthIndex[$row['ym']])) {
                $diseaseTrendSeries[$row['condition_name']][$monthIndex[$row['ym']]] = (int)$row['total_cases'];
            }
        }
    }
} catch (Throwable $e) {
    $diseaseTrendMonths = [];
    $diseaseTrendSeries = [];
}

// 4. Patient Medical Records (Dynamic with Filters, ALL records included without limit)
try {
    $medicalRecordsWhere = [];
    $medicalRecordsParams = [];
    if ($barangayFilter !== '') {
        $medicalRecordsWhere[] = "p.barangay = ?";
        $medicalRecordsParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $medicalRecordsWhere[] = "p.sex = ?";
        $medicalRecordsParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $medicalRecordsWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $medicalRecordsParams[] = $ageGroupFilter;
    }
    if ($fromDate !== '') {
        $medicalRecordsWhere[] = "(DATE(p.created_at) >= ? OR pc.diagnosed_on >= ?)";
        $medicalRecordsParams[] = $fromDate;
        $medicalRecordsParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $medicalRecordsWhere[] = "(DATE(p.created_at) <= ? OR pc.diagnosed_on <= ?)";
        $medicalRecordsParams[] = $toDate;
        $medicalRecordsParams[] = $toDate;
    }
    $medicalRecordsSql = "SELECT
            p.id,
            p.first_name,
            p.last_name,
            p.sex,
            p.birth_date,
            p.barangay,
            p.status,
            COUNT(DISTINCT pc.id) AS conditions_count,
            COUNT(DISTINCT pa.id) AS allergies_count,
            MAX(pc.diagnosed_on) AS latest_diagnosis_date
        FROM patients p
        LEFT JOIN patient_conditions pc ON pc.patient_id = p.id
        LEFT JOIN patient_allergies pa ON pa.patient_id = p.id
        " . (!empty($medicalRecordsWhere) ? 'WHERE ' . implode(' AND ', $medicalRecordsWhere) : '') . "
        GROUP BY p.id, p.first_name, p.last_name, p.sex, p.birth_date, p.barangay, p.status
        ORDER BY p.id DESC";
    $medicalStmt = $pdo->prepare($medicalRecordsSql);
    $medicalStmt->execute($medicalRecordsParams);
    $medicalRecordsRows = $medicalStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $medicalRecordsRows = [];
}

// 5. Patient Consultations (Dynamic with Filters, ALL records included without limit)
try {
    $consultationWhere = ["v.visit_type = 'general'"];
    $consultationParams = [];
    if ($fromDate !== '') {
        $consultationWhere[] = "DATE(v.visit_datetime) >= ?";
        $consultationParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $consultationWhere[] = "DATE(v.visit_datetime) <= ?";
        $consultationParams[] = $toDate;
    }
    if ($barangayFilter !== '') {
        $consultationWhere[] = "p.barangay = ?";
        $consultationParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $consultationWhere[] = "p.sex = ?";
        $consultationParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $consultationWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $consultationParams[] = $ageGroupFilter;
    }
    $consultationSql = "SELECT
            DATE(v.visit_datetime) AS consult_date,
            p.first_name,
            p.last_name,
            p.barangay,
            v.reason,
            v.notes
        FROM visits v
        INNER JOIN patients p ON p.id = v.patient_id
        WHERE " . implode(' AND ', $consultationWhere) . "
        ORDER BY v.visit_datetime DESC";
    $consultationStmt = $pdo->prepare($consultationSql);
    $consultationStmt->execute($consultationParams);
    $consultationRows = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $consultationRows = [];
}

// 6. Population Report by Age Group and Gender (Dynamic with Filters)
try {
    $ageGenderWhere = ["p.status <> 'deceased'"];
    $ageGenderParams = [];
    if ($barangayFilter !== '') {
        $ageGenderWhere[] = "p.barangay = ?";
        $ageGenderParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $ageGenderWhere[] = "p.sex = ?";
        $ageGenderParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $ageGenderWhere[] = $buildAgeWhere('p.birth_date') . " = ?";
        $ageGenderParams[] = $ageGroupFilter;
    }
    if ($fromDate !== '') {
        $ageGenderWhere[] = "DATE(p.created_at) >= ?";
        $ageGenderParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $ageGenderWhere[] = "DATE(p.created_at) <= ?";
        $ageGenderParams[] = $toDate;
    }

    $ageGenderSql = "SELECT
            " . $buildAgeWhere('p.birth_date') . " AS age_group,
            p.sex,
            COUNT(*) AS total
        FROM patients p
        WHERE " . implode(' AND ', $ageGenderWhere) . "
        GROUP BY age_group, p.sex
        ORDER BY FIELD(age_group, '0-4','5-12','13-17','18-35','36-59','60+'), p.sex";
    $ageGenderStmt = $pdo->prepare($ageGenderSql);
    $ageGenderStmt->execute($ageGenderParams);
    $ageGenderRows = $ageGenderStmt->fetchAll(PDO::FETCH_ASSOC);

    $ageGroupLabels = ['0-4', '5-12', '13-17', '18-35', '36-59', '60+'];
    $maleMap = array_fill_keys($ageGroupLabels, 0);
    $femaleMap = array_fill_keys($ageGroupLabels, 0);
    foreach ($ageGenderRows as $row) {
        $group = $row['age_group'];
        $sex = strtolower((string)$row['sex']);
        $total = (int)$row['total'];
        if ($sex === 'male') {
            $maleMap[$group] = $total;
        } elseif ($sex === 'female') {
            $femaleMap[$group] = $total;
        }
    }
    $maleCounts = array_values($maleMap);
    $femaleCounts = array_values($femaleMap);
} catch (Throwable $e) {
    $ageGroupLabels = ['0-4', '5-12', '13-17', '18-35', '36-59', '60+'];
    $maleCounts = [0, 0, 0, 0, 0, 0];
    $femaleCounts = [0, 0, 0, 0, 0, 0];
}

// Handle CSV Exports
if ($exportType !== '') {
    if ($exportType === 'medical_records') {
        $rows = array_map(function (array $row): array {
            return [
                $row['id'],
                $row['last_name'] . ', ' . $row['first_name'],
                $row['sex'],
                $row['birth_date'],
                $row['barangay'],
                $row['status'],
                $row['conditions_count'],
                $row['allergies_count'],
                $row['latest_diagnosis_date'] ?: '',
            ];
        }, $medicalRecordsRows);
        $csvOutput('patient_medical_records.csv', ['ID', 'Patient', 'Sex', 'Birth Date', 'Barangay', 'Status', 'Conditions', 'Allergies', 'Latest Diagnosis'], $rows);
    } elseif ($exportType === 'consultation') {
        $rows = array_map(function (array $row): array {
            return [
                $row['consult_date'],
                $row['last_name'] . ', ' . $row['first_name'],
                $row['barangay'],
                $row['reason'] ?: '',
                $row['notes'] ?: '',
            ];
        }, $consultationRows);
        $csvOutput('patient_consultation.csv', ['Date', 'Patient', 'Barangay', 'Reason', 'Notes'], $rows);
    } elseif ($exportType === 'population') {
        $rows = [];
        foreach ($ageGroupLabels as $idx => $label) {
            $rows[] = [$label, $maleCounts[$idx] ?? 0, $femaleCounts[$idx] ?? 0];
        }
        $csvOutput('population_by_age_gender.csv', ['Age Group', 'Male', 'Female'], $rows);
    }
}

// Prepare filter summary text for display & print metadata
$filterSummaryParts = [];
if ($fromDate !== '' || $toDate !== '') {
    $filterSummaryParts[] = 'Period: ' . ($fromDate ?: 'Start') . ' to ' . ($toDate ?: 'Present');
}
if ($barangayFilter !== '') {
    $filterSummaryParts[] = 'Barangay: ' . $barangayFilter;
}
if ($sexFilter !== '') {
    $filterSummaryParts[] = 'Gender: ' . ucfirst($sexFilter);
}
if ($ageGroupFilter !== '') {
    $filterSummaryParts[] = 'Age Group: ' . $ageGroupFilter;
}
$filterSummaryText = !empty($filterSummaryParts) ? implode(' | ', $filterSummaryParts) : 'All Records (No Filters Applied)';

$currentUserFullName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Health Center Staff';
$currentUserRole = ($_SESSION['role'] ?? '') === 'admin' ? 'System Administrator / Admin' : 'Barangay Health Worker (BHW)';
$currentDateTimeFormatted = date('F j, Y, h:i A');

require __DIR__ . '/partials/header.php';
?>

<!-- Printable Official Header (Visible only when full-page printing) -->
<div id="printOfficialHeader" class="hidden print:block mb-6 border-b-2 border-slate-800 pb-4">
  <div class="flex items-center justify-between gap-4">
    <div class="shrink-0">
      <svg class="w-16 h-16" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="46" fill="#0f766e" stroke="#115e59" stroke-width="2"/>
        <circle cx="50" cy="50" r="41" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-dasharray="3 2"/>
        <path d="M43 25 h14 v18 h18 v14 h-18 v18 h-14 v-18 h-18 v-14 h18 z" fill="#0ea5a4" opacity="0.3"/>
        <rect x="44" y="24" width="12" height="52" rx="2" fill="#0f766e"/>
        <rect x="24" y="44" width="52" height="12" rx="2" fill="#0f766e"/>
        <circle cx="50" cy="50" r="7" fill="#ffffff"/>
        <path d="M50 45 L52 49 L56 50 L52 52 L50 56 L48 52 L44 50 L48 49 Z" fill="#0f766e"/>
      </svg>
    </div>
    <div class="text-center flex-1">
      <div class="text-xs uppercase tracking-widest text-slate-600 font-semibold">Republic of the Philippines</div>
      <div class="text-xs uppercase tracking-wider text-slate-700 font-medium">Department of Health • Primary Care Services</div>
      <div class="text-base font-bold text-slate-900 tracking-wide uppercase mt-0.5">Barangay Health Center & Care Hub</div>
      <div class="text-xs font-semibold text-teal-800">HealthLogs Information Management System</div>
    </div>
    <div class="shrink-0 text-right text-xs text-slate-500">
      <div><strong>Date:</strong> <?= date('M d, Y') ?></div>
      <div><strong>Time:</strong> <?= date('h:i A') ?></div>
    </div>
  </div>
  <div class="mt-3 bg-slate-50 p-2.5 rounded border border-slate-200 flex flex-wrap justify-between items-center text-xs text-slate-700">
    <div><strong>Filter Scope:</strong> <?= h($filterSummaryText) ?></div>
    <div><strong>Generated By:</strong> <?= h($currentUserFullName) ?> (<?= h($currentUserRole) ?>)</div>
  </div>
</div>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow print:hidden">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-sm text-slate-500 font-medium">Health Analytics & Reporting</div>
      <div class="text-2xl font-semibold text-slate-900">Reports</div>
      <p class="text-sm text-slate-500 mt-1">Dynamic reporting synchronized across patient medical records, consultations, demographics, and disease trends.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="/HealthLogs/public/immunization/tcl.php" class="inline-flex items-center gap-1.5 bg-teal-700 hover:bg-teal-800 text-white px-3 py-2 rounded-lg text-xs font-semibold shadow transition">
        <i class="fas fa-syringe"></i>
        <span>Child TCL-2</span>
      </a>
      <a href="/HealthLogs/public/maternal/tcl.php" class="inline-flex items-center gap-1.5 bg-rose-700 hover:bg-rose-800 text-white px-3 py-2 rounded-lg text-xs font-semibold shadow transition">
        <i class="fas fa-person-pregnant"></i>
        <span>Maternal 8-ANC TCL</span>
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 bg-slate-900 text-white px-3.5 py-2 rounded-lg text-xs font-semibold shadow hover:bg-slate-800 transition">
        <i class="fas fa-print"></i>
        <span>Print Full Report</span>
      </button>
    </div>
  </div>
</div>

<!-- Dynamic Filter Bar -->
<form method="get" class="mt-6 bg-white p-4 sm:p-5 rounded-xl shadow print:hidden">
  <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold mb-3">Filter Options (Dynamic Filtering)</div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
      <input type="date" name="from" value="<?= h($fromDate) ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
      <input type="date" name="to" value="<?= h($toDate) ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Barangay</label>
      <select name="barangay" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All barangays</option>
        <?php foreach ($barangayOptions as $opt): ?>
          <option value="<?= h($opt) ?>" <?= $barangayFilter === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Gender / Sex</label>
      <select name="sex" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All genders</option>
        <option value="male" <?= $sexFilter === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $sexFilter === 'female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Age Group</label>
      <select name="age_group" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All age groups</option>
        <?php foreach ($validAgeGroups as $grp): ?>
          <option value="<?= h($grp) ?>" <?= $ageGroupFilter === $grp ? 'selected' : '' ?>><?= h($grp) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button type="submit" class="flex-1 bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-800 transition shadow">
        <i class="fas fa-filter mr-1 text-xs"></i> Apply
      </button>
      <a href="/HealthLogs/public/reports.php" class="flex-1 text-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm hover:bg-slate-50 transition">
        Reset
      </a>
    </div>
  </div>

  <?php if ($filterSummaryText !== 'All Records (No Filters Applied)'): ?>
    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
      <div class="flex items-center gap-1.5 flex-wrap">
        <span class="font-semibold text-slate-700">Active Filter:</span>
        <span class="inline-flex items-center bg-teal-50 text-teal-800 border border-teal-200 px-2.5 py-0.5 rounded-full font-medium">
          <?= h($filterSummaryText) ?>
        </span>
      </div>
      <span class="text-slate-500"><?= count($medicalRecordsRows) ?> patients found</span>
    </div>
  <?php endif; ?>
</form>

<!-- Section: Patient Medical Records -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Patient Medical Records</div>
      <p class="text-sm text-slate-500 mt-0.5">Complete list of registered patients with condition and allergy summaries (<?= count($medicalRecordsRows) ?> records total).</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-sm" onclick="printReportSection('section-medical-records', 'Official Report: Patient Medical Records')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print with Header & Signatory
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-sm" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'medical_records']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>

  <div id="section-medical-records">
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:hidden">
      <input id="medicalSearch" class="w-full sm:w-80 border rounded-lg px-3 py-2 text-sm" placeholder="Search patient, barangay, status..." />
      <div class="text-xs text-slate-500">10 rows per page (all <?= count($medicalRecordsRows) ?> shown on print)</div>
    </div>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="min-w-full text-sm min-w-[650px]" id="medicalTable">
      <thead>
        <tr class="border-b text-slate-500 uppercase text-xs bg-slate-50/75">
          <th class="text-left px-3 py-2.5">Patient</th>
          <th class="text-left px-3 py-2.5">Sex</th>
          <th class="text-left px-3 py-2.5">Birth Date</th>
          <th class="text-left px-3 py-2.5">Barangay</th>
          <th class="text-left px-3 py-2.5">Conditions</th>
          <th class="text-left px-3 py-2.5">Allergies</th>
          <th class="text-left px-3 py-2.5">Latest Diagnosis</th>
        </tr>
      </thead>
      <tbody id="medicalTableBody">
        <?php if (empty($medicalRecordsRows)): ?>
          <tr><td class="px-3 py-4 text-slate-500 text-center" colspan="7">No patient medical records found matching current filters.</td></tr>
        <?php else: ?>
          <?php foreach ($medicalRecordsRows as $row): ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/50">
              <td class="px-3 py-2 font-medium text-slate-900 whitespace-nowrap"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap capitalize"><?= h((string)$row['sex']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap"><?= h($row['birth_date']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap"><?= h($row['barangay']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap text-center"><?= h((string)$row['conditions_count']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap text-center"><?= h((string)$row['allergies_count']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap"><?= h($row['latest_diagnosis_date'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-3 flex items-center justify-between text-sm print:hidden">
      <div id="medicalPageInfo" class="text-slate-500 text-xs sm:text-sm"></div>
      <div class="flex gap-2">
        <button id="medicalPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm hover:bg-slate-50 transition">Prev</button>
        <button id="medicalNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm hover:bg-slate-50 transition">Next</button>
      </div>
    </div>
  </div>
</div>

<!-- Section: Patient Consultation -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Patient Consultation</div>
      <p class="text-sm text-slate-500 mt-0.5">Recorded general consultations matching active filters (<?= count($consultationRows) ?> records total).</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-sm" onclick="printReportSection('section-consultation', 'Official Report: Patient Consultations')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print with Header & Signatory
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-sm" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'consultation']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>

  <div id="section-consultation">
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:hidden">
      <input id="consultationSearch" class="w-full sm:w-80 border rounded-lg px-3 py-2 text-sm" placeholder="Search patient, barangay, reason, notes..." />
      <div class="text-xs text-slate-500">10 rows per page (all <?= count($consultationRows) ?> shown on print)</div>
    </div>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="min-w-full text-sm min-w-[600px]" id="consultationTable">
      <thead>
        <tr class="border-b text-slate-500 uppercase text-xs bg-slate-50/75">
          <th class="text-left px-3 py-2.5">Date</th>
          <th class="text-left px-3 py-2.5">Patient</th>
          <th class="text-left px-3 py-2.5">Barangay</th>
          <th class="text-left px-3 py-2.5">Reason</th>
          <th class="text-left px-3 py-2.5">Notes</th>
        </tr>
      </thead>
      <tbody id="consultationTableBody">
        <?php if (empty($consultationRows)): ?>
          <tr><td class="px-3 py-4 text-slate-500 text-center" colspan="5">No consultation records found matching current filters.</td></tr>
        <?php else: ?>
          <?php foreach ($consultationRows as $row): ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/50">
              <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800"><?= h($row['consult_date']) ?></td>
              <td class="px-3 py-2 font-medium text-slate-900 whitespace-nowrap"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2 whitespace-nowrap"><?= h($row['barangay']) ?></td>
              <td class="px-3 py-2"><?= h($row['reason'] ?: '—') ?></td>
              <td class="px-3 py-2"><?= h($row['notes'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-3 flex items-center justify-between text-sm print:hidden">
      <div id="consultationPageInfo" class="text-slate-500 text-xs sm:text-sm"></div>
      <div class="flex gap-2">
        <button id="consultationPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm hover:bg-slate-50 transition">Prev</button>
        <button id="consultationNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm hover:bg-slate-50 transition">Next</button>
      </div>
    </div>
  </div>
</div>

<!-- Section: Population Report by Age Group and Gender -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Population Report by Age Group and Gender</div>
      <p class="text-sm text-slate-500 mt-0.5">Demographic distribution for non-deceased community members.</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-sm" onclick="printReportSection('section-population', 'Official Demographic Report: Age & Gender Distribution')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print Chart
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-sm" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'population']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>
  <div id="section-population">
    <div class="mt-4 relative min-h-[220px]">
      <canvas id="populationAgeGenderChart" height="120"></canvas>
    </div>
  </div>
</div>

<!-- Section: Forecasting -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex items-center justify-between">
    <div>
      <div class="text-lg font-semibold text-slate-900">Admission / Consultation Forecasting</div>
      <p class="text-sm text-slate-500 mt-0.5">Trend projections based on historic weekly clinic visits.</p>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mt-4">
    <div class="rounded-xl border border-slate-200 bg-slate-50/75 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Consultation Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($consultationForecast)): ?>
          <?php foreach ($consultationForecast as $i => $value): ?>
            <div class="flex justify-between border-b border-slate-200/60 pb-1.5 last:border-b-0 last:pb-0">
              <span>Week <?= h((string)($i + 1)) ?></span>
              <strong><?= h(number_format($value, 1)) ?> consultations</strong>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-slate-400">Not enough consultation records for forecasting.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50/75 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Total Visit / Intake Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($admissionForecast)): ?>
          <?php foreach ($admissionForecast as $i => $value): ?>
            <div class="flex justify-between border-b border-slate-200/60 pb-1.5 last:border-b-0 last:pb-0">
              <span>Week <?= h((string)($i + 1)) ?></span>
              <strong><?= h(number_format($value, 1)) ?> visits</strong>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-slate-400">Not enough visit records for forecasting.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Section: Seasonal Disease & Peak Months -->
<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300">
    <div class="text-lg font-semibold text-slate-900">Seasonal Disease Cases</div>
    <p class="text-sm text-slate-500 mt-0.5">Diagnosed disease distribution per calendar month.</p>
    <div class="mt-4 relative min-h-[200px]" id="section-seasonal">
      <canvas id="seasonalDiseaseChart" height="120"></canvas>
    </div>
  </div>
  <div class="bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300">
    <div class="text-lg font-semibold text-slate-900">Peak Infection Months</div>
    <p class="text-sm text-slate-500 mt-0.5">Highest recorded monthly incidence.</p>
    <ul class="mt-4 space-y-3 text-sm text-slate-700">
      <?php if (!empty($seasonalTopMonths)): ?>
        <?php foreach ($seasonalTopMonths as $row): ?>
          <li class="flex items-center justify-between border-b border-slate-100 pb-2.5 last:border-b-0 last:pb-0">
            <span class="font-medium text-slate-800"><?= h($row['month_label']) ?></span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
              <?= h((string)$row['total_cases']) ?> cases
            </span>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="text-slate-400">No condition diagnosis history recorded yet.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- Section: Disease Case Trends -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow print:shadow-none print:border print:border-slate-300">
  <div class="text-lg font-semibold text-slate-900">Disease Case Trends (Top 5 Diseases)</div>
  <p class="text-sm text-slate-500 mt-0.5">Monthly trajectory for top diagnosed conditions.</p>
  <div class="mt-4 relative min-h-[200px]" id="section-trends">
    <canvas id="diseaseTrendChart" height="120"></canvas>
  </div>
</div>

<!-- Printable Signatory Section (Visible on full page print) -->
<div id="printOfficialSignatories" class="hidden print:block mt-12 pt-6 border-t-2 border-slate-800 page-break-inside-avoid">
  <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold mb-6">Official Signatures & Approval</div>
  <div class="grid grid-cols-3 gap-6 text-center">
    <div>
      <div class="text-xs text-slate-600 text-left mb-10">Prepared by:</div>
      <div class="font-bold text-slate-900 text-sm border-b border-slate-800 pb-1 uppercase"><?= h($currentUserFullName) ?></div>
      <div class="text-xs text-slate-600 mt-1"><?= h($currentUserRole) ?></div>
      <div class="text-[10px] text-slate-400 mt-0.5">Date: <?= date('M d, Y') ?></div>
    </div>
    <div>
      <div class="text-xs text-slate-600 text-left mb-10">Verified by:</div>
      <div class="font-bold text-slate-900 text-sm border-b border-slate-800 pb-1 uppercase">___________________________</div>
      <div class="text-xs text-slate-600 mt-1">Supervising Public Health Nurse</div>
      <div class="text-[10px] text-slate-400 mt-0.5">Date: ____________________</div>
    </div>
    <div>
      <div class="text-xs text-slate-600 text-left mb-10">Approved by:</div>
      <div class="font-bold text-slate-900 text-sm border-b border-slate-800 pb-1 uppercase">___________________________</div>
      <div class="text-xs text-slate-600 mt-1">Municipal Health Officer / Physician</div>
      <div class="text-[10px] text-slate-400 mt-0.5">Date: ____________________</div>
    </div>
  </div>
  <div class="mt-8 text-center text-[10px] text-slate-400 border-t border-dashed border-slate-300 pt-2">
    Official HealthLogs Health Information System Summary Report • Generated on <?= h($currentDateTimeFormatted) ?> • Confidential
  </div>
</div>

<script>
  const seasonalDiseaseRows = <?= json_encode($seasonalDiseaseRows) ?>;
  const diseaseTrendMonths = <?= json_encode($diseaseTrendMonths) ?>;
  const diseaseTrendSeries = <?= json_encode($diseaseTrendSeries) ?>;
  const ageGroupLabels = <?= json_encode($ageGroupLabels) ?>;
  const maleCounts = <?= json_encode($maleCounts) ?>;
  const femaleCounts = <?= json_encode($femaleCounts) ?>;

  let seasonalChartInstance = null;
  let diseaseTrendChartInstance = null;
  let populationChartInstance = null;

  if (seasonalDiseaseRows.length) {
    seasonalChartInstance = new Chart(document.getElementById('seasonalDiseaseChart'), {
      type: 'bar',
      data: {
        labels: seasonalDiseaseRows.map((row) => row.month_label),
        datasets: [{
          label: 'Cases',
          data: seasonalDiseaseRows.map((row) => Number(row.total_cases)),
          backgroundColor: 'rgba(14,165,164,0.65)',
          borderColor: '#0ea5a4',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  const trendDiseaseNames = Object.keys(diseaseTrendSeries || {});
  if (diseaseTrendMonths.length && trendDiseaseNames.length) {
    const palette = ['#0ea5a4', '#2563eb', '#9333ea', '#f97316', '#16a34a'];
    const datasets = trendDiseaseNames.map((name, idx) => ({
      label: name,
      data: diseaseTrendSeries[name],
      borderColor: palette[idx % palette.length],
      backgroundColor: palette[idx % palette.length],
      tension: 0.25,
      pointRadius: 3,
      borderWidth: 2
    }));

    diseaseTrendChartInstance = new Chart(document.getElementById('diseaseTrendChart'), {
      type: 'line',
      data: { labels: diseaseTrendMonths, datasets },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  if (ageGroupLabels.length) {
    populationChartInstance = new Chart(document.getElementById('populationAgeGenderChart'), {
      type: 'bar',
      data: {
        labels: ageGroupLabels,
        datasets: [
          {
            label: 'Male',
            data: maleCounts,
            backgroundColor: 'rgba(37,99,235,0.7)',
            borderColor: '#2563eb',
            borderWidth: 1
          },
          {
            label: 'Female',
            data: femaleCounts,
            backgroundColor: 'rgba(236,72,153,0.65)',
            borderColor: '#ec4899',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          x: { stacked: false },
          y: { beginAtZero: true }
        }
      }
    });
  }

  function setupTableControls(options) {
    const tableBody = document.getElementById(options.bodyId);
    const searchInput = document.getElementById(options.searchId);
    const prevBtn = document.getElementById(options.prevId);
    const nextBtn = document.getElementById(options.nextId);
    const pageInfo = document.getElementById(options.infoId);
    if (!tableBody || !searchInput || !prevBtn || !nextBtn || !pageInfo) return;

    const pageSize = 10;
    let page = 1;
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const hasDataRows = allRows.some((row) => row.children.length > 1);
    if (!hasDataRows) return;

    function render() {
      const term = searchInput.value.trim().toLowerCase();
      const filtered = allRows.filter((row) => row.textContent.toLowerCase().includes(term));
      const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
      if (page > totalPages) page = totalPages;
      const start = (page - 1) * pageSize;
      const end = start + pageSize;

      allRows.forEach((row) => { row.style.display = 'none'; });
      filtered.slice(start, end).forEach((row) => { row.style.display = ''; });

      pageInfo.textContent = `Page ${page} of ${totalPages} (${filtered.length} matching rows)`;
      prevBtn.disabled = page <= 1;
      nextBtn.disabled = page >= totalPages;
      prevBtn.classList.toggle('opacity-50', prevBtn.disabled);
      nextBtn.classList.toggle('opacity-50', nextBtn.disabled);
    }

    searchInput.addEventListener('input', () => { page = 1; render(); });
    prevBtn.addEventListener('click', () => { if (page > 1) { page--; render(); } });
    nextBtn.addEventListener('click', () => { page++; render(); });
    render();
  }

  // Official Print Function with Logo, Date, Signatory, and All Patients
  function printReportSection(sectionId, reportTitle) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    // Clone section content
    const clone = section.cloneNode(true);

    // Ensure all table rows are visible in print (remove pagination display:none)
    clone.querySelectorAll('tr').forEach((row) => {
      row.style.display = '';
    });

    // Remove search and pagination controls from the print output
    clone.querySelectorAll('input, button, #medicalPageInfo, #consultationPageInfo, .print\\:hidden').forEach((el) => {
      el.remove();
    });

    // Handle any canvas inside the clone by converting source canvas to image
    const origCanvas = section.querySelector('canvas');
    if (origCanvas) {
      const img = document.createElement('img');
      img.src = origCanvas.toDataURL('image/png');
      img.style.maxWidth = '100%';
      img.style.height = 'auto';
      img.style.margin = '10px 0';
      const cloneCanvas = clone.querySelector('canvas');
      if (cloneCanvas && cloneCanvas.parentNode) {
        cloneCanvas.parentNode.replaceChild(img, cloneCanvas);
      }
    }

    const filterInfo = <?= json_encode($filterSummaryText) ?>;
    const printUser = <?= json_encode($currentUserFullName) ?>;
    const printRole = <?= json_encode($currentUserRole) ?>;
    const printDate = <?= json_encode($currentDateTimeFormatted) ?>;

    const win = window.open('', '_blank', 'width=1100,height=800');
    if (!win) {
      alert('Popup blocker prevented opening the print window. Please allow popups for this site.');
      return;
    }

    win.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <title>${reportTitle}</title>
        <style>
          @page {
            size: auto;
            margin: 15mm 12mm 15mm 12mm;
          }
          body {
            font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            line-height: 1.4;
          }
          .official-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
          }
          .header-center {
            text-align: center;
            flex: 1;
            padding: 0 15px;
          }
          .rep-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
          .agency-title { font-size: 11px; text-transform: uppercase; color: #334155; font-weight: 600; margin-top: 1px; }
          .hub-title { font-size: 15px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin-top: 2px; }
          .sys-title { font-size: 11px; color: #0f766e; font-weight: 700; margin-top: 1px; }
          .doc-meta-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
          }
          .doc-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
          }
          th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.5px;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
          }
          td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            color: #1e293b;
          }
          tr:nth-child(even) td {
            background-color: #f8fafc;
          }
          .signatory-grid {
            margin-top: 36px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            page-break-inside: avoid;
            text-align: center;
          }
          .sig-box {
            display: flex;
            flex-direction: column;
          }
          .sig-label {
            font-size: 10.5px;
            color: #475569;
            text-align: left;
            margin-bottom: 38px;
          }
          .sig-name {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            border-bottom: 1px solid #0f172a;
            padding-bottom: 2px;
          }
          .sig-role {
            font-size: 10px;
            color: #475569;
            margin-top: 3px;
          }
          .sig-date {
            font-size: 9.5px;
            color: #94a3b8;
            margin-top: 2px;
          }
          .watermark-footer {
            margin-top: 24px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
          }
        </style>
      </head>
      <body>
        <div class="official-header">
          <div>
            <svg width="60" height="60" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="50" cy="50" r="46" fill="#0f766e" stroke="#115e59" stroke-width="2"/>
              <circle cx="50" cy="50" r="41" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-dasharray="3 2"/>
              <path d="M43 25 h14 v18 h18 v14 h-18 v18 h-14 v-18 h-18 v-14 h18 z" fill="#0ea5a4" opacity="0.3"/>
              <rect x="44" y="24" width="12" height="52" rx="2" fill="#0f766e"/>
              <rect x="24" y="44" width="52" height="12" rx="2" fill="#0f766e"/>
              <circle cx="50" cy="50" r="7" fill="#ffffff"/>
              <path d="M50 45 L52 49 L56 50 L52 52 L50 56 L48 52 L44 50 L48 49 Z" fill="#0f766e"/>
            </svg>
          </div>
          <div class="header-center">
            <div class="rep-title">Republic of the Philippines</div>
            <div class="agency-title">Department of Health • Primary Care Services</div>
            <div class="hub-title">Barangay Health Center & Care Hub</div>
            <div class="sys-title">HealthLogs Information Management System</div>
          </div>
          <div style="text-align: right; font-size: 10px; color: #64748b;">
            <div><strong>Date:</strong> <?= date('M d, Y') ?></div>
            <div><strong>Time:</strong> <?= date('h:i A') ?></div>
          </div>
        </div>

        <div class="doc-meta-box">
          <div>
            <div class="doc-title">${reportTitle}</div>
            <div style="color: #475569; margin-top: 2px;"><strong>Filter Scope:</strong> ${filterInfo}</div>
          </div>
          <div style="text-align: right; color: #475569;">
            <div><strong>Generated By:</strong> ${printUser}</div>
            <div><strong>Designation:</strong> ${printRole}</div>
          </div>
        </div>

        <div class="report-content">
          ${clone.innerHTML}
        </div>

        <div class="signatory-grid">
          <div class="sig-box">
            <div class="sig-label">Prepared by:</div>
            <div class="sig-name">${printUser}</div>
            <div class="sig-role">${printRole}</div>
            <div class="sig-date">Date: <?= date('M d, Y') ?></div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Verified by:</div>
            <div class="sig-name">___________________________</div>
            <div class="sig-role">Supervising Public Health Nurse</div>
            <div class="sig-date">Date: ____________________</div>
          </div>
          <div class="sig-box">
            <div class="sig-label">Approved by:</div>
            <div class="sig-name">___________________________</div>
            <div class="sig-role">Municipal Health Officer / Physician</div>
            <div class="sig-date">Date: ____________________</div>
          </div>
        </div>

        <div class="watermark-footer">
          Official HealthLogs System Generated Document • Certified Medical & Program Records • Timestamp: ${printDate}
        </div>
      </body>
      </html>
    `);

    win.document.close();
    win.focus();
    setTimeout(() => {
      win.print();
    }, 400);
  }

  setupTableControls({
    bodyId: 'medicalTableBody',
    searchId: 'medicalSearch',
    prevId: 'medicalPrev',
    nextId: 'medicalNext',
    infoId: 'medicalPageInfo'
  });

  setupTableControls({
    bodyId: 'consultationTableBody',
    searchId: 'consultationSearch',
    prevId: 'consultationPrev',
    nextId: 'consultationNext',
    infoId: 'consultationPageInfo'
  });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
