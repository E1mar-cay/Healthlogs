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

try {
    $weeklySql = "SELECT
            YEARWEEK(visit_datetime, 1) AS week_key,
            COUNT(*) AS total_visits,
            SUM(CASE WHEN visit_type = 'general' THEN 1 ELSE 0 END) AS consultation_visits
        FROM visits
        WHERE visit_datetime >= DATE_SUB(CURDATE(), INTERVAL 140 DAY)
        GROUP BY YEARWEEK(visit_datetime, 1)
        ORDER BY week_key ASC";
    $weeklyRows = $pdo->query($weeklySql)->fetchAll(PDO::FETCH_ASSOC);

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

try {
    $seasonalSql = "SELECT
            MONTH(diagnosed_on) AS month_no,
            DATE_FORMAT(diagnosed_on, '%b') AS month_label,
            COUNT(*) AS total_cases
        FROM patient_conditions
        WHERE diagnosed_on IS NOT NULL
        GROUP BY MONTH(diagnosed_on), DATE_FORMAT(diagnosed_on, '%b')
        ORDER BY MONTH(diagnosed_on)";
    $seasonalDiseaseRows = $pdo->query($seasonalSql)->fetchAll(PDO::FETCH_ASSOC);

    $sortedSeason = $seasonalDiseaseRows;
    usort($sortedSeason, fn($a, $b) => (int)$b['total_cases'] <=> (int)$a['total_cases']);
    $seasonalTopMonths = array_slice($sortedSeason, 0, 3);
} catch (Throwable $e) {
    $seasonalDiseaseRows = [];
    $seasonalTopMonths = [];
}

try {
    $topDiseaseSql = "SELECT condition_name, COUNT(*) AS total_cases
        FROM patient_conditions
        WHERE diagnosed_on IS NOT NULL
        GROUP BY condition_name
        ORDER BY total_cases DESC
        LIMIT 5";
    $topDiseases = $pdo->query($topDiseaseSql)->fetchAll(PDO::FETCH_ASSOC);
    $topDiseaseNames = array_map(fn($d) => $d['condition_name'], $topDiseases);

    if (!empty($topDiseaseNames)) {
        $trendSql = "SELECT
                DATE_FORMAT(diagnosed_on, '%Y-%m') AS ym,
                condition_name,
                COUNT(*) AS total_cases
            FROM patient_conditions
            WHERE diagnosed_on >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND diagnosed_on IS NOT NULL
              AND condition_name IN (" . implode(',', array_fill(0, count($topDiseaseNames), '?')) . ")
            GROUP BY DATE_FORMAT(diagnosed_on, '%Y-%m'), condition_name
            ORDER BY ym ASC";
        $trendStmt = $pdo->prepare($trendSql);
        $trendStmt->execute($topDiseaseNames);
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
        ORDER BY p.id DESC
        LIMIT 20";
    $medicalStmt = $pdo->prepare($medicalRecordsSql);
    $medicalStmt->execute($medicalRecordsParams);
    $medicalRecordsRows = $medicalStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $medicalRecordsRows = [];
}

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
        ORDER BY v.visit_datetime DESC
        LIMIT 25";
    $consultationStmt = $pdo->prepare($consultationSql);
    $consultationStmt->execute($consultationParams);
    $consultationRows = $consultationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $consultationRows = [];
}

try {
    $ageGenderSql = "SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 0 AND 4 THEN '0-4'
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 5 AND 12 THEN '5-12'
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 13 AND 17 THEN '13-17'
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 35 THEN '18-35'
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 36 AND 59 THEN '36-59'
                ELSE '60+'
            END AS age_group,
            sex,
            COUNT(*) AS total
        FROM patients
        WHERE status <> 'deceased'
          " . ($barangayFilter !== '' ? "AND barangay = ?" : "") . "
          " . (in_array($sexFilter, $validSex, true) ? "AND sex = ?" : "") . "
          " . (in_array($ageGroupFilter, $validAgeGroups, true) ? "AND " . $buildAgeWhere('birth_date') . " = ?" : "") . "
        GROUP BY age_group, sex
        ORDER BY FIELD(age_group, '0-4','5-12','13-17','18-35','36-59','60+'), sex";
    $ageGenderParams = [];
    if ($barangayFilter !== '') {
        $ageGenderParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $ageGenderParams[] = $sexFilter;
    }
    if (in_array($ageGroupFilter, $validAgeGroups, true)) {
        $ageGenderParams[] = $ageGroupFilter;
    }
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

require __DIR__ . '/partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">Admin Reports</div>
  <div class="text-2xl font-semibold">Disease and Activity Reports</div>
  <p class="text-sm text-slate-500 mt-1">Disease values come from patient condition records (`patient_conditions`).</p>
</div>

<form method="get" class="mt-6 bg-white p-4 rounded shadow grid grid-cols-1 md:grid-cols-6 gap-3">
  <input type="date" name="from" value="<?= h($fromDate) ?>" class="w-full border rounded px-3 py-2" />
  <input type="date" name="to" value="<?= h($toDate) ?>" class="w-full border rounded px-3 py-2" />
  <select name="barangay" class="w-full border rounded px-3 py-2">
    <option value="">All barangays</option>
    <?php foreach ($barangayOptions as $opt): ?>
      <option value="<?= h($opt) ?>" <?= $barangayFilter === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="sex" class="w-full border rounded px-3 py-2">
    <option value="">All genders</option>
    <option value="male" <?= $sexFilter === 'male' ? 'selected' : '' ?>>Male</option>
    <option value="female" <?= $sexFilter === 'female' ? 'selected' : '' ?>>Female</option>
  </select>
  <select name="age_group" class="w-full border rounded px-3 py-2">
    <option value="">All age groups</option>
    <?php foreach ($validAgeGroups as $grp): ?>
      <option value="<?= h($grp) ?>" <?= $ageGroupFilter === $grp ? 'selected' : '' ?>><?= h($grp) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="flex gap-2">
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded">Apply</button>
    <a href="/HealthLogs/public/reports.php" class="px-4 py-2 rounded border border-slate-300 text-slate-700">Clear</a>
  </div>
</form>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="flex items-center justify-between gap-3">
    <div class="text-lg font-semibold">Patient Medical Records</div>
    <div class="flex items-center gap-3">
      <button type="button" class="text-sm text-slate-700" onclick="printSection('section-medical-records')">Print/PDF</button>
      <a class="text-sm text-blue-700" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'medical_records']))) ?>">Export CSV</a>
    </div>
  </div>
  <p class="text-sm text-slate-500 mt-1">Latest 20 patients with condition/allergy summary.</p>
  <div id="section-medical-records">
    <div class="mt-4 flex items-center justify-between gap-3">
      <input id="medicalSearch" class="w-full md:w-80 border rounded px-3 py-2 text-sm" placeholder="Search patient, barangay, status..." />
      <div class="text-xs text-slate-500">10 rows per page</div>
    </div>
    <div class="overflow-x-auto mt-4">
      <table class="min-w-full text-sm" id="medicalTable">
      <thead>
        <tr>
          <th class="text-left px-3 py-2">Patient</th>
          <th class="text-left px-3 py-2">Sex</th>
          <th class="text-left px-3 py-2">Birth Date</th>
          <th class="text-left px-3 py-2">Barangay</th>
          <th class="text-left px-3 py-2">Conditions</th>
          <th class="text-left px-3 py-2">Allergies</th>
          <th class="text-left px-3 py-2">Latest Diagnosis</th>
        </tr>
      </thead>
      <tbody id="medicalTableBody">
        <?php if (empty($medicalRecordsRows)): ?>
          <tr><td class="px-3 py-3 text-slate-500" colspan="7">No patient medical records to display.</td></tr>
        <?php else: ?>
          <?php foreach ($medicalRecordsRows as $row): ?>
            <tr class="border-t border-slate-100">
              <td class="px-3 py-2"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2"><?= h(ucfirst((string)$row['sex'])) ?></td>
              <td class="px-3 py-2"><?= h($row['birth_date']) ?></td>
              <td class="px-3 py-2"><?= h($row['barangay']) ?></td>
              <td class="px-3 py-2"><?= h((string)$row['conditions_count']) ?></td>
              <td class="px-3 py-2"><?= h((string)$row['allergies_count']) ?></td>
              <td class="px-3 py-2"><?= h($row['latest_diagnosis_date'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-3 flex items-center justify-between text-sm">
      <div id="medicalPageInfo" class="text-slate-500"></div>
      <div class="flex gap-2">
        <button id="medicalPrev" type="button" class="px-3 py-1 border border-slate-300 rounded text-slate-700">Prev</button>
        <button id="medicalNext" type="button" class="px-3 py-1 border border-slate-300 rounded text-slate-700">Next</button>
      </div>
    </div>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="flex items-center justify-between gap-3">
    <div class="text-lg font-semibold">Patient Consultation</div>
    <div class="flex items-center gap-3">
      <button type="button" class="text-sm text-slate-700" onclick="printSection('section-consultation')">Print/PDF</button>
      <a class="text-sm text-blue-700" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'consultation']))) ?>">Export CSV</a>
    </div>
  </div>
  <p class="text-sm text-slate-500 mt-1">Most recent 25 general consultation entries.</p>
  <div id="section-consultation">
    <div class="mt-4 flex items-center justify-between gap-3">
      <input id="consultationSearch" class="w-full md:w-80 border rounded px-3 py-2 text-sm" placeholder="Search patient, barangay, reason, notes..." />
      <div class="text-xs text-slate-500">10 rows per page</div>
    </div>
    <div class="overflow-x-auto mt-4">
      <table class="min-w-full text-sm" id="consultationTable">
      <thead>
        <tr>
          <th class="text-left px-3 py-2">Date</th>
          <th class="text-left px-3 py-2">Patient</th>
          <th class="text-left px-3 py-2">Barangay</th>
          <th class="text-left px-3 py-2">Reason</th>
          <th class="text-left px-3 py-2">Notes</th>
        </tr>
      </thead>
      <tbody id="consultationTableBody">
        <?php if (empty($consultationRows)): ?>
          <tr><td class="px-3 py-3 text-slate-500" colspan="5">No consultation records to display.</td></tr>
        <?php else: ?>
          <?php foreach ($consultationRows as $row): ?>
            <tr class="border-t border-slate-100">
              <td class="px-3 py-2"><?= h($row['consult_date']) ?></td>
              <td class="px-3 py-2"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2"><?= h($row['barangay']) ?></td>
              <td class="px-3 py-2"><?= h($row['reason'] ?: '-') ?></td>
              <td class="px-3 py-2"><?= h($row['notes'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-3 flex items-center justify-between text-sm">
      <div id="consultationPageInfo" class="text-slate-500"></div>
      <div class="flex gap-2">
        <button id="consultationPrev" type="button" class="px-3 py-1 border border-slate-300 rounded text-slate-700">Prev</button>
        <button id="consultationNext" type="button" class="px-3 py-1 border border-slate-300 rounded text-slate-700">Next</button>
      </div>
    </div>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="flex items-center justify-between gap-3">
    <div class="text-lg font-semibold">Population Report by Age Group and Gender</div>
    <div class="flex items-center gap-3">
      <button type="button" class="text-sm text-slate-700" onclick="printSection('section-population')">Print/PDF</button>
      <a class="text-sm text-blue-700" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'population']))) ?>">Export CSV</a>
    </div>
  </div>
  <div id="section-population">
    <p class="text-sm text-slate-500 mt-1">Counts of active/inactive (non-deceased) patients.</p>
    <canvas id="populationAgeGenderChart" height="120" class="mt-4"></canvas>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="text-lg font-semibold">Admission / Consultation Forecasting</div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
    <div class="rounded-lg border border-slate-200 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500">Consultation Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($consultationForecast)): ?>
          <?php foreach ($consultationForecast as $i => $value): ?>
            <div>Week <?= h((string)($i + 1)) ?>: <strong><?= h(number_format($value, 1)) ?></strong> consultations</div>
          <?php endforeach; ?>
        <?php else: ?>
          <div>Not enough consultation data yet.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="rounded-lg border border-slate-200 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-500">Admission Forecast (Next 4 Weeks)</div>
      <div class="mt-3 space-y-2 text-sm text-slate-700">
        <?php if (!empty($admissionForecast)): ?>
          <?php foreach ($admissionForecast as $i => $value): ?>
            <div>Week <?= h((string)($i + 1)) ?>: <strong><?= h(number_format($value, 1)) ?></strong> admissions</div>
          <?php endforeach; ?>
        <?php else: ?>
          <div>Not enough admission data yet.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 bg-white p-6 rounded shadow">
    <div class="text-lg font-semibold">Seasonal Disease</div>
    <canvas id="seasonalDiseaseChart" height="120" class="mt-4"></canvas>
  </div>
  <div class="bg-white p-6 rounded shadow">
    <div class="text-lg font-semibold">Peak Months</div>
    <ul class="mt-4 space-y-3 text-sm text-slate-700">
      <?php if (!empty($seasonalTopMonths)): ?>
        <?php foreach ($seasonalTopMonths as $row): ?>
          <li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0"><?= h($row['month_label']) ?>: <?= h((string)$row['total_cases']) ?> cases</li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>No disease diagnosis history yet.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded shadow">
  <div class="text-lg font-semibold">Disease Case Trends (Top 5, Last 12 Months)</div>
  <canvas id="diseaseTrendChart" height="120" class="mt-4"></canvas>
</div>

<script>
  const seasonalDiseaseRows = <?= json_encode($seasonalDiseaseRows) ?>;
  const diseaseTrendMonths = <?= json_encode($diseaseTrendMonths) ?>;
  const diseaseTrendSeries = <?= json_encode($diseaseTrendSeries) ?>;
  const ageGroupLabels = <?= json_encode($ageGroupLabels) ?>;
  const maleCounts = <?= json_encode($maleCounts) ?>;
  const femaleCounts = <?= json_encode($femaleCounts) ?>;

  if (seasonalDiseaseRows.length) {
    new Chart(document.getElementById('seasonalDiseaseChart'), {
      type: 'bar',
      data: {
        labels: seasonalDiseaseRows.map((row) => row.month_label),
        datasets: [{
          label: 'Cases',
          data: seasonalDiseaseRows.map((row) => Number(row.total_cases)),
          backgroundColor: 'rgba(14,165,164,0.6)',
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
      pointRadius: 2,
      borderWidth: 2
    }));

    new Chart(document.getElementById('diseaseTrendChart'), {
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
    new Chart(document.getElementById('populationAgeGenderChart'), {
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

      pageInfo.textContent = `Page ${page} of ${totalPages} (${filtered.length} results)`;
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

  function printSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    const win = window.open('', '_blank', 'width=1000,height=700');
    if (!win) return;
    win.document.write(`
      <html>
      <head>
        <title>Report Export</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 24px; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background: #f3f4f6; }
          canvas { max-width: 100%; }
        </style>
      </head>
      <body>${section.innerHTML}</body>
      </html>
    `);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 300);
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
