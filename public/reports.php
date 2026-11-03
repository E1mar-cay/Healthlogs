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
$seasonalDatasets = [];
$allDiseaseSet = [];
$totalSeasonalCases = 0;
$diseaseTrendMonths = [];
$diseaseTrendSeries = [];
$medicalRecordsRows = [];
$consultationRows = [];
$ageGenderRows = [];
$ageGroupLabels = [];
$maleCounts = [];
$femaleCounts = [];
$barangayOptions = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

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

    $monthLabels = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
    ];

    $seasonalSql = "SELECT
            MONTH(pc.diagnosed_on) AS month_no,
            pc.condition_name,
            COUNT(*) AS total_cases
        FROM patient_conditions pc
        INNER JOIN patients p ON p.id = pc.patient_id
        WHERE " . implode(' AND ', $seasonalWhere) . "
        GROUP BY MONTH(pc.diagnosed_on), pc.condition_name
        ORDER BY MONTH(pc.diagnosed_on) ASC, total_cases DESC";
    $seasonalStmt = $pdo->prepare($seasonalSql);
    $seasonalStmt->execute($seasonalParams);
    $breakdownRows = $seasonalStmt->fetchAll(PDO::FETCH_ASSOC);

    $monthDiseaseMap = [];
    foreach ($breakdownRows as $row) {
        $m = (int)$row['month_no'];
        $cond = $row['condition_name'];
        $cnt = (int)$row['total_cases'];
        $monthDiseaseMap[$m][$cond] = $cnt;
        $allDiseaseSet[$cond] = ($allDiseaseSet[$cond] ?? 0) + $cnt;
    }
    arsort($allDiseaseSet);
    $totalSeasonalCases = array_sum($allDiseaseSet);

    // Build complete 12 calendar months array (ensuring Jan-Dec are always represented)
    for ($m = 1; $m <= 12; $m++) {
        $diseasesInMonth = $monthDiseaseMap[$m] ?? [];
        $totalMonthCases = array_sum($diseasesInMonth);
        $diseaseParts = [];
        foreach ($diseasesInMonth as $name => $cnt) {
            $diseaseParts[] = "$name ($cnt)";
        }
        $seasonalDiseaseRows[] = [
            'month_no' => $m,
            'month_label' => $monthLabels[$m],
            'total_cases' => $totalMonthCases,
            'diseases' => $diseasesInMonth,
            'disease_summary' => !empty($diseaseParts) ? implode(', ', $diseaseParts) : 'No cases recorded'
        ];
    }

    $sortedSeason = $seasonalDiseaseRows;
    usort($sortedSeason, fn($a, $b) => (int)$b['total_cases'] <=> (int)$a['total_cases']);
    $seasonalTopMonths = array_filter(array_slice($sortedSeason, 0, 3), fn($r) => $r['total_cases'] > 0);

    // Muted slate palette keeps the charts consistent with the reports UI.
    $diseaseColors = [
        'Arthritis' => '#14b8a6',
        'Hypertension' => '#60a5fa',
        'Migraine' => '#fbbf24',
        'Diabetes Type 2' => '#a78bfa',
        'Allergic Rhinitis' => '#34d399',
        'Asthma' => '#fb7185',
        'Hypothyroidism' => '#818cf8',
    ];
    $fallbackPalette = ['#14b8a6', '#60a5fa', '#fbbf24', '#a78bfa', '#34d399', '#fb7185', '#818cf8'];
    $colorIdx = 0;

    foreach ($allDiseaseSet as $diseaseName => $overallCount) {
        $color = $diseaseColors[$diseaseName] ?? ($fallbackPalette[$colorIdx++ % count($fallbackPalette)]);
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = $monthDiseaseMap[$m][$diseaseName] ?? 0;
        }
        $seasonalDatasets[] = [
            'label' => $diseaseName,
            'data' => $data,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'borderWidth' => 1,
            'borderRadius' => 3,
        ];
    }
} catch (Throwable $e) {
    $seasonalDiseaseRows = [];
    $seasonalTopMonths = [];
    $seasonalDatasets = [];
    $allDiseaseSet = [];
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
            // Anchor to the latest condition diagnosis date in database for accurate historical trend
            $refConditionDate = $pdo->query("SELECT COALESCE(MAX(DATE(pc.diagnosed_on)), CURDATE()) FROM patient_conditions pc INNER JOIN patients p ON p.id = pc.patient_id")->fetchColumn();
            $trendWhere[] = "pc.diagnosed_on >= DATE_SUB(?, INTERVAL 12 MONTH)";
            $trendParams[] = $refConditionDate;
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
            GROUP_CONCAT(DISTINCT pc.condition_name ORDER BY pc.condition_name SEPARATOR ', ') AS conditions_list,
            GROUP_CONCAT(DISTINCT pa.allergen ORDER BY pa.allergen SEPARATOR ', ') AS allergies_list,
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

// 6. Number of Patients per Classification (Item 6: RHU Lifecourse & Priority Groups)
$allClassificationsMeta = PatientClassifier::getClassifications();
$classificationSummaryRows = [];
$totalClassifiedPatients = 0;
$specialPoverty = ['pwd_count' => 0, 'four_ps_count' => 0];

try {
    $classWhere = ["p.status <> 'deceased'"];
    $classParams = [];
    if ($barangayFilter !== '') {
        $classWhere[] = "p.barangay = ?";
        $classParams[] = $barangayFilter;
    }
    if (in_array($sexFilter, $validSex, true)) {
        $classWhere[] = "p.sex = ?";
        $classParams[] = $sexFilter;
    }
    if ($fromDate !== '') {
        $classWhere[] = "DATE(p.created_at) >= ?";
        $classParams[] = $fromDate;
    }
    if ($toDate !== '') {
        $classWhere[] = "DATE(p.created_at) <= ?";
        $classParams[] = $toDate;
    }

    $classSql = "SELECT
            COALESCE(NULLIF(p.classification, ''), 'adult') AS classification_key,
            SUM(CASE WHEN p.sex = 'male' THEN 1 ELSE 0 END) AS male_count,
            SUM(CASE WHEN p.sex = 'female' THEN 1 ELSE 0 END) AS female_count,
            COUNT(*) AS total_count
        FROM patients p
        WHERE " . implode(' AND ', $classWhere) . "
        GROUP BY classification_key
        ORDER BY total_count DESC";
    $classStmt = $pdo->prepare($classSql);
    $classStmt->execute($classParams);
    $rawClassRows = $classStmt->fetchAll(PDO::FETCH_ASSOC);

    $classCountsMap = [];
    foreach ($rawClassRows as $r) {
        $classCountsMap[$r['classification_key']] = $r;
        $totalClassifiedPatients += (int)$r['total_count'];
    }

    // Build complete classification matrix ensuring all standard DOH/RHU groups exist
    foreach ($allClassificationsMeta as $cKey => $cMeta) {
        $mCount = (int)($classCountsMap[$cKey]['male_count'] ?? 0);
        $fCount = (int)($classCountsMap[$cKey]['female_count'] ?? 0);
        $tCount = (int)($classCountsMap[$cKey]['total_count'] ?? 0);
        $pct = $totalClassifiedPatients > 0 ? round(($tCount / $totalClassifiedPatients) * 100, 1) : 0;

        $classificationSummaryRows[] = [
            'key' => $cKey,
            'label' => $cMeta['label'],
            'short_label' => $cMeta['short_label'],
            'category' => $cMeta['category'],
            'description' => $cMeta['description'],
            'badge' => $cMeta['badge'],
            'icon' => $cMeta['icon'],
            'priority' => $cMeta['priority'],
            'male_count' => $mCount,
            'female_count' => $fCount,
            'total_count' => $tCount,
            'pct' => $pct
        ];
    }

    // Sort by priority and total count
    usort($classificationSummaryRows, function($a, $b) {
        return $b['total_count'] <=> $a['total_count'];
    });

    // PWD & 4Ps flags
    $specialPovertySql = "SELECT
            SUM(CASE WHEN p.is_pwd = 1 THEN 1 ELSE 0 END) AS pwd_count,
            SUM(CASE WHEN p.is_4ps = 1 THEN 1 ELSE 0 END) AS four_ps_count
        FROM patients p
        WHERE " . implode(' AND ', $classWhere);
    $specialPovertyStmt = $pdo->prepare($specialPovertySql);
    $specialPovertyStmt->execute($classParams);
    $specialPoverty = $specialPovertyStmt->fetch(PDO::FETCH_ASSOC) ?: ['pwd_count' => 0, 'four_ps_count' => 0];

} catch (Throwable $e) {
    $classificationSummaryRows = [];
    $totalClassifiedPatients = 0;
}

// Handle CSV Exports
if ($exportType !== '') {
    if ($exportType === 'medical_records') {
        $rows = array_map(function (array $row): array {
            return [
                $row['id'],
                $row['last_name'] . ', ' . $row['first_name'],
                ucfirst((string)$row['sex']),
                $row['birth_date'],
                $row['barangay'] ?: 'Barangay Tangcul',
                ucfirst((string)$row['status']),
                $row['conditions_list'] ?: 'None',
                $row['allergies_list'] ?: 'None',
                $row['latest_diagnosis_date'] ? date('M d, Y', strtotime($row['latest_diagnosis_date'])) : 'None',
            ];
        }, $medicalRecordsRows);
        $csvOutput('patient_medical_records_tangcul.csv', ['ID', 'Patient', 'Sex', 'Birth Date', 'Purok', 'Status', 'Conditions', 'Allergies', 'Latest Diagnosis'], $rows);
    } elseif ($exportType === 'consultation') {
        $rows = array_map(function (array $row): array {
            return [
                $row['consult_date'],
                $row['last_name'] . ', ' . $row['first_name'],
                $row['barangay'] ?: 'Barangay Tangcul',
                $row['reason'] ?: '',
                $row['notes'] ?: '',
            ];
        }, $consultationRows);
        $csvOutput('patient_consultation_tangcul.csv', ['Date', 'Patient', 'Purok', 'Reason', 'Notes'], $rows);
    } elseif ($exportType === 'population') {
        $rows = [];
        foreach ($ageGroupLabels as $idx => $label) {
            $rows[] = [$label, $maleCounts[$idx] ?? 0, $femaleCounts[$idx] ?? 0];
        }
        $csvOutput('population_by_age_gender_tangcul.csv', ['Age Group', 'Male', 'Female'], $rows);
    } elseif ($exportType === 'classification') {
        $rows = array_map(function (array $c): array {
            return [
                $c['label'],
                $c['category'],
                $c['male_count'],
                $c['female_count'],
                $c['total_count'],
                $c['pct'] . '%',
                $c['priority'] ? 'Yes' : 'No'
            ];
        }, $classificationSummaryRows);
        $csvOutput('patients_by_classification_tangcul.csv', ['Classification', 'Category', 'Male', 'Female', 'Total Count', 'Percentage', 'RHU Priority'], $rows);
    }
}

// Prepare filter summary text for display & print metadata
$filterSummaryParts = [];
if ($fromDate !== '' || $toDate !== '') {
    $filterSummaryParts[] = 'Period: ' . ($fromDate ?: 'Start') . ' to ' . ($toDate ?: 'Present');
}
if ($barangayFilter !== '') {
    $filterSummaryParts[] = 'Purok: ' . $barangayFilter;
}
if ($sexFilter !== '') {
    $filterSummaryParts[] = 'Gender: ' . ucfirst($sexFilter);
}
if ($ageGroupFilter !== '') {
    $filterSummaryParts[] = 'Age Group: ' . $ageGroupFilter;
}
$filterSummaryText = !empty($filterSummaryParts) ? implode(' | ', $filterSummaryParts) : 'All Records (Barangay Tangcul)';

$currentUserFullName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Health Center Staff';
$currentUserRole = ($_SESSION['role'] ?? '') === 'admin' ? 'System Administrator / Admin' : 'Barangay Health Worker (BHW)';
$currentDateTimeFormatted = date('F j, Y, h:i A');

require __DIR__ . '/partials/header.php';
?>

<style>
  .app-main [class*=" bg-teal-"], .app-main [class^="bg-teal-"],
  .app-main [class*=" bg-indigo-"], .app-main [class^="bg-indigo-"],
  .app-main [class*=" bg-emerald-"], .app-main [class^="bg-emerald-"],
  .app-main [class*=" bg-rose-"], .app-main [class^="bg-rose-"],
  .app-main [class*=" bg-amber-"], .app-main [class^="bg-amber-"],
  .app-main [class*=" bg-sky-"], .app-main [class^="bg-sky-"],
  .app-main [class*=" bg-purple-"], .app-main [class^="bg-purple-"],
  .app-main [class*=" bg-blue-"], .app-main [class^="bg-blue-"],
  .app-main [class*=" bg-orange-"], .app-main [class^="bg-orange-"],
  .app-main [class*=" bg-pink-"], .app-main [class^="bg-pink-"],
  .app-main [class*=" bg-cyan-"], .app-main [class^="bg-cyan-"],
  .app-main [class*=" bg-yellow-"], .app-main [class^="bg-yellow-"],
  .app-main [class*=" bg-red-"], .app-main [class^="bg-red-"],
  .app-main [class*=" bg-green-"], .app-main [class^="bg-green-"] {
    background-color: #f1f5f9 !important;
  }

  .app-main [class*=" bg-teal-6"], .app-main [class*=" bg-teal-7"], .app-main [class*=" bg-teal-8"], .app-main [class*=" bg-teal-9"],
  .app-main [class*=" bg-indigo-6"], .app-main [class*=" bg-indigo-7"], .app-main [class*=" bg-indigo-8"], .app-main [class*=" bg-indigo-9"],
  .app-main [class*=" bg-emerald-6"], .app-main [class*=" bg-emerald-7"], .app-main [class*=" bg-emerald-8"], .app-main [class*=" bg-emerald-9"],
  .app-main [class*=" bg-rose-6"], .app-main [class*=" bg-rose-7"], .app-main [class*=" bg-rose-8"], .app-main [class*=" bg-rose-9"],
  .app-main [class*=" bg-amber-6"], .app-main [class*=" bg-amber-7"], .app-main [class*=" bg-amber-8"], .app-main [class*=" bg-amber-9"],
  .app-main [class*=" bg-sky-6"], .app-main [class*=" bg-sky-7"], .app-main [class*=" bg-sky-8"], .app-main [class*=" bg-sky-9"],
  .app-main [class*=" bg-purple-6"], .app-main [class*=" bg-purple-7"], .app-main [class*=" bg-purple-8"], .app-main [class*=" bg-purple-9"],
  .app-main [class*=" bg-blue-6"], .app-main [class*=" bg-blue-7"], .app-main [class*=" bg-blue-8"], .app-main [class*=" bg-blue-9"],
  .app-main [class*=" bg-orange-6"], .app-main [class*=" bg-orange-7"], .app-main [class*=" bg-orange-8"], .app-main [class*=" bg-orange-9"],
  .app-main [class*=" bg-pink-6"], .app-main [class*=" bg-pink-7"], .app-main [class*=" bg-pink-8"], .app-main [class*=" bg-pink-9"],
  .app-main [class*=" bg-cyan-6"], .app-main [class*=" bg-cyan-7"], .app-main [class*=" bg-cyan-8"], .app-main [class*=" bg-cyan-9"],
  .app-main [class*=" bg-yellow-6"], .app-main [class*=" bg-yellow-7"], .app-main [class*=" bg-yellow-8"], .app-main [class*=" bg-yellow-9"],
  .app-main [class*=" bg-red-6"], .app-main [class*=" bg-red-7"], .app-main [class*=" bg-red-8"], .app-main [class*=" bg-red-9"],
  .app-main [class*=" bg-green-6"], .app-main [class*=" bg-green-7"], .app-main [class*=" bg-green-8"], .app-main [class*=" bg-green-9"] {
    background-color: #475569 !important;
  }

  .app-main [class*="text-teal-"], .app-main [class*="text-indigo-"],
  .app-main [class*="text-emerald-"], .app-main [class*="text-rose-"],
  .app-main [class*="text-amber-"], .app-main [class*="text-sky-"],
  .app-main [class*="text-purple-"], .app-main [class*="text-blue-"],
  .app-main [class*="text-orange-"], .app-main [class*="text-pink-"],
  .app-main [class*="text-cyan-"], .app-main [class*="text-yellow-"],
  .app-main [class*="text-red-"], .app-main [class*="text-green-"] {
    color: #475569 !important;
  }

  .app-main [class*="border-teal-"], .app-main [class*="border-indigo-"],
  .app-main [class*="border-emerald-"], .app-main [class*="border-rose-"],
  .app-main [class*="border-amber-"], .app-main [class*="border-sky-"],
  .app-main [class*="border-purple-"], .app-main [class*="border-blue-"],
  .app-main [class*="border-orange-"], .app-main [class*="border-pink-"],
  .app-main [class*="border-cyan-"], .app-main [class*="border-yellow-"],
  .app-main [class*="border-red-"], .app-main [class*="border-green-"] {
    border-color: #e2e8f0 !important;
  }

  .app-main [class*="hover:bg-teal-"]:hover, .app-main [class*="hover:bg-indigo-"]:hover,
  .app-main [class*="hover:bg-emerald-"]:hover, .app-main [class*="hover:bg-rose-"]:hover,
  .app-main [class*="hover:bg-amber-"]:hover, .app-main [class*="hover:bg-sky-"]:hover,
  .app-main [class*="hover:bg-purple-"]:hover, .app-main [class*="hover:bg-blue-"]:hover,
  .app-main [class*="hover:bg-orange-"]:hover, .app-main [class*="hover:bg-pink-"]:hover,
  .app-main [class*="hover:bg-cyan-"]:hover, .app-main [class*="hover:bg-yellow-"]:hover,
  .app-main [class*="hover:bg-red-"]:hover, .app-main [class*="hover:bg-green-"]:hover {
    background-color: #e2e8f0 !important;
  }

  .app-main [class*="hover:text-teal-"]:hover, .app-main [class*="hover:text-indigo-"]:hover,
  .app-main [class*="hover:text-emerald-"]:hover, .app-main [class*="hover:text-rose-"]:hover,
  .app-main [class*="hover:text-amber-"]:hover, .app-main [class*="hover:text-sky-"]:hover,
  .app-main [class*="hover:text-purple-"]:hover, .app-main [class*="hover:text-blue-"]:hover,
  .app-main [class*="hover:text-orange-"]:hover, .app-main [class*="hover:text-pink-"]:hover,
  .app-main [class*="hover:text-cyan-"]:hover, .app-main [class*="hover:text-yellow-"]:hover,
  .app-main [class*="hover:text-red-"]:hover, .app-main [class*="hover:text-green-"]:hover {
    color: #0f172a !important;
  }

  .app-main [class*="shadow"] {
    box-shadow: none !important;
  }

  #tclSection a {
    background-color: #fff !important;
    border-color: #e2e8f0 !important;
    border-radius: 0.75rem !important;
    box-shadow: none !important;
  }
  #tclSection a:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    filter: none;
  }
  #tclSection a span:first-child {
    background-color: #f0fdfa !important;
    color: #0f766e !important;
    box-shadow: none !important;
  }
  #tclSection a > div > div > div:first-child {
    color: #0f172a !important;
  }
  #tclSection a > div > div > div:last-child {
    color: #64748b !important;
  }

  #reportsFilters button[type="submit"] {
    background-color: #0f766e !important;
    color: #fff !important;
  }
  #reportsFilters button[type="submit"]:hover { background-color: #115e59 !important; }
  #reportsFilters .active-filter {
    background-color: #f0fdfa !important;
    color: #115e59 !important;
    border-color: #99f6e4 !important;
  }

  #classificationSummaryCards > div {
    background-color: #fff !important;
    border-color: #e2e8f0 !important;
    border-radius: 0.75rem !important;
    box-shadow: none !important;
  }
  #classificationSummaryCards > div > div:first-child { color: #475569 !important; }
  #classificationSummaryCards > div > div:first-child i { color: #0f766e !important; }
  #classificationSummaryCards > div > div:nth-child(2) { color: #0f172a !important; }
  #classificationSummaryCards > div > div:last-child { color: #64748b !important; }

  .app-main > div.bg-white[class*="border-slate-"] {
    border-color: #e2e8f0 !important;
    border-radius: 0.875rem !important;
    box-shadow: none !important;
  }

  #reportsFilters {
    border-color: #e2e8f0 !important;
    border-radius: 0.875rem !important;
    box-shadow: none !important;
  }

  #reportsFilters input,
  #reportsFilters select {
    border-color: #cbd5e1;
    border-radius: 0.5rem;
    background-color: #fff;
  }

  #reportsFilters input:focus,
  #reportsFilters select:focus {
    outline: none;
    border-color: #0f766e;
    box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.12);
  }

  .app-main table thead tr {
    background-color: #f8fafc;
  }
</style>

<!-- Printable Official Header (Visible only when full-page printing) -->
<div id="printOfficialHeader" class="hidden print:block mb-6 border-b-2 border-slate-800 pb-4">
  <div class="flex items-center justify-between gap-4">
    <div class="shrink-0">
      <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" class="w-16 h-16 rounded-xl object-cover border border-slate-300 shadow-xs">
    </div>
    <div class="text-center flex-1">
      <div class="text-xs uppercase tracking-widest text-slate-600 font-semibold">Republic of the Philippines • Province of Isabela • City of Ilagan</div>
      <div class="text-xs uppercase tracking-wider text-slate-700 font-medium">Barangay Tangcul Primary Care & Health Services</div>
      <div class="text-base font-bold text-slate-900 tracking-wide uppercase mt-0.5">Barangay Tangcul Health Station & Care Hub</div>
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

<div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 print:hidden">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs uppercase tracking-wider text-teal-700 font-semibold mb-1">Health Analytics &amp; Master Registries</div>
      <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Reports &amp; Program Registers (Barangay Tangcul)</h1>
      <p class="text-sm text-slate-500 mt-1">Official DOH Target Client Lists (TCL), demographic lifecourse cohorts, consultations, and epidemiological disease trends.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition">
        <i class="fas fa-print text-xs"></i>
        <span>Print Full Report</span>
      </button>
    </div>
  </div>

  <!-- Item 11: Target Client Lists (TCL) by Program Hub -->
  <div id="tclSection" class="mt-5 pt-4 border-t border-slate-100">
    <div class="flex items-center justify-between mb-3">
      <div class="text-xs uppercase tracking-widest font-bold text-slate-500 flex items-center gap-2">
        <i class="fas fa-folder-closed text-teal-600"></i>
        <span>Official Target Client Lists (TCL) By Health Program</span>
      </div>
      <span class="text-[11px] text-slate-400">Department of Health Master Clinical Registries</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Child Immunization TCL-2 -->
      <a href="/HealthLogs/public/immunization/tcl.php" class="p-3.5 rounded-xl border border-teal-200 bg-teal-50/60 hover:bg-teal-50 hover:border-teal-300 transition block group shadow-2xs">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform">
            <i class="fas fa-syringe"></i>
          </span>
          <div>
            <div class="text-xs font-bold text-slate-900 group-hover:text-teal-700 transition">Child Immunization (TCL-2)</div>
            <div class="text-[10px] text-teal-700 font-medium">0–23 Months Infant Cohort</div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Vaccines, antigens, fully-immunized child (FIC) tracking.</p>
      </a>

      <!-- Maternal 8-ANC TCL -->
      <a href="/HealthLogs/public/maternal/tcl.php" class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/60 hover:bg-rose-50 hover:border-rose-300 transition block group shadow-2xs">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform">
            <i class="fas fa-person-pregnant"></i>
          </span>
          <div>
            <div class="text-xs font-bold text-slate-900 group-hover:text-rose-700 transition">Maternal Health (8-ANC TCL)</div>
            <div class="text-[10px] text-rose-700 font-medium">Pregnancies &amp; 4-PNC Registry</div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Antenatal care, risk factors, tetanus toxoid, postpartum.</p>
      </a>

      <!-- NCD TCL -->
      <a href="/HealthLogs/public/ncd/tcl.php" class="p-3.5 rounded-xl border border-blue-200 bg-blue-50/60 hover:bg-blue-50 hover:border-blue-300 transition block group shadow-2xs">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform">
            <i class="fas fa-heart-pulse"></i>
          </span>
          <div>
            <div class="text-xs font-bold text-slate-900 group-hover:text-blue-700 transition">Non-Communicable (TCL-NCD)</div>
            <div class="text-[10px] text-blue-700 font-medium">Hypertension &amp; Diabetes PhilPEN</div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Risk stratification, BP tracking, blood glucose, meds.</p>
      </a>

      <!-- Family Planning TCL -->
      <a href="/HealthLogs/public/family_planning/tcl.php" class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/60 hover:bg-purple-50 hover:border-purple-300 transition block group shadow-2xs">
        <div class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform">
            <i class="fas fa-venus-mars"></i>
          </span>
          <div>
            <div class="text-xs font-bold text-slate-900 group-hover:text-purple-700 transition">Family Planning (TCL-FP)</div>
            <div class="text-[10px] text-purple-700 font-medium">Contraceptive Users &amp; Refills</div>
          </div>
        </div>
        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Pills, DMPA injectables, condoms, IUD follow-ups.</p>
      </a>
    </div>
  </div>
</div>

<!-- Dynamic Filter Bar -->
<form id="reportsFilters" method="get" class="mt-6 bg-white p-4 sm:p-5 rounded-xl shadow-xs border border-slate-100 print:hidden">
  <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold mb-3">Filter Options (Dynamic Filtering)</div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
      <input type="date" name="from" value="<?= h($fromDate) ?>" class="w-full border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-slate-50/50" />
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
      <input type="date" name="to" value="<?= h($toDate) ?>" class="w-full border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-slate-50/50" />
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Purok (Brgy. Tangcul)</label>
      <select name="barangay" class="w-full border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Puroks</option>
        <?php foreach ($barangayOptions as $opt): ?>
          <option value="<?= h($opt) ?>" <?= $barangayFilter === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Gender / Sex</label>
      <select name="sex" class="w-full border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All genders</option>
        <option value="male" <?= $sexFilter === 'male' ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $sexFilter === 'female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-600 mb-1">Age Group</label>
      <select name="age_group" class="w-full border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All age groups</option>
        <?php foreach ($validAgeGroups as $grp): ?>
          <option value="<?= h($grp) ?>" <?= $ageGroupFilter === $grp ? 'selected' : '' ?>><?= h($grp) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="flex items-end gap-2">
      <button type="submit" class="flex-1 bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-800 transition shadow-xs">
        <i class="fas fa-filter mr-1 text-xs"></i> Apply
      </button>
      <a href="/HealthLogs/public/reports.php" class="flex-1 text-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
        Reset
      </a>
    </div>
  </div>

  <?php if ($filterSummaryText !== 'All Records (Barangay Tangcul)'): ?>
    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
      <div class="flex items-center gap-1.5 flex-wrap">
        <span class="font-semibold text-slate-700">Active Filter:</span>
        <span class="active-filter inline-flex items-center bg-teal-50 text-teal-800 border border-teal-200 px-2.5 py-0.5 rounded-full font-medium">
          <?= h($filterSummaryText) ?>
        </span>
      </div>
      <span class="text-slate-500 font-medium"><?= count($medicalRecordsRows) ?> patients found</span>
    </div>
  <?php endif; ?>
</form>
<!-- ============================================================ -->
<!-- Item 6: Number of Patients per Classification Section       -->
<!-- ============================================================ -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-2xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 mb-1">
        <i class="fas fa-layer-group text-[10px]"></i> RHU Priority Demographics
      </div>
      <div class="text-xl font-bold text-slate-900 tracking-tight">Number of Patients per Classification</div>
      <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Distribution of registered community members across DOH lifecourse stages and priority clinical cohorts (Total: <?= number_format($totalClassifiedPatients) ?> patients).</p>
    </div>
    <div class="flex items-center gap-2 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-xs font-semibold text-slate-700 hover:text-slate-900 border border-slate-200 bg-white px-3 py-1.5 rounded-xl hover:bg-slate-50 transition shadow-2xs" onclick="printReportSection('section-patient-classification', 'Official Report: Patients by Classification')">
        <i class="fas fa-print mr-1.5 text-xs text-indigo-600"></i>Print Section
      </button>
      <a class="inline-flex items-center text-xs font-semibold text-indigo-700 hover:text-indigo-900 border border-indigo-200 bg-indigo-50/70 px-3 py-1.5 rounded-xl hover:bg-indigo-100 transition shadow-2xs" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'export' => 'classification']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>

  <div id="section-patient-classification">
    <!-- Quick Cohort Summary Cards -->
    <div id="classificationSummaryCards" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-4 print:grid-cols-5">
      <?php
        $pediatricTotal = 0;
        $maternalTotal = 0;
        $seniorTotal = 0;
        $adultTotal = 0;
        foreach ($classificationSummaryRows as $cr) {
            if (in_array($cr['key'], ['infant', 'under_five', 'school_age'], true)) $pediatricTotal += $cr['total_count'];
            if (in_array($cr['key'], ['pregnant', 'postpartum'], true)) $maternalTotal += $cr['total_count'];
            if ($cr['key'] === 'senior') $seniorTotal += $cr['total_count'];
            if ($cr['key'] === 'adult') $adultTotal += $cr['total_count'];
        }
      ?>
      <div class="p-3 rounded-xl border border-pink-200 bg-pink-50/60">
        <div class="text-[10px] font-bold uppercase tracking-wider text-pink-700 flex items-center justify-between">
          <span>Pediatric (0-9y)</span>
          <i class="fas fa-baby"></i>
        </div>
        <div class="text-xl font-black text-pink-900 mt-1"><?= number_format($pediatricTotal) ?></div>
        <div class="text-[10px] text-pink-600 font-medium">Routine EPI &amp; Nutrition</div>
      </div>

      <div class="p-3 rounded-xl border border-rose-200 bg-rose-50/60">
        <div class="text-[10px] font-bold uppercase tracking-wider text-rose-700 flex items-center justify-between">
          <span>Maternal Care</span>
          <i class="fas fa-person-pregnant"></i>
        </div>
        <div class="text-xl font-black text-rose-900 mt-1"><?= number_format($maternalTotal) ?></div>
        <div class="text-[10px] text-rose-600 font-medium">Prenatal &amp; Postpartum</div>
      </div>

      <div class="p-3 rounded-xl border border-purple-200 bg-purple-50/60">
        <div class="text-[10px] font-bold uppercase tracking-wider text-purple-700 flex items-center justify-between">
          <span>Seniors (60+)</span>
          <i class="fas fa-person-cane"></i>
        </div>
        <div class="text-xl font-black text-purple-900 mt-1"><?= number_format($seniorTotal) ?></div>
        <div class="text-[10px] text-purple-600 font-medium">Geriatric &amp; NCD Free Meds</div>
      </div>

      <div class="p-3 rounded-xl border border-blue-200 bg-blue-50/60">
        <div class="text-[10px] font-bold uppercase tracking-wider text-blue-700 flex items-center justify-between">
          <span>PWD Registered</span>
          <i class="fas fa-wheelchair"></i>
        </div>
        <div class="text-xl font-black text-blue-900 mt-1"><?= (int)($specialPoverty['pwd_count'] ?? 0) ?></div>
        <div class="text-[10px] text-blue-600 font-medium">Special Assistance</div>
      </div>

      <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/60">
        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 flex items-center justify-between">
          <span>4Ps Beneficiaries</span>
          <i class="fas fa-hand-holding-heart"></i>
        </div>
        <div class="text-xl font-black text-emerald-900 mt-1"><?= (int)($specialPoverty['four_ps_count'] ?? 0) ?></div>
        <div class="text-[10px] text-emerald-600 font-medium">Priority Welfare Program</div>
      </div>
    </div>

    <!-- Breakdown Table -->
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-xs min-w-[760px] border-collapse">
        <thead class="bg-slate-100/70 text-slate-600 uppercase font-semibold border-b border-slate-200">
          <tr>
            <th class="py-2.5 px-3">Classification &amp; Lifecourse Stage</th>
            <th class="py-2.5 px-3">Category</th>
            <th class="py-2.5 px-3">Target Scope &amp; Eligibility</th>
            <th class="py-2.5 px-3 text-right">Male</th>
            <th class="py-2.5 px-3 text-right">Female</th>
            <th class="py-2.5 px-3 text-right font-bold text-slate-900">Total Count</th>
            <th class="py-2.5 px-3 text-right font-bold text-indigo-700">Share (%)</th>
            <th class="py-2.5 px-3 text-center">Priority</th>
            <th class="py-2.5 px-3 text-center print:hidden">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($classificationSummaryRows)): ?>
            <tr><td colspan="9" class="py-6 text-center text-slate-400">No patient classification records found.</td></tr>
          <?php else: ?>
            <?php foreach ($classificationSummaryRows as $row): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="py-2.5 px-3 font-bold text-slate-900 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-xs">
                      <i class="fas <?= h($row['icon']) ?>"></i>
                    </span>
                    <span><?= h($row['label']) ?></span>
                  </div>
                </td>
                <td class="py-2.5 px-3 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                    <?= h($row['category']) ?>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-slate-500 max-w-xs truncate" title="<?= h($row['description']) ?>">
                  <?= h($row['description']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap"><?= number_format($row['male_count']) ?></td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap"><?= number_format($row['female_count']) ?></td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                  <?= number_format($row['total_count']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                  <?= number_format($row['pct'], 1) ?>%
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <?php if ($row['priority']): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                      <i class="fas fa-star text-[9px] text-amber-500"></i> Priority
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                      Standard
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap print:hidden">
                  <a href="/HealthLogs/public/patients/index.php?classification=<?= h($row['key']) ?>" class="text-[11px] font-semibold text-teal-700 hover:text-teal-900 hover:underline">
                    View List <i class="fas fa-arrow-right text-[9px] ml-0.5"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr class="bg-slate-50 font-bold border-t-2 border-slate-200 text-slate-900">
              <td colspan="3" class="py-2.5 px-3 uppercase text-[11px] tracking-wider text-slate-600">Total Classified Population</td>
              <td class="py-2.5 px-3 text-right font-mono"><?= number_format(array_sum(array_column($classificationSummaryRows, 'male_count'))) ?></td>
              <td class="py-2.5 px-3 text-right font-mono"><?= number_format(array_sum(array_column($classificationSummaryRows, 'female_count'))) ?></td>
              <td class="py-2.5 px-3 text-right font-mono text-teal-800"><?= number_format($totalClassifiedPatients) ?></td>
              <td class="py-2.5 px-3 text-right font-mono text-indigo-700">100.0%</td>
              <td colspan="2"></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Section: Patient Medical Records -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Patient Medical Records</div>
      <p class="text-sm text-slate-500 mt-0.5">Complete list of registered patients with condition and allergy summaries (<?= count($medicalRecordsRows) ?> records total).</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-xs" onclick="printReportSection('section-medical-records', 'Official Report: Patient Medical Records')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print with Header & Signatory
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-xs" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'medical_records']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>

  <div id="section-medical-records">
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:hidden">
      <input id="medicalSearch" class="w-full sm:w-80 border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-slate-50/50" placeholder="Search patient, purok, status..." />
      <div class="text-xs text-slate-500 font-medium">10 rows per page (all <?= count($medicalRecordsRows) ?> shown on print)</div>
    </div>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="min-w-full text-sm min-w-[750px]" id="medicalTable">
      <thead>
        <tr class="border-b border-slate-200 text-slate-500 uppercase text-xs bg-slate-50/75">
          <th class="text-left px-3 py-2.5 font-semibold">Patient</th>
          <th class="text-left px-3 py-2.5 font-semibold">Sex</th>
          <th class="text-left px-3 py-2.5 font-semibold">Birth Date</th>
          <th class="text-left px-3 py-2.5 font-semibold">Purok</th>
          <th class="text-left px-3 py-2.5 font-semibold">Status</th>
          <th class="text-left px-3 py-2.5 font-semibold">Conditions</th>
          <th class="text-left px-3 py-2.5 font-semibold">Allergies</th>
          <th class="text-left px-3 py-2.5 font-semibold">Latest Diagnosis</th>
        </tr>
      </thead>
      <tbody id="medicalTableBody">
        <?php if (empty($medicalRecordsRows)): ?>
          <tr><td class="px-3 py-6 text-slate-500 text-center" colspan="8">No patient medical records found matching current filters.</td></tr>
        <?php else: ?>
          <?php foreach ($medicalRecordsRows as $row): ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition-colors">
              <td class="px-3 py-2.5 font-medium text-slate-900 whitespace-nowrap"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2.5 whitespace-nowrap capitalize"><?= h((string)$row['sex']) ?></td>
              <td class="px-3 py-2.5 whitespace-nowrap text-slate-600"><?= h($row['birth_date']) ?></td>
              <td class="px-3 py-2.5 whitespace-nowrap text-slate-700"><?= h($row['barangay'] ?: 'Barangay Tangcul') ?></td>
              <td class="px-3 py-2.5 whitespace-nowrap">
                <?php
                  $status = strtolower((string)$row['status']);
                  if ($status === 'active') {
                      echo '<span class="pill-badge pill-emerald inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>';
                  } elseif ($status === 'deceased') {
                      echo '<span class="pill-badge pill-rose inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Deceased</span>';
                  } else {
                      echo '<span class="pill-badge pill-slate inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">Inactive</span>';
                  }
                ?>
              </td>
              <td class="px-3 py-2.5">
                <?php if (!empty($row['conditions_list'])): ?>
                  <div class="flex flex-wrap gap-1">
                    <?php foreach (array_map('trim', explode(',', $row['conditions_list'])) as $cond): ?>
                      <?php if ($cond !== ''): ?>
                        <span class="pill-badge pill-teal inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-teal-50 text-teal-800 border border-teal-200">
                          <?= h($cond) ?>
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-xs text-slate-400 italic">None</span>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2.5">
                <?php if (!empty($row['allergies_list'])): ?>
                  <div class="flex flex-wrap gap-1">
                    <?php foreach (array_map('trim', explode(',', $row['allergies_list'])) as $allergy): ?>
                      <?php if ($allergy !== ''): ?>
                        <span class="pill-badge pill-amber inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                          <?= h($allergy) ?>
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-xs text-slate-400 italic">None</span>
                <?php endif; ?>
              </td>
              <td class="px-3 py-2.5 whitespace-nowrap text-slate-600 text-xs">
                <?= !empty($row['latest_diagnosis_date']) ? h(date('M d, Y', strtotime($row['latest_diagnosis_date']))) : '<span class="text-slate-400 italic">None</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
    <div class="mt-3 flex items-center justify-between text-sm print:hidden">
      <div id="medicalPageInfo" class="text-slate-500 text-xs sm:text-sm font-medium"></div>
      <div class="flex gap-2">
        <button id="medicalPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm font-medium hover:bg-slate-50 transition">Prev</button>
        <button id="medicalNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-lg text-slate-700 text-xs sm:text-sm font-medium hover:bg-slate-50 transition">Next</button>
      </div>
    </div>
  </div>
</div>

<!-- Section: Patient Consultation -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Patient Consultation</div>
      <p class="text-sm text-slate-500 mt-0.5">Recorded general consultations matching active filters (<?= count($consultationRows) ?> records total).</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-xs" onclick="printReportSection('section-consultation', 'Official Report: Patient Consultations')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print with Header & Signatory
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-xs" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'consultation']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>

  <div id="section-consultation">
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:hidden">
      <input id="consultationSearch" class="w-full sm:w-80 border border-slate-200 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 rounded-lg px-3 py-2 text-sm bg-slate-50/50" placeholder="Search patient, purok, reason, notes..." />
      <div class="text-xs text-slate-500 font-medium">10 rows per page (all <?= count($consultationRows) ?> shown on print)</div>
    </div>
    <div class="overflow-x-auto mt-4 -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="min-w-full text-sm min-w-[600px]" id="consultationTable">
      <thead>
        <tr class="border-b border-slate-200 text-slate-500 uppercase text-xs bg-slate-50/75">
          <th class="text-left px-3 py-2.5 font-semibold">Date</th>
          <th class="text-left px-3 py-2.5 font-semibold">Patient</th>
          <th class="text-left px-3 py-2.5 font-semibold">Purok</th>
          <th class="text-left px-3 py-2.5 font-semibold">Reason</th>
          <th class="text-left px-3 py-2.5 font-semibold">Notes</th>
        </tr>
      </thead>
      <tbody id="consultationTableBody">
        <?php if (empty($consultationRows)): ?>
          <tr><td class="px-3 py-6 text-slate-500 text-center" colspan="5">No consultation records found matching current filters.</td></tr>
        <?php else: ?>
          <?php foreach ($consultationRows as $row): ?>
            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition-colors">
              <td class="px-3 py-2.5 whitespace-nowrap font-medium text-slate-800"><?= h($row['consult_date']) ?></td>
              <td class="px-3 py-2.5 font-medium text-slate-900 whitespace-nowrap"><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
              <td class="px-3 py-2.5 whitespace-nowrap text-slate-700"><?= h($row['barangay'] ?: 'Barangay Tangcul') ?></td>
              <td class="px-3 py-2.5 text-slate-700"><?= h($row['reason'] ?: '—') ?></td>
              <td class="px-3 py-2.5 text-slate-600"><?= h($row['notes'] ?: '—') ?></td>
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
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 print:mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <div class="text-lg font-semibold text-slate-900">Population Report by Age Group and Gender</div>
      <p class="text-sm text-slate-500 mt-0.5">Demographic distribution for non-deceased community members.</p>
    </div>
    <div class="flex items-center gap-3 self-end sm:self-auto print:hidden">
      <button type="button" class="inline-flex items-center text-sm font-medium text-slate-700 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-xs" onclick="printReportSection('section-population', 'Official Demographic Report: Age & Gender Distribution')">
        <i class="fas fa-print mr-1.5 text-xs text-teal-700"></i>Print Chart
      </button>
      <a class="inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-xs" href="/HealthLogs/public/reports.php?<?= h(http_build_query(array_filter(['from' => $fromDate, 'to' => $toDate, 'barangay' => $barangayFilter, 'sex' => $sexFilter, 'age_group' => $ageGroupFilter, 'export' => 'population']))) ?>">
        <i class="fas fa-file-csv mr-1.5 text-xs"></i>Export CSV
      </a>
    </div>
  </div>
  <div id="section-population">
    <div class="mt-4 relative h-64 sm:h-72 w-full">
      <canvas id="populationAgeGenderChart"></canvas>
    </div>
  </div>
</div>

<!-- Section: Forecasting -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 print:mb-6">
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
  <div class="xl:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 flex flex-col justify-between">
    <div>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <div class="text-lg font-semibold text-slate-900">Seasonal Disease Cases</div>
          <p class="text-sm text-slate-500 mt-0.5">Diagnosed disease distribution per calendar month (Total: <?= (int)$totalSeasonalCases ?> cases across all 12 months).</p>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 border border-teal-200 text-teal-800 text-xs font-semibold self-start sm:self-auto print:hidden">
          <i class="fas fa-layer-group text-[11px] text-teal-600"></i> Stacked by Disease
        </div>
      </div>
      <div class="mt-4 relative h-64 sm:h-72 w-full" id="section-seasonal">
        <canvas id="seasonalDiseaseChart"></canvas>
      </div>
    </div>

    <!-- Disease Breakdown Pills -->
    <?php if (!empty($allDiseaseSet)): ?>
      <div class="mt-4 pt-3 border-t border-slate-100 print:pt-2">
        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Recorded Conditions in Selected Scope (<?= count($allDiseaseSet) ?> Diseases):</div>
        <div class="flex flex-wrap gap-1.5">
          <?php 
          $badgePalette = [
            'Arthritis' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-800', 'border' => 'border-teal-200', 'dot' => '#14b8a6'],
            'Hypertension' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'border' => 'border-blue-200', 'dot' => '#60a5fa'],
            'Migraine' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'border' => 'border-amber-200', 'dot' => '#fbbf24'],
            'Diabetes Type 2' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-800', 'border' => 'border-purple-200', 'dot' => '#a78bfa'],
            'Allergic Rhinitis' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-800', 'border' => 'border-emerald-200', 'dot' => '#34d399'],
            'Asthma' => ['bg' => 'bg-pink-50', 'text' => 'text-pink-800', 'border' => 'border-pink-200', 'dot' => '#fb7185'],
            'Hypothyroidism' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-800', 'border' => 'border-indigo-200', 'dot' => '#818cf8'],
          ];
          foreach ($allDiseaseSet as $diseaseName => $count): 
            $style = $badgePalette[$diseaseName] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-800', 'border' => 'border-slate-200', 'dot' => '#475569'];
          ?>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium <?= $style['bg'] ?> <?= $style['text'] ?> border <?= $style['border'] ?>">
              <span class="w-2 h-2 rounded-full inline-block" style="background-color: <?= $style['dot'] ?>;"></span>
              <span><?= h($diseaseName) ?></span>
              <span class="font-bold ml-0.5 opacity-80">(<?= (int)$count ?>)</span>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300 flex flex-col justify-between">
    <div>
      <div class="text-lg font-semibold text-slate-900">Peak Infection Months</div>
      <p class="text-sm text-slate-500 mt-0.5">Highest recorded monthly incidence.</p>
      <div class="mt-4 space-y-3">
        <?php if (!empty($seasonalTopMonths)): ?>
          <?php $rank = 1; foreach ($seasonalTopMonths as $row): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 transition hover:bg-slate-50">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-5 h-5 rounded-full bg-slate-900 text-white text-[10px] font-bold flex items-center justify-center">#<?= $rank++ ?></span>
                  <span class="font-bold text-slate-900 text-sm"><?= h($row['month_label']) ?></span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                  <?= h((string)$row['total_cases']) ?> cases
                </span>
              </div>
              <div class="mt-2 text-xs text-slate-600">
                <span class="font-semibold text-slate-700">Conditions:</span>
                <div class="mt-1 flex flex-wrap gap-1">
                  <?php foreach ($row['diseases'] as $dName => $dCount): ?>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[11px] text-slate-700 font-medium">
                      <?= h($dName) ?>: <strong class="text-slate-900"><?= (int)$dCount ?></strong>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-slate-400 text-xs py-3">No condition diagnosis history recorded yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500">
      <i class="fas fa-info-circle text-teal-600 mr-1"></i>
      <strong>Data Note:</strong> Y-axis scale stops at 5 because <strong>December (5 cases)</strong> is currently the highest monthly case count in the database.
    </div>
  </div>
</div>

<!-- Section: Disease Case Trends -->
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-xs border border-slate-100 print:shadow-none print:border print:border-slate-300">
  <div class="text-lg font-semibold text-slate-900">Disease Case Trends (Top 5 Diseases)</div>
  <p class="text-sm text-slate-500 mt-0.5">Monthly trajectory for top diagnosed conditions.</p>
  <div class="mt-4 relative h-64 sm:h-72 w-full" id="section-trends">
    <canvas id="diseaseTrendChart"></canvas>
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
  const seasonalLabels = <?= json_encode(array_column($seasonalDiseaseRows, 'month_label')) ?>;
  const seasonalDatasets = <?= json_encode($seasonalDatasets) ?>;
  const monthlyTotals = <?= json_encode(array_combine(array_column($seasonalDiseaseRows, 'month_label'), array_column($seasonalDiseaseRows, 'total_cases'))) ?>;
  const diseaseTrendMonths = <?= json_encode($diseaseTrendMonths) ?>;
  const diseaseTrendSeries = <?= json_encode($diseaseTrendSeries) ?>;
  const ageGroupLabels = <?= json_encode($ageGroupLabels) ?>;
  const maleCounts = <?= json_encode($maleCounts) ?>;
  const femaleCounts = <?= json_encode($femaleCounts) ?>;

  let seasonalChartInstance = null;
  let diseaseTrendChartInstance = null;
  let populationChartInstance = null;

  Chart.defaults.font.family = "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif";
  Chart.defaults.color = '#64748b';

  if (seasonalLabels.length && seasonalDatasets.length && document.getElementById('seasonalDiseaseChart')) {
    const maxVal = Math.max(...Object.values(monthlyTotals), 5);
    seasonalChartInstance = new Chart(document.getElementById('seasonalDiseaseChart'), {
      type: 'bar',
      data: {
        labels: seasonalLabels,
        datasets: seasonalDatasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              usePointStyle: true,
              pointStyle: 'circle',
              font: { size: 11 },
              padding: 10
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              title: function(tooltipItems) {
                const month = tooltipItems[0].label;
                const total = monthlyTotals[month] || 0;
                return `${month} • ${total} Total Case${total === 1 ? '' : 's'}`;
              },
              label: function(context) {
                const val = context.raw || 0;
                if (val <= 0) return null;
                return ` ${context.dataset.label}: ${val} case${val === 1 ? '' : 's'}`;
              }
            }
          }
        },
        scales: {
          x: {
            stacked: true,
            grid: { display: false }
          },
          y: {
            stacked: true,
            beginAtZero: true,
            suggestedMax: maxVal + 1,
            ticks: {
              precision: 0,
              stepSize: 1
            },
            title: {
              display: true,
              text: 'Number of Cases',
              font: { size: 11 }
            },
            grid: { color: 'rgba(226, 232, 240, 0.6)' }
          }
        }
      }
    });
  }

  const trendDiseaseNames = Object.keys(diseaseTrendSeries || {});
  if (diseaseTrendMonths.length && trendDiseaseNames.length && document.getElementById('diseaseTrendChart')) {
    const palette = ['#14b8a6', '#60a5fa', '#fbbf24', '#a78bfa', '#34d399'];
    const datasets = trendDiseaseNames.map((name, idx) => ({
      label: name,
      data: diseaseTrendSeries[name],
      borderColor: palette[idx % palette.length],
      backgroundColor: palette[idx % palette.length],
      tension: 0.3,
      pointRadius: 4,
      pointHoverRadius: 6,
      borderWidth: 2.5
    }));

    diseaseTrendChartInstance = new Chart(document.getElementById('diseaseTrendChart'), {
      type: 'line',
      data: { labels: diseaseTrendMonths, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, usePointStyle: true, padding: 16 }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { color: 'rgba(226, 232, 240, 0.4)' }
          },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(226, 232, 240, 0.6)' }
          }
        }
      }
    });
  }

  if (ageGroupLabels.length && document.getElementById('populationAgeGenderChart')) {
    populationChartInstance = new Chart(document.getElementById('populationAgeGenderChart'), {
      type: 'bar',
      data: {
        labels: ageGroupLabels,
        datasets: [
          {
            label: 'Male',
            data: maleCounts,
            backgroundColor: 'rgba(96, 165, 250, 0.75)',
            borderColor: '#60a5fa',
            borderRadius: 6,
            borderWidth: 1,
            maxBarThickness: 32
          },
          {
            label: 'Female',
            data: femaleCounts,
            backgroundColor: 'rgba(251, 113, 133, 0.75)',
            borderColor: '#fb7185',
            borderRadius: 6,
            borderWidth: 1,
            maxBarThickness: 32
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, usePointStyle: true, padding: 16 }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(226, 232, 240, 0.6)' }
          }
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

    const pageSize = 20;
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
        <link rel="icon" type="image/jpeg" href="/HealthLogs/public/assets/images/logo.jpeg">
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
            vertical-align: middle;
          }
          tr:nth-child(even) td {
            background-color: #f8fafc;
          }
          .pill-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 600;
            margin: 1px 2px;
            line-height: 1.2;
          }
          .pill-teal {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
          }
          .pill-amber {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
          }
          .pill-emerald {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
          }
          .pill-slate {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
          }
          .pill-rose {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
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
          <div style="display: flex; align-items: center;">
            <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" style="width: 58px; height: 58px; object-fit: cover; border-radius: 10px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
          </div>
          <div class="header-center">
            <div class="rep-title">Republic of the Philippines • Province of Isabela • City of Ilagan</div>
            <div class="agency-title">Barangay Tangcul Primary Care & Health Services</div>
            <div class="hub-title">Barangay Tangcul Health Station & Care Hub</div>
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
            <div class="sig-role">Municipal / City Health Officer</div>
            <div class="sig-date">Date: ____________________</div>
          </div>
        </div>

        <div class="watermark-footer">
          Official HealthLogs System Generated Document • Certified Medical & Program Records • Barangay Tangcul, City of Ilagan • Timestamp: ${printDate}
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
