<?php
$pageTitle = 'Forecasting';
require __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../app/Core/ForecastLogger.php';
require __DIR__ . '/partials/header.php';

$seriesOptions = [
    'visits_total' => 'Patient Visits',
    'medicine_total' => 'Medicine Demand',
];

$seriesUnits = [
    'visits_total' => 'visits',
    'medicine_total' => 'units',
];

$seriesKey = $_POST['series_key'] ?? 'visits_total';
$horizon = max(1, (int)($_POST['horizon'] ?? 30));

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime = microtime(true);
    $runId = ForecastLogger::startRun($seriesKey, $horizon, 'ARIMA');

    $python = getenv('PYTHON_PATH') ?: ($_ENV['PYTHON_PATH'] ?? null) ?: (file_exists(__DIR__ . '/../.venv/Scripts/python.exe') ? __DIR__ . '/../.venv/Scripts/python.exe' : 'python');
    $script = __DIR__ . '/../scripts/forecast_arima.py';
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) .
        ' --series-key ' . escapeshellarg($seriesKey) .
        ' --horizon ' . escapeshellarg((string)$horizon) .
        ' 2>&1';
    $output = shell_exec($cmd);

    $executionTime = round(microtime(true) - $startTime, 3);

    if (!$output) {
        $error = 'No output from forecasting script.';
        ForecastLogger::logFailure($runId, $error, $executionTime);
    } else {
        $data = json_decode($output, true);
        if (!is_array($data)) {
            $cleanedOutput = trim(strip_tags((string)$output));
            $error = 'Forecasting script failed. ' . ($cleanedOutput !== '' ? $cleanedOutput : 'No valid response from Python.');
            ForecastLogger::logFailure($runId, 'Invalid output from python: ' . substr($output, 0, 500), $executionTime);
        } elseif (!empty($data['error'])) {
            $error = $data['error'];
            ForecastLogger::logFailure($runId, $error, $executionTime);
        } elseif (!isset($data['forecast'], $data['summary'])) {
            $error = 'Forecast response is incomplete.';
            ForecastLogger::logFailure($runId, $error, $executionTime);
        } else {
            $result = $data;

            // Extract diagnostics and metrics
            $metrics = $data['metrics'] ?? null;
            $diagnostics = null;
            if (isset($data['diagnostics'])) {
                $diagnostics = $data['diagnostics'];
                $diagnostics['model_order'] = $data['summary']['model'] ?? null;
                $diagnostics['seasonal_order'] = $data['summary']['seasonal_model'] ?? null;
                if ($metrics) {
                    $diagnostics['mae'] = $metrics['mae'] ?? null;
                    $diagnostics['rmse'] = $metrics['rmse'] ?? null;
                    $diagnostics['mape'] = $metrics['mape'] ?? null;
                }
            }

            ForecastLogger::logSuccess(
                $runId,
                (int)($data['summary']['history_points'] ?? 0),
                (int)($data['summary']['training_points'] ?? 0),
                $executionTime,
                $data['forecast'],
                $diagnostics,
                $metrics
            );
        }
    }
}

$summary = $result['summary'] ?? null;
$forecastRows = $result['forecast'] ?? [];
$historyRows = $result['history'] ?? [];
$metrics = $result['metrics'] ?? ($summary ? [
    'mae' => $summary['mae'] ?? null,
    'rmse' => $summary['rmse'] ?? null,
    'mape' => $summary['mape'] ?? null,
    'accuracy_rating' => $summary['accuracy_rating'] ?? null,
] : null);
$unitLabel = $seriesUnits[$seriesKey] ?? 'items';
$forecastGenerated = $_SERVER['REQUEST_METHOD'] === 'POST' && $result !== null;

// ==========================================
// 1. MEDICINE DEMAND FORECASTING (NEXT 3 MONTHS)
// ==========================================
$medicineForecastList = [];
$medicineSummaryStats = [
    'total_demand_3m' => 0,
    'critical_count' => 0,
    'reorder_count' => 0,
    'adequate_count' => 0,
];

try {
    $refMedDate = $pdo->query("SELECT COALESCE(MAX(DATE(transaction_datetime)), CURDATE()) FROM medicine_transactions WHERE transaction_type = 'dispensed'")->fetchColumn();

    $medSql = "
        SELECT m.id, m.name, m.generic_name, m.unit, m.reorder_level,
               COALESCE(stk.current_stock, 0) AS current_stock,
               COALESCE(m1.qty, 0) AS m1_dispensed,
               COALESCE(m2.qty, 0) AS m2_dispensed,
               COALESCE(m3.qty, 0) AS m3_dispensed,
               COALESCE(tot.qty, 0) AS total_past_dispensed
        FROM medicines m
        LEFT JOIN (
            SELECT medicine_id, 
                   SUM(CASE 
                        WHEN transaction_type = 'received' THEN quantity
                        WHEN transaction_type IN ('dispensed', 'expired', 'returned') THEN -ABS(quantity)
                        ELSE quantity 
                   END) AS current_stock
            FROM medicine_transactions
            GROUP BY medicine_id
        ) stk ON stk.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, SUM(ABS(quantity)) AS qty
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
              AND transaction_datetime >= DATE_SUB(:refDate1, INTERVAL 30 DAY)
            GROUP BY medicine_id
        ) m1 ON m1.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, SUM(ABS(quantity)) AS qty
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
              AND transaction_datetime >= DATE_SUB(:refDate2, INTERVAL 60 DAY)
              AND transaction_datetime < DATE_SUB(:refDate3, INTERVAL 30 DAY)
            GROUP BY medicine_id
        ) m2 ON m2.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, SUM(ABS(quantity)) AS qty
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
              AND transaction_datetime >= DATE_SUB(:refDate4, INTERVAL 90 DAY)
              AND transaction_datetime < DATE_SUB(:refDate5, INTERVAL 60 DAY)
            GROUP BY medicine_id
        ) m3 ON m3.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, SUM(ABS(quantity)) AS qty
            FROM medicine_transactions
            WHERE transaction_type = 'dispensed'
            GROUP BY medicine_id
        ) tot ON tot.medicine_id = m.id
        ORDER BY total_past_dispensed DESC
    ";

    $stmtMeds = $pdo->prepare($medSql);
    $stmtMeds->execute([
        'refDate1' => $refMedDate,
        'refDate2' => $refMedDate,
        'refDate3' => $refMedDate,
        'refDate4' => $refMedDate,
        'refDate5' => $refMedDate,
    ]);
    $medRows = $stmtMeds->fetchAll(PDO::FETCH_ASSOC);

    foreach ($medRows as $r) {
        $m1 = (float)$r['m1_dispensed'];
        $m2 = (float)$r['m2_dispensed'];
        $m3 = (float)$r['m3_dispensed'];
        $totalPast = (float)$r['total_past_dispensed'];
        $currentStock = max(0, (float)$r['current_stock']);
        $reorderLevel = (float)($r['reorder_level'] ?: 100);

        if ($m1 + $m2 + $m3 > 0) {
            $monthlyBase = ($m1 * 0.5) + ($m2 * 0.3) + ($m3 * 0.2);
            $trendSlope = ($m1 - $m3) / 2.0;
        } elseif ($totalPast > 0) {
            $monthlyBase = $totalPast / 12.0;
            $trendSlope = 0;
        } else {
            $monthlyBase = 0;
            $trendSlope = 0;
        }

        $forecast_m1 = round(max(0, $monthlyBase + ($trendSlope * 0.2)));
        $forecast_m2 = round(max(0, $monthlyBase + ($trendSlope * 0.5)));
        $forecast_m3 = round(max(0, $monthlyBase + ($trendSlope * 0.8)));
        $forecast_total_3m = $forecast_m1 + $forecast_m2 + $forecast_m3;

        // Buffer stock (20% safety stock)
        $bufferStock = round($forecast_total_3m * 0.2);
        $targetStock = $forecast_total_3m + $bufferStock;
        $suggestedOrder = max(0, $targetStock - $currentStock);

        if ($forecast_m1 > 0 && $currentStock < $forecast_m1) {
            $status = 'Critical Shortage';
            $statusBadge = 'bg-rose-100 text-rose-800 border-rose-300';
            $medicineSummaryStats['critical_count']++;
        } elseif ($forecast_total_3m > 0 && $currentStock < $forecast_total_3m) {
            $status = 'Reorder Needed';
            $statusBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            $medicineSummaryStats['reorder_count']++;
        } elseif ($currentStock < $reorderLevel) {
            $status = 'Low Buffer';
            $statusBadge = 'bg-yellow-100 text-yellow-800 border-yellow-300';
            $medicineSummaryStats['reorder_count']++;
        } elseif ($forecast_total_3m > 0 && $currentStock > ($forecast_total_3m * 2.5)) {
            $status = 'Overstocked';
            $statusBadge = 'bg-blue-100 text-blue-800 border-blue-300';
            $medicineSummaryStats['adequate_count']++;
        } else {
            $status = 'Adequate Stock';
            $statusBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $medicineSummaryStats['adequate_count']++;
        }

        $medicineSummaryStats['total_demand_3m'] += $forecast_total_3m;

        $medicineForecastList[] = [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'generic_name' => $r['generic_name'],
            'unit' => $r['unit'],
            'current_stock' => $currentStock,
            'forecast_m1' => $forecast_m1,
            'forecast_m2' => $forecast_m2,
            'forecast_m3' => $forecast_m3,
            'forecast_total_3m' => $forecast_total_3m,
            'suggested_order' => $suggestedOrder,
            'status' => $status,
            'status_badge' => $statusBadge,
        ];
    }
    $topDemandMeds = array_slice($medicineForecastList, 0, 8);
} catch (Throwable $e) {
    $medicineForecastList = [];
    $topDemandMeds = [];
}

// ==========================================
// 2. PATIENT VISITS FORECASTING BY HEALTH PROGRAM CATEGORY (NEXT 1-3 MONTHS)
// ==========================================
$categoryForecast = [];
try {
    $refVisitDate = $pdo->query("SELECT COALESCE(MAX(DATE(visit_datetime)), CURDATE()) FROM visits")->fetchColumn();

    // 1. Maternal & Prenatal Health (Buntis)
    $activePregnancies = (int)$pdo->query("SELECT COUNT(*) FROM pregnancies WHERE status = 'ongoing'")->fetchColumn();
    $matVisitsM1 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'maternal' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    $matVisitsM2 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'maternal' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 60 DAY) AND visit_datetime < DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    $matVisitsM3 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'maternal' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 90 DAY) AND visit_datetime < DATE_SUB('$refVisitDate', INTERVAL 60 DAY)")->fetchColumn();
    
    // Weighted maternal visit projection considering active pregnancies (avg 2 checkups/month per pregnant woman nearing delivery)
    $matBase = max(10, (int)round(($matVisitsM1 * 0.4) + ($matVisitsM2 * 0.3) + ($matVisitsM3 * 0.3) + ($activePregnancies * 1.5)));
    $matForecastM1 = $matBase;
    $matForecastM2 = (int)round($matBase * 1.15); // +15% entering 3rd trimester
    $matForecastM3 = (int)round($matBase * 1.25); // +25% delivery readiness & checkups
    $matGrowth = round((($matForecastM2 - $matVisitsM1) / max(1, $matVisitsM1)) * 100);

    $categoryForecast['maternal'] = [
        'title' => 'Maternal & Prenatal Care (Buntis)',
        'subtitle' => 'Pregnant Patients & Delivery Monitoring',
        'icon' => 'fa-female',
        'badge' => 'Buntis / Prenatal',
        'color' => 'rose',
        'active_cohort' => $activePregnancies . ' active pregnancies enrolled',
        'm1' => $matForecastM1,
        'm2' => $matForecastM2,
        'm3' => $matForecastM3,
        'total_3m' => $matForecastM1 + $matForecastM2 + $matForecastM3,
        'trend_pct' => $matGrowth,
        'trend_label' => ($matGrowth >= 0 ? '+' : '') . $matGrowth . '% projected surge in Month 2 & 3',
        'insight' => 'Expect higher maternal consultations over the next 2 months as active pregnancies enter 2nd/3rd trimesters. Prepare prenatal vitamins, Ferrous Sulfate, and ensure BHW prenatal visit coverage.',
    ];

    // 2. Child Immunization & Vaccines
    $pendingVaccines = (int)$pdo->query("SELECT COUNT(*) FROM immunization_schedule WHERE status = 'scheduled'")->fetchColumn();
    $immVisitsM1 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'immunization' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    $immVisitsM2 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'immunization' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 60 DAY) AND visit_datetime < DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    
    $immBase = max(15, (int)round(($immVisitsM1 * 0.5) + ($immVisitsM2 * 0.5) + ($pendingVaccines * 0.8)));
    $immForecastM1 = $immBase;
    $immForecastM2 = (int)round($immBase * 1.05);
    $immForecastM3 = (int)round($immBase * 1.10);

    $categoryForecast['immunization'] = [
        'title' => 'Child Immunization & Vaccines',
        'subtitle' => 'Infant & Under-5 Scheduled Vaccination Doses',
        'icon' => 'fa-baby',
        'badge' => 'Bakuna / Immunization',
        'color' => 'teal',
        'active_cohort' => $pendingVaccines . ' scheduled upcoming doses',
        'm1' => $immForecastM1,
        'm2' => $immForecastM2,
        'm3' => $immForecastM3,
        'total_3m' => $immForecastM1 + $immForecastM2 + $immForecastM3,
        'trend_pct' => 8,
        'trend_label' => 'Steady demand (+8% infant cohort expansion)',
        'insight' => 'Infant immunization visits remain consistent. Verify vaccine stock (BCG, Pentavalent, OPV, Measles) and send automated SMS reminders for scheduled vaccination days.',
    ];

    // 3. TB Monitoring & DOTS Adherence
    $activeTbCases = (int)$pdo->query("SELECT COUNT(*) FROM tb_cases WHERE status = 'active'")->fetchColumn();
    $tbBase = max(5, $activeTbCases * 8); // ~8 clinic visits/supervised logs per month per active case
    $tbForecastM1 = $tbBase;
    $tbForecastM2 = (int)round($tbBase * 0.95); // gradual completion
    $tbForecastM3 = (int)round($tbBase * 0.90);

    $categoryForecast['tb'] = [
        'title' => 'TB Monitoring & DOTS Care',
        'subtitle' => 'Directly Observed Therapy & Evaluation Visits',
        'icon' => 'fa-lungs',
        'badge' => 'TB DOTS Program',
        'color' => 'amber',
        'active_cohort' => $activeTbCases . ' active TB patients undergoing treatment',
        'm1' => $tbForecastM1,
        'm2' => $tbForecastM2,
        'm3' => $tbForecastM3,
        'total_3m' => $tbForecastM1 + $tbForecastM2 + $tbForecastM3,
        'trend_pct' => -5,
        'trend_label' => 'Stable adherence / gradual treatment completion',
        'insight' => 'Daily DOTS intake and monthly lab check-ups. Ensure sufficient stock of anti-TB blister packs and monitor patients due for medicine to prevent lost to follow-up.',
    ];

    // 4. General Consultations & Adult / Senior Health
    $genVisitsM1 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'general' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    $genVisitsM2 = (int)$pdo->query("SELECT COUNT(*) FROM visits WHERE visit_type = 'general' AND visit_datetime >= DATE_SUB('$refVisitDate', INTERVAL 60 DAY) AND visit_datetime < DATE_SUB('$refVisitDate', INTERVAL 30 DAY)")->fetchColumn();
    $genBase = max(40, (int)round(($genVisitsM1 * 0.5) + ($genVisitsM2 * 0.5)));
    $genForecastM1 = $genBase;
    $genForecastM2 = (int)round($genBase * 1.10);
    $genForecastM3 = (int)round($genBase * 1.15);

    $categoryForecast['general'] = [
        'title' => 'General Consultations & Adult Health',
        'subtitle' => 'Hypertension, Diabetes, and Acute Illnesses',
        'icon' => 'fa-user-md',
        'badge' => 'General Care',
        'color' => 'blue',
        'active_cohort' => 'Primary care outpatient visits',
        'm1' => $genForecastM1,
        'm2' => $genForecastM2,
        'm3' => $genForecastM3,
        'total_3m' => $genForecastM1 + $genForecastM2 + $genForecastM3,
        'trend_pct' => 12,
        'trend_label' => '+12% projected demand for maintenance & acute care',
        'insight' => 'Expect higher outpatient consultations for seasonal cough, colds, and maintenance refill visits (Amlodipine, Losartan, Metformin). Ensure ample buffer inventory.',
    ];
} catch (Throwable $e) {
    $categoryForecast = [];
}

// Seasonal disease & trends
$seasonalDiseaseRows = [];
$seasonalTopMonths = [];
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
} catch (Throwable $e) {}

$diseaseTrendMonths = [];
$diseaseTrendSeries = [];
$topDiseaseNames = [];
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
            $name = $row['condition_name'];
            $ym = $row['ym'];
            if (isset($diseaseTrendSeries[$name], $monthIndex[$ym])) {
                $diseaseTrendSeries[$name][$monthIndex[$ym]] = (int)$row['total_cases'];
            }
        }
    }
} catch (Throwable $e) {}

if ($summary) {
    $firstWeek = array_slice($forecastRows, 0, min(7, count($forecastRows)));
    $laterPeriod = count($forecastRows) > 7 ? array_slice($forecastRows, 7) : [];
    $firstWeekAverage = count($firstWeek) ? array_sum(array_column($firstWeek, 'value')) / count($firstWeek) : 0;
    $laterAverage = count($laterPeriod) ? array_sum(array_column($laterPeriod, 'value')) / count($laterPeriod) : $summary['forecast_average'];
    $changeVsRecent = $summary['forecast_average'] - $summary['recent_average'];
    $changeWord = abs($changeVsRecent) < 0.5 ? 'about the same as' : ($changeVsRecent > 0 ? 'higher than' : 'lower than');

    $planningNotes = [
        'Plan for around ' . number_format($summary['forecast_average'], 1) . ' ' . $unitLabel . ' per day.',
        'That is ' . $changeWord . ' the recent average of ' . number_format($summary['recent_average'], 1) . '.',
        'The highest single-day estimate is ' . number_format($summary['peak_value'], 1) . ' on ' . $summary['peak_date'] . '.',
    ];
}
?>

<?php display_flash_messages(); ?>

<!-- Page Banner -->
<div class="bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-teal-700">Predictive Health Analytics</div>
      <div class="text-2xl font-bold text-slate-900 mt-1">3-Month Demand &amp; Patient Visit Forecasting</div>
      <p class="text-sm text-slate-500 mt-1">Forecasts specific medicine consumption needs and projected patient visits by program (Maternal, Immunization, TB, General Care) based on historical health data.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip bg-teal-50 text-teal-700 border border-teal-200">
        <i class="fas fa-chart-line mr-1 text-xs"></i> 3-Month Projection
      </span>
      <button type="button" onclick="window.print()" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow transition">
        <i class="fas fa-print mr-1.5 text-xs"></i> Print Forecast Report
      </button>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SECTION 1: 3-MONTH MEDICINE DEMAND & INVENTORY REORDER FORECAST -->
<!-- ============================================================ -->
<div class="mt-6 bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
        <i class="fas fa-pills"></i>
        <span>Medicine Demand Forecasting (Next 3 Months)</span>
      </div>
      <h3 class="text-xl font-bold text-slate-900 mt-2">Which Medicines Will Be In High Demand?</h3>
      <p class="text-xs text-slate-500 mt-0.5">Projects 3-month unit consumption per medicine based on historical dispensing velocity, stock levels, and safety buffer recommendations.</p>
    </div>
    <div class="text-xs text-slate-500 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
      <i class="fas fa-info-circle text-blue-600 mr-1"></i>
      Includes <strong>+20% safety buffer</strong> recommendation
    </div>
  </div>

  <!-- Medicine Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
    <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-rose-700 flex items-center justify-between">
        <span>Critical Stockout Risk</span>
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <div class="text-3xl font-extrabold text-rose-900 mt-2"><?= $medicineSummaryStats['critical_count'] ?></div>
      <div class="text-xs text-rose-700 mt-1">Stock runs out in &lt; 30 days</div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-amber-700 flex items-center justify-between">
        <span>Reorder Recommended</span>
        <i class="fas fa-cart-arrow-down"></i>
      </div>
      <div class="text-3xl font-extrabold text-amber-900 mt-2"><?= $medicineSummaryStats['reorder_count'] ?></div>
      <div class="text-xs text-amber-700 mt-1">Stock below 3-month demand</div>
    </div>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-emerald-700 flex items-center justify-between">
        <span>Adequate Stock</span>
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="text-3xl font-extrabold text-emerald-900 mt-2"><?= $medicineSummaryStats['adequate_count'] ?></div>
      <div class="text-xs text-emerald-700 mt-1">Stock covers 3+ months demand</div>
    </div>

    <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-blue-700 flex items-center justify-between">
        <span>3-Month Projected Dispense</span>
        <i class="fas fa-boxes-stacked"></i>
      </div>
      <div class="text-3xl font-extrabold text-blue-900 mt-2 font-mono"><?= number_format($medicineSummaryStats['total_demand_3m']) ?></div>
      <div class="text-xs text-blue-700 mt-1">Total projected units needed</div>
    </div>
  </div>

  <!-- Multi-Month Chart: Top In-Demand Medicines -->
  <div class="mt-6 bg-slate-50/60 p-4 rounded-xl border border-slate-200">
    <div class="flex items-center justify-between mb-2">
      <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Top Medicines Projected Demand Progression (Month 1 vs Month 2 vs Month 3)</div>
      <span class="text-[11px] text-slate-400">Unit: Dispensed Quantity</span>
    </div>
    <div class="h-64 sm:h-72">
      <canvas id="medicineForecastBarChart"></canvas>
    </div>
  </div>

  <!-- Detailed Medicine Demand & Stock Reorder Table -->
  <div class="mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
      <div class="text-sm font-bold text-slate-900">Medicine Demand Breakdown &amp; Suggested Reorder Quantities</div>
      <div class="flex items-center gap-1.5 text-xs">
        <span class="text-slate-500">Filter Risk:</span>
        <button type="button" class="med-filter-btn px-2.5 py-1 rounded bg-slate-900 text-white font-semibold" data-med-filter="all">All</button>
        <button type="button" class="med-filter-btn px-2.5 py-1 rounded bg-slate-100 text-slate-700 hover:bg-slate-200" data-med-filter="Critical Shortage">Critical</button>
        <button type="button" class="med-filter-btn px-2.5 py-1 rounded bg-slate-100 text-slate-700 hover:bg-slate-200" data-med-filter="Reorder Needed">Reorder Needed</button>
        <button type="button" class="med-filter-btn px-2.5 py-1 rounded bg-slate-100 text-slate-700 hover:bg-slate-200" data-med-filter="Adequate Stock">Adequate</button>
      </div>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
      <table class="w-full text-left text-xs min-w-[760px] border-collapse" id="medicineForecastTable">
        <thead>
          <tr class="border-b bg-slate-100 text-slate-600 uppercase font-semibold">
            <th class="py-3 px-3">Medicine Name</th>
            <th class="py-3 px-3 text-right">Current Stock</th>
            <th class="py-3 px-3 text-right">Month 1 (+30d)</th>
            <th class="py-3 px-3 text-right">Month 2 (+60d)</th>
            <th class="py-3 px-3 text-right">Month 3 (+90d)</th>
            <th class="py-3 px-3 text-right font-bold text-slate-900">Total 3M Demand</th>
            <th class="py-3 px-3 text-center">Stock Status</th>
            <th class="py-3 px-3 text-right font-bold text-teal-800">Suggested Reorder</th>
          </tr>
        </thead>
        <tbody class="divide-y text-slate-700">
          <?php if (empty($medicineForecastList)): ?>
            <tr>
              <td colspan="8" class="py-6 text-center text-slate-400">No medicine transaction history available for forecasting.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($medicineForecastList as $m): ?>
              <tr class="med-row hover:bg-slate-50 transition" data-med-status="<?= h($m['status']) ?>">
                <td class="py-2.5 px-3 whitespace-nowrap">
                  <div class="font-bold text-slate-900"><?= h($m['name']) ?></div>
                  <div class="text-[11px] text-slate-400"><?= h($m['generic_name']) ?> &bull; <?= h($m['unit']) ?></div>
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-semibold text-slate-800 whitespace-nowrap">
                  <?= number_format($m['current_stock']) ?> <?= h($m['unit']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap">
                  <?= number_format($m['forecast_m1']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap">
                  <?= number_format($m['forecast_m2']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-slate-600 whitespace-nowrap">
                  <?= number_format($m['forecast_m3']) ?>
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                  <?= number_format($m['forecast_total_3m']) ?>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold border <?= $m['status_badge'] ?>">
                    <?= h($m['status']) ?>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                  <?php if ($m['suggested_order'] > 0): ?>
                    <span class="font-mono font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                      +<?= number_format($m['suggested_order']) ?> <?= h($m['unit']) ?>
                    </span>
                  <?php else: ?>
                    <span class="text-emerald-700 font-semibold">&check; Sufficient</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SECTION 2: 3-MONTH PATIENT VISITS & HEALTH PROGRAM FORECASTING -->
<!-- ============================================================ -->
<div class="mt-8 bg-white p-5 sm:p-6 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200">
        <i class="fas fa-users"></i>
        <span>Program &amp; Category Patient Visit Forecasting (Next 1–3 Months)</span>
      </div>
      <h3 class="text-xl font-bold text-slate-900 mt-2">Which Patient Groups Will Visit Most in Next 3 Months?</h3>
      <p class="text-xs text-slate-500 mt-0.5">Forecasts specific patient demographics (Buntis/Pregnant Women, Child Immunization, TB Patients, General Care) to allocate clinical staff and resources proactively.</p>
    </div>
  </div>

  <!-- 4 Program Forecasting Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">
    <?php foreach ($categoryForecast as $catKey => $cat): ?>
      <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 hover:border-slate-300 transition-all flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-10 h-10 rounded-xl bg-<?= $cat['color'] ?>-100 text-<?= $cat['color'] ?>-700 flex items-center justify-center font-bold text-base shadow-2xs">
                <i class="fas <?= $cat['icon'] ?>"></i>
              </span>
              <div>
                <h4 class="font-bold text-slate-900 text-base"><?= h($cat['title']) ?></h4>
                <div class="text-xs text-slate-400"><?= h($cat['subtitle']) ?></div>
              </div>
            </div>
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-<?= $cat['color'] ?>-50 text-<?= $cat['color'] ?>-800 border border-<?= $cat['color'] ?>-200">
              <?= h($cat['badge']) ?>
            </span>
          </div>

          <!-- Monthly Progression -->
          <div class="grid grid-cols-3 gap-2 mt-4 bg-white p-3 rounded-lg border border-slate-200 text-center">
            <div>
              <div class="text-[10px] uppercase font-bold text-slate-400">Month 1 (+30d)</div>
              <div class="text-xl font-extrabold text-slate-800 mt-1"><?= number_format($cat['m1']) ?></div>
              <div class="text-[10px] text-slate-500">visits</div>
            </div>
            <div class="border-x border-slate-100">
              <div class="text-[10px] uppercase font-bold text-slate-400">Month 2 (+60d)</div>
              <div class="text-xl font-extrabold text-teal-700 mt-1"><?= number_format($cat['m2']) ?></div>
              <div class="text-[10px] text-slate-500">visits</div>
            </div>
            <div>
              <div class="text-[10px] uppercase font-bold text-slate-400">Month 3 (+90d)</div>
              <div class="text-xl font-extrabold text-blue-700 mt-1"><?= number_format($cat['m3']) ?></div>
              <div class="text-[10px] text-slate-500">visits</div>
            </div>
          </div>

          <!-- Trend Banner -->
          <div class="mt-3 text-xs font-semibold text-<?= $cat['color'] ?>-800 flex items-center gap-1.5">
            <i class="fas fa-arrow-trend-up"></i>
            <span><?= h($cat['trend_label']) ?></span>
          </div>

          <!-- Clinical Insight -->
          <p class="text-xs text-slate-600 mt-2 bg-white p-3 rounded-lg border border-slate-150 leading-relaxed">
            <strong>Key Insight:</strong> <?= h($cat['insight']) ?>
          </p>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-200/80 flex items-center justify-between text-xs text-slate-500">
          <span>Cohort Basis: <strong><?= h($cat['active_cohort']) ?></strong></span>
          <span class="font-bold text-slate-900">3M Total: <?= number_format($cat['total_3m']) ?> visits</span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Category Comparison Chart -->
  <div class="mt-6 bg-slate-50/60 p-4 rounded-xl border border-slate-200">
    <div class="flex items-center justify-between mb-2">
      <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Health Program Visit Comparison (Month 1 vs Month 2 vs Month 3)</div>
      <span class="text-[11px] text-slate-400">Unit: Expected Consultations / Visits</span>
    </div>
    <div class="h-64 sm:h-72">
      <canvas id="categoryForecastBarChart"></canvas>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SECTION 3: DAILY TIME-SERIES FORECAST & ARIMA MODEL RUNNER   -->
<!-- ============================================================ -->
<div class="mt-8 bg-white p-6 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b pb-4">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Statistical Analysis</div>
      <div class="text-xl font-bold text-slate-900 mt-1">Daily Time-Series ARIMA Model Generator</div>
      <p class="text-xs text-slate-500 mt-0.5">Runs auto-ARIMA machine learning algorithms across daily historical telemetry.</p>
    </div>
    <div class="text-xs text-slate-500">
      Select series and horizon to generate mathematical confidence intervals.
    </div>
  </div>

  <form id="arimaForecastForm" class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4" method="post">
    <div class="md:col-span-2">
      <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">What to forecast</label>
      <select name="series_key" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <?php foreach ($seriesOptions as $key => $label): ?>
          <option value="<?= h($key) ?>" <?= $seriesKey === $key ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Days to look ahead (Horizon)</label>
      <input name="horizon" value="<?= h($horizon) ?>" type="number" min="1" max="90" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500" />
    </div>
    <div class="md:col-span-3">
      <button class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow transition" type="submit">
        <i class="fas fa-calculator mr-1.5 text-xs"></i> Run ARIMA Model
      </button>
    </div>
  </form>
</div>

<div class="mt-6 bg-white p-5 rounded-xl shadow border border-slate-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Daily Forecast Snapshot</div>
      <div class="text-lg font-bold text-slate-900 mt-1">Fast Mathematical Snapshot</div>
      <p class="text-xs text-slate-500 mt-0.5" id="snapshotIntro">Loading quick forecast snapshot...</p>
    </div>
    <div class="text-xs text-slate-500" id="snapshotMeta">Using selected series settings.</div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
      <div class="text-2xl font-bold mt-2" id="snapshotAverage">--</div>
      <div class="text-xs text-slate-500 mt-1" id="snapshotUnit">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
      <div class="text-2xl font-bold mt-2" id="snapshotPeak">--</div>
      <div class="text-xs text-slate-500 mt-1" id="snapshotPeakDate">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs uppercase tracking-widest text-slate-400">Expected Total</div>
      <div class="text-2xl font-bold mt-2" id="snapshotTotal">--</div>
      <div class="text-xs text-slate-500 mt-1" id="snapshotHorizon">--</div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="flex items-center justify-between">
        <span class="text-xs uppercase tracking-widest text-slate-400">Model Fit (MAPE)</span>
        <span id="snapshotRatingBadge" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">Fit</span>
      </div>
      <div class="text-2xl font-bold mt-2 font-mono" id="snapshotMape">--</div>
      <div class="text-xs text-slate-500 mt-1" id="snapshotMae">--</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2">
      <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Daily Projection Preview</div>
      <div id="snapshotDays" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3"></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Model Insights</div>
      <ul class="mt-3 space-y-2.5 text-xs text-slate-700" id="snapshotSummary">
        <li>Loading summary...</li>
      </ul>
    </div>
  </div>
</div>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
  <div class="xl:col-span-2 bg-white p-6 rounded-xl shadow border border-slate-100">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Seasonal Disease</div>
    <div class="text-lg font-bold text-slate-900 mt-1">Month-Of-Year Case Pattern</div>
    <canvas id="seasonalDiseaseChart" height="120" class="mt-4"></canvas>
  </div>
  <div class="bg-white p-6 rounded-xl shadow border border-slate-100">
    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Peak Months</div>
    <div class="text-lg font-bold text-slate-900 mt-1">Highest Disease Seasons</div>
    <ul class="mt-4 space-y-3 text-xs text-slate-700">
      <?php if (!empty($seasonalTopMonths)): ?>
        <?php foreach ($seasonalTopMonths as $row): ?>
          <li class="border-b border-slate-100 pb-2.5 last:border-b-0 last:pb-0 flex items-center justify-between">
            <span class="font-semibold text-slate-800"><?= h($row['month_label']) ?></span>
            <span class="font-bold text-teal-700"><?= h((string)$row['total_cases']) ?> cases</span>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>No seasonal disease data yet.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<div class="mt-6 bg-white p-6 rounded-xl shadow border border-slate-100">
  <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Disease Case Trends</div>
  <div class="text-lg font-bold text-slate-900 mt-1">Top Disease Trends (Last 12 Months)</div>
  <canvas id="diseaseTrendChart" height="120" class="mt-4"></canvas>
</div>

<!-- Model Execution Logs History Section -->
<?php
// Query recent runs
$recentRuns = ForecastLogger::getRecentLogs(30);
$successRunsCount = count(array_filter($recentRuns, fn($r) => $r['status'] === 'success'));
$failedRunsCount = count(array_filter($recentRuns, fn($r) => $r['status'] === 'failed'));
?>
<div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Analytics History</div>
      <div class="text-lg font-semibold">Model Execution Logs &amp; ARIMA Parameters</div>
      <p class="text-sm text-slate-500 mt-1">Audit log of fitted models, training points, and statistical parameters.</p>
    </div>
    
    <!-- Filter Toggle: Successful (default) | All Runs | Failed -->
    <div class="flex flex-wrap sm:inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1 text-xs font-medium self-start md:self-auto gap-1 sm:gap-0">
      <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all bg-white text-slate-900 shadow-xs font-semibold" data-filter="success">
        <i class="fas fa-check-circle text-emerald-600 mr-1"></i> Successful
        <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"><?= $successRunsCount ?></span>
      </button>
      <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900" data-filter="all">
        All Runs
        <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold"><?= count($recentRuns) ?></span>
      </button>
      <?php if ($failedRunsCount > 0): ?>
        <button type="button" class="log-filter-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900" data-filter="failed">
          <i class="fas fa-exclamation-triangle text-amber-600 mr-1"></i> Failed
          <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold"><?= $failedRunsCount ?></span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-6 overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
    <table class="w-full text-left border-collapse text-sm min-w-[760px]">
      <thead>
        <tr class="border-b border-slate-200 text-slate-400 font-medium text-xs">
          <th class="py-3 px-4">Run Time</th>
          <th class="py-3 px-4">Series Key</th>
          <th class="py-3 px-4">Horizon</th>
          <th class="py-3 px-4">Model Type</th>
          <th class="py-3 px-4">Fitted Model</th>
          <th class="py-3 px-3">MAE</th>
          <th class="py-3 px-3">RMSE</th>
          <th class="py-3 px-3">MAPE</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-slate-700" id="logTableBody">
        <tr id="noFilteredLogsRow" class="hidden">
          <td colspan="10" class="py-8 px-4 text-center text-slate-400">
            <i class="fas fa-filter mr-1.5 text-slate-300"></i> No logs match the selected filter.
          </td>
        </tr>
        <?php if (empty($recentRuns)): ?>
          <tr>
            <td colspan="10" class="py-4 px-4 text-center text-slate-400">No forecasting logs recorded yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentRuns as $run): 
            $seriesLabel = $seriesOptions[$run['series_key']] ?? $run['series_key'];
            $statusClass = $run['status'] === 'success' 
              ? 'bg-green-50 text-green-700 border border-green-200' 
              : 'bg-red-50 text-red-700 border border-red-200';
            
            $modelOrderText = 'N/A';
            if ($run['model_type'] === 'ARIMA') {
                $order = $run['model_order'] ?? '';
                $seasonal = $run['seasonal_order'] ?? '';
                if ($order) {
                    $modelOrderText = 'ARIMA' . $order;
                    if ($seasonal && $seasonal !== '(0, 0, 0, 0)' && $seasonal !== '(0, 0, 0, 1)' && $seasonal !== '(0, 0, 0, 7)') {
                        $modelOrderText .= ' x ' . $seasonal;
                    }
                }
            } else {
                $modelOrderText = 'Fast Average';
            }
          ?>
            <tr class="log-row hover:bg-slate-50 transition-colors" data-status="<?= h($run['status']) ?>">
              <td class="py-3 px-4 whitespace-nowrap">
                <?= h(date('M d, Y h:i A', strtotime($run['created_at']))) ?>
              </td>
              <td class="py-3 px-4 font-medium">
                <?= h($seriesLabel) ?>
              </td>
              <td class="py-3 px-4">
                <?= h($run['horizon']) ?> days
              </td>
              <td class="py-3 px-4">
                <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                  <?= h($run['model_type']) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-xs font-semibold text-slate-700">
                <?= h($modelOrderText === 'N/A' ? 'Auto-fitted model' : $modelOrderText) ?>
              </td>
              <td class="py-3 px-3 font-mono text-xs text-slate-700">
                <?= $run['mae'] !== null ? h(number_format((float)$run['mae'], 2)) : '<span class="text-slate-300">--</span>' ?>
              </td>
              <td class="py-3 px-3 font-mono text-xs text-slate-700">
                <?= $run['rmse'] !== null ? h(number_format((float)$run['rmse'], 2)) : '<span class="text-slate-300">--</span>' ?>
              </td>
              <td class="py-3 px-3 text-xs font-medium">
                <?php if ($run['mape'] !== null): ?>
                  <?php
                    $mVal = (float)$run['mape'];
                    $mClass = $mVal < 20.0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($mVal < 50.0 ? 'text-blue-700 bg-blue-50 border-blue-200' : 'text-amber-700 bg-amber-50 border-amber-200');
                  ?>
                  <span class="px-2 py-0.5 rounded border text-[11px] font-semibold <?= $mClass ?>">
                    <?= h(number_format($mVal, 1)) ?>%
                  </span>
                <?php else: ?>
                  <span class="text-slate-300">--</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium <?= $statusClass ?>">
                  <?= h(ucfirst($run['status'])) ?>
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <?php if ($run['status'] === 'success'): ?>
                  <button type="button" 
                          class="view-run-details-btn text-teal-600 hover:text-teal-900 border border-teal-200 hover:bg-teal-50 px-3 py-1 rounded transition-colors text-xs font-medium"
                          data-run-id="<?= (int)$run['id'] ?>">
                    <i class="fas fa-eye mr-1"></i> View Details
                  </button>
                <?php else: ?>
                  <button type="button" 
                          class="view-run-error-btn text-red-600 hover:text-red-900 border border-red-200 hover:bg-red-50 px-3 py-1 rounded transition-colors text-xs font-medium"
                          data-error-msg="<?= h($run['error_message'] ?? 'Unknown script error') ?>">
                    <i class="fas fa-exclamation-triangle mr-1"></i> View Error
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal for Model Log Details -->
<div id="modelLogModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <!-- Backdrop -->
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="modelLogModalBackdrop"></button>
  
  <div class="flex min-h-screen items-center justify-center p-2 sm:p-4">
    <!-- Modal Card -->
    <div class="relative bg-white rounded-xl shadow-xl border border-slate-200 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-100 bg-slate-50">
        <div>
          <h3 class="text-base sm:text-lg font-semibold text-slate-900" id="modalTitle">Model Details</h3>
          <p class="text-xs text-slate-500 mt-0.5" id="modalSubtitle">Run ID: --</p>
        </div>
        <button type="button" id="modelLogModalClose" class="text-slate-400 hover:text-slate-600 focus:outline-none rounded-lg p-1 hover:bg-slate-100 transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-4 sm:space-y-6 flex-1">
        
        <!-- Run Info Summary Card -->
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 p-3 sm:p-4 bg-slate-50 border border-slate-100 rounded-xl">
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Model Type</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalModelType">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Execution Time</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalExecTime">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">History Size</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalHistorySize">--</div>
          </div>
          <div>
            <div class="text-xs text-slate-400 uppercase tracking-wider">Training Points</div>
            <div class="text-sm font-semibold text-slate-800 mt-1" id="modalTrainingPoints">--</div>
          </div>
        </div>

        <!-- Model Evaluation & Accuracy (MAE, RMSE, MAPE) -->
        <div class="border border-slate-200 rounded-xl p-5 bg-gradient-to-r from-slate-50 to-teal-50/20">
          <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">
            <h4 class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
              <i class="fas fa-chart-line text-teal-600"></i> Model Accuracy & Error Evaluation
            </h4>
            <span id="modalAccuracyRating" class="px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">Statistical Fit</span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">MAE</span>
                <span class="text-[10px] text-slate-400 font-mono">Mean Absolute</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalMae">--</div>
              <div class="text-xs text-slate-500 mt-1" id="modalMaeDesc">Average deviation per day</div>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">RMSE</span>
                <span class="text-[10px] text-slate-400 font-mono">Root Mean Sq.</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalRmse">--</div>
              <div class="text-xs text-slate-500 mt-1">Penalizes large outlier spikes</div>
            </div>
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-slate-500 font-medium">MAPE</span>
                <span class="text-[10px] text-slate-400 font-mono">Percentage Error</span>
              </div>
              <div class="text-xl font-bold text-slate-800 mt-1 font-mono" id="modalMape">--</div>
              <div class="text-xs text-slate-500 mt-1">Relative percentage error</div>
            </div>
          </div>
        </div>

        <!-- Key Takeaways & Planning Insights -->
        <div id="modalSummarySection" class="border border-slate-150 rounded-xl p-5 bg-slate-50/50">
          <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-150 pb-2 mb-3">
            <i class="fas fa-lightbulb mr-1.5 text-amber-500"></i> Key Takeaways & Planning Insights
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-3">
            <div class="space-y-3">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Expected Daily Average</span>
                <span class="font-bold text-slate-800" id="modalInsightAvg">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Total Expected Volume</span>
                <span class="font-bold text-slate-800" id="modalInsightTotal">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Planning Period (Horizon)</span>
                <span class="font-semibold text-slate-800" id="modalInsightHorizon">--</span>
              </div>
            </div>
            <div class="space-y-3">
              <div class="flex justify-between items-center text-sm">
                <span class="text-slate-500">Busiest Expected Day</span>
                <span class="font-bold text-slate-800" id="modalInsightPeak">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Quietest Expected Day</span>
                <span class="font-bold text-slate-800" id="modalInsightQuiet">--</span>
              </div>
              <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                <span class="text-slate-500">Usual Daily Range</span>
                <span class="font-semibold text-slate-800" id="modalInsightRange">--</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Forecast Predictions Table -->
        <div>
          <h4 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-2 mb-3">
            <i class="fas fa-table mr-1.5 text-blue-500"></i> Forecast Results (Daily Predictions)
          </h4>
          <div class="border border-slate-150 rounded-xl overflow-hidden max-h-[40vh] overflow-y-auto">
            <table class="w-full text-left border-collapse text-xs relative">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-150 text-slate-500 sticky top-0 z-10 shadow-sm">
                  <th class="py-2.5 px-4">Forecast Date</th>
                  <th class="py-2.5 px-4 text-right">Expected Amount</th>
                  <th class="py-2.5 px-4 text-right">Min Expected</th>
                  <th class="py-2.5 px-4 text-right">Max Expected</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700" id="modalPredictionsBody">
                <!-- Dynamic rows -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="flex justify-end items-center px-6 py-4 border-t border-slate-100 bg-slate-50">
        <button type="button" id="modelLogModalCloseBtn" class="bg-slate-900 hover:bg-slate-800 text-white text-xs px-4 py-2 rounded-lg font-medium shadow transition-colors">
          Close Details
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SECTION 4: ARIMA RESULTS & ERROR METRICS DISPLAY              -->
<!-- ============================================================ -->
<div id="arimaResultsSection" class="<?= $summary ? '' : 'hidden' ?>">
  <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <div class="text-xs uppercase tracking-widest font-bold text-teal-700 flex items-center gap-1.5">
          <i class="fas fa-robot"></i>
          <span>What The ARIMA Model Says</span>
        </div>
        <p class="mt-2 text-lg font-medium text-slate-900" id="arimaIntroText"><?= h($summary['intro'] ?? '') ?></p>
        <div class="mt-2 text-sm text-slate-600" id="arimaHistoryMeta">
          <?php if ($summary): ?>
            Based on <?= h($summary['history_points']) ?> days of history from <?= h($summary['history_start']) ?> to <?= h($summary['history_end']) ?>, with the most recent <?= h($summary['training_points'] ?? $summary['history_points']) ?> days used for model fitting.
          <?php endif; ?>
        </div>
      </div>
      <div class="print:hidden">
        <button class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition" type="button" onclick="window.print()">
          <i class="fas fa-print mr-1.5 text-xs"></i> Print Forecast
        </button>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mt-6">
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Average Per Day</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaAvgPerDay"><?= h(number_format((float)($summary['forecast_average'] ?? 0), 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1" id="arimaUnitExpected"><?= h($unitLabel) ?> expected each day</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Expected Total</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaExpectedTotal"><?= h(number_format((float)($summary['expected_total'] ?? 0), 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1" id="arimaHorizonDays">Across <?= h($horizon) ?> days</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Busiest Day (Peak)</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaPeakValue"><?= h(number_format((float)($summary['peak_value'] ?? 0), 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1" id="arimaPeakDate"><?= h($summary['peak_date'] ?? '--') ?></div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Compared To Recent Days</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaRecentAvg"><?= h(number_format((float)($summary['recent_average'] ?? 0), 1)) ?></div>
      <div class="text-sm text-slate-500 mt-1">Recent daily average</div>
    </div>
  </div>

  <!-- Model Evaluation & Error Metrics (MAE, RMSE, MAPE) Section -->
  <div class="mt-6 bg-white p-6 rounded-xl shadow border-l-4 border-teal-600 border border-slate-100" id="arimaMetricsCard">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-150">
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2.5 py-0.5 rounded border border-teal-200">
            <i class="fas fa-microchip mr-1"></i> Model Evaluation
          </span>
          <span class="text-xs text-slate-400">Quantitative Statistical Analysis</span>
        </div>
        <h3 class="text-xl font-bold text-slate-900 mt-1">Forecast Error Metrics: MAE, RMSE &amp; MAPE</h3>
        <p class="text-sm text-slate-500 mt-0.5" id="arimaTrainingCountMeta">
          Model fit accuracy evaluated across <?= h($summary['training_points'] ?? 180) ?> daily data points in the training window.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <?php
          $mapeVal = (float)($metrics['mape'] ?? 0);
          $ratingText = $metrics['accuracy_rating'] ?? ($mapeVal < 10.0 ? 'High Accuracy' : ($mapeVal < 20.0 ? 'Good Fit' : ($mapeVal < 50.0 ? 'Reasonable' : 'High Variance')));
          $badgeBg = $mapeVal < 20.0 ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : ($mapeVal < 50.0 ? 'bg-blue-50 text-blue-800 border-blue-300' : 'bg-amber-50 text-amber-800 border-amber-300');
        ?>
        <div class="text-left md:text-right">
          <div class="text-xs text-slate-400 uppercase tracking-widest font-medium">Model Rating</div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border mt-0.5 <?= $badgeBg ?>" id="arimaRatingBadge">
            <span class="h-2 w-2 rounded-full bg-current"></span>
            <span id="arimaRatingText"><?= h($ratingText) ?></span>
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <!-- MAE Card -->
      <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Mean Absolute Error</span>
          <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">MAE</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
          <span id="arimaMaeVal"><?= h(number_format((float)($metrics['mae'] ?? 0), 2)) ?></span>
          <span class="text-sm font-normal text-slate-500 font-sans arima-unit-label"><?= h($unitLabel) ?></span>
        </div>
        <p class="text-xs text-slate-600 mt-2 leading-relaxed" id="arimaMaeDesc">
          On average, daily predictions deviate from actual activity by &plusmn;<strong id="arimaMaeStrong"><?= h(number_format((float)($metrics['mae'] ?? 0), 2)) ?></strong> <span class="arima-unit-label"><?= h($unitLabel) ?></span>.
        </p>
        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
          <span>Formula: &Sigma;|y &minus; &ycirc;| / n</span>
          <span class="text-teal-600 font-semibold">Linear Penalty</span>
        </div>
      </div>

      <!-- RMSE Card -->
      <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Root Mean Squared Error</span>
          <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">RMSE</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
          <span id="arimaRmseVal"><?= h(number_format((float)($metrics['rmse'] ?? 0), 2)) ?></span>
          <span class="text-sm font-normal text-slate-500 font-sans arima-unit-label"><?= h($unitLabel) ?></span>
        </div>
        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
          Measures residual spread; penalizes large sudden demand spikes or outlier days more heavily.
        </p>
        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
          <span>Formula: &radic;(&Sigma;(y &minus; &ycirc;)&sup2; / n)</span>
          <span class="text-teal-600 font-semibold">Quadratic Penalty</span>
        </div>
      </div>

      <!-- MAPE Card -->
      <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 relative overflow-hidden group hover:border-teal-400 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs uppercase font-bold tracking-wider text-slate-500">Mean Absolute % Error</span>
          <span class="text-xs font-mono px-2 py-0.5 rounded bg-white text-slate-700 border border-slate-200 shadow-2xs font-semibold">MAPE</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-900 mt-3 font-mono">
          <span id="arimaMapeVal"><?= h(number_format((float)($metrics['mape'] ?? 0), 1)) ?></span><span class="text-xl font-bold text-slate-500 font-sans">%</span>
        </div>
        <p class="text-xs text-slate-600 mt-2 leading-relaxed" id="arimaMapeDesc">
          Relative percentage deviation across non-zero active days.
        </p>
        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-mono">
          <span>Formula: &Sigma;(|y &minus; &ycirc;| / y) / n</span>
          <span class="text-teal-600 font-semibold">Scale-Independent</span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-slate-800">Historical Telemetry &amp; ARIMA Forecast Range</div>
        <span class="text-xs text-slate-400">95% Confidence Bounds</span>
      </div>
      <div class="h-72">
        <canvas id="forecastChart"></canvas>
      </div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Planning Notes</div>
      <ul class="mt-4 space-y-3 text-sm text-slate-700" id="arimaPlanningNotes">
        <?php if ($summary && !empty($planningNotes)): ?>
          <?php foreach ($planningNotes as $note): ?>
            <li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0"><?= h($note) ?></li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Next 7 Days</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaFirstWeekAvg"><?= isset($firstWeekAverage) ? h(number_format($firstWeekAverage, 1)) : '--' ?></div>
      <div class="text-sm text-slate-500 mt-1"><span class="arima-unit-label"><?= h($unitLabel) ?></span> per day</div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Rest Of Forecast Horizon</div>
      <div class="text-2xl font-bold mt-2 text-slate-900" id="arimaLaterAvg"><?= isset($laterAverage) ? h(number_format($laterAverage, 1)) : '--' ?></div>
      <div class="text-sm text-slate-500 mt-1"><span class="arima-unit-label"><?= h($unitLabel) ?></span> per day</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
    <div class="xl:col-span-2 bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">Daily Projections</div>
          <div class="text-lg font-bold text-slate-900 mt-1">Next Few Days At A Glance</div>
        </div>
        <div class="text-xs text-slate-500" id="arimaUpcomingHeader">Upcoming days</div>
      </div>
      <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5" id="arimaUpcomingCards">
        <?php if (!empty($forecastRows)): ?>
          <?php foreach (array_slice($forecastRows, 0, 6) as $row): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
              <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold"><?= h($row['date']) ?></div>
              <div class="text-2xl font-bold mt-1.5 text-slate-900"><?= h(number_format($row['value'], 1)) ?></div>
              <div class="text-xs text-slate-500 mt-1"><?= h($unitLabel) ?> expected</div>
              <div class="text-[11px] text-slate-400 mt-2 font-mono">Range: <?= h(number_format($row['lower'], 1)) ?> to <?= h(number_format($row['upper'], 1)) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="bg-white p-5 rounded-xl shadow border border-slate-100">
      <div class="text-xs uppercase tracking-widest text-slate-500 font-semibold">Report Summary</div>
      <ul class="mt-4 space-y-3 text-xs text-slate-700" id="arimaReportSummary">
        <li class="border-b border-slate-100 pb-2.5">Forecast generated on <?= h($result['generated_on'] ?? date('Y-m-d')) ?>.</li>
        <li class="border-b border-slate-100 pb-2.5">Series selected: <?= h($seriesOptions[$seriesKey] ?? $seriesKey) ?>.</li>
        <li class="border-b border-slate-100 pb-2.5">Planning horizon: <?= h($horizon) ?> days.</li>
        <li>Use this statistical projection for staffing, clinical inventory preparation, and scheduling.</li>
      </ul>
    </div>
  </div>
</div>

<script>
  let forecastChartInstance = null;

  function initOrUpdateForecastChart(historyRows, forecastRows) {
    const canvas = document.getElementById('forecastChart');
    if (!canvas) return;

    const historyLabels = historyRows.map((row) => row.date);
    const forecastLabels = forecastRows.map((row) => row.date);
    const labels = [...historyLabels, ...forecastLabels];

    const historyValues = historyRows.map((row) => Number(row.value));
    const forecastValues = forecastRows.map((row) => Number(row.value));
    const lowerValues = forecastRows.map((row) => Number(row.lower));
    const upperValues = forecastRows.map((row) => Number(row.upper));
    const lastHistoryValue = historyValues.length ? historyValues[historyValues.length - 1] : null;

    const historyDataset = [...historyValues, ...Array(forecastValues.length).fill(null)];
    const forecastDataset = [...Array(Math.max(historyValues.length - 1, 0)).fill(null), lastHistoryValue, ...forecastValues];
    const lowerDataset = [...Array(historyValues.length).fill(null), ...lowerValues];
    const upperDataset = [...Array(historyValues.length).fill(null), ...upperValues];

    if (forecastChartInstance) {
      forecastChartInstance.destroy();
    }

    forecastChartInstance = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Actual Telemetry',
            data: historyDataset,
            borderColor: '#0f172a',
            backgroundColor: 'rgba(15,23,42,0.08)',
            tension: 0.25,
            pointRadius: 1.5,
            borderWidth: 2
          },
          {
            label: 'Lower Bound (95% CI)',
            data: lowerDataset,
            borderColor: 'rgba(14,165,164,0)',
            backgroundColor: 'rgba(14,165,164,0.12)',
            pointRadius: 0,
            borderWidth: 0
          },
          {
            label: 'Upper Bound (95% CI)',
            data: upperDataset,
            borderColor: 'rgba(14,165,164,0)',
            backgroundColor: 'rgba(14,165,164,0.12)',
            pointRadius: 0,
            borderWidth: 0,
            fill: '-1'
          },
          {
            label: 'ARIMA Forecast',
            data: forecastDataset,
            borderColor: '#0ea5a4',
            backgroundColor: 'rgba(14,165,164,0.08)',
            tension: 0.3,
            pointRadius: 2,
            borderDash: [5, 4],
            borderWidth: 2.5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' }
        },
        scales: {
          x: { ticks: { maxTicksLimit: 12 } },
          y: { beginAtZero: true }
        }
      }
    });
  }

  <?php if ($summary && !empty($historyRows) && !empty($forecastRows)): ?>
    initOrUpdateForecastChart(<?= json_encode($historyRows) ?>, <?= json_encode($forecastRows) ?>);
  <?php endif; ?>

  // ============================================================
  // ASYNC ARIMA MODEL RUNNER WITH SWEETALERT LOADING
  // ============================================================
  document.addEventListener('DOMContentLoaded', function () {
    const arimaForm = document.getElementById('arimaForecastForm');
    if (!arimaForm) return;

    arimaForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const seriesKey = arimaForm.querySelector('select[name="series_key"]').value;
      const horizon = arimaForm.querySelector('input[name="horizon"]').value || '30';
      const unit = snapshotUnitMap[seriesKey] || 'items';
      const label = snapshotSeriesLabelMap[seriesKey] || seriesKey;

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Training ARIMA Model...',
          html: `
            <div class="py-2 text-center text-sm text-slate-600">
              <div class="mb-3 inline-flex p-3.5 rounded-full bg-teal-50 text-teal-600 border border-teal-200 shadow-2xs">
                <i class="fas fa-brain fa-spin text-2xl"></i>
              </div>
              <p class="font-bold text-slate-800 text-base">Fitting auto-ARIMA for ${label}</p>
              <p class="text-xs text-slate-400 mt-1">Analyzing historical daily telemetry &amp; calculating 95% confidence bounds across ${horizon} days...</p>
            </div>
          `,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
      }

      const body = new URLSearchParams({
        series_key: seriesKey,
        horizon: horizon
      });

      fetch('/HealthLogs/public/forecast_run.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
          if (!ok || data.error || !data.summary || !data.forecast) {
            throw new Error(data.error || 'Failed to generate ARIMA forecast.');
          }

          const summary = data.summary || {};
          const metrics = data.metrics || summary;
          const forecast = data.forecast || [];
          const history = data.history || [];

          // 1. Update text & summary values
          document.getElementById('arimaIntroText').textContent = summary.intro || '';
          document.getElementById('arimaHistoryMeta').textContent = `Based on ${summary.history_points || history.length} days of history from ${summary.history_start || '--'} to ${summary.history_end || '--'}, with the most recent ${summary.training_points || history.length} days used for model fitting.`;

          document.getElementById('arimaAvgPerDay').textContent = Number(summary.forecast_average || 0).toFixed(1);
          document.getElementById('arimaUnitExpected').textContent = `${unit} expected each day`;
          document.getElementById('arimaExpectedTotal').textContent = Number(summary.expected_total || 0).toFixed(1);
          document.getElementById('arimaHorizonDays').textContent = `Across ${horizon} days`;
          document.getElementById('arimaPeakValue').textContent = Number(summary.peak_value || 0).toFixed(1);
          document.getElementById('arimaPeakDate').textContent = summary.peak_date || '--';
          document.getElementById('arimaRecentAvg').textContent = Number(summary.recent_average || 0).toFixed(1);

          // 2. Update error metrics
          document.querySelectorAll('.arima-unit-label').forEach(el => el.textContent = unit);
          document.getElementById('arimaMaeVal').textContent = metrics.mae !== undefined ? Number(metrics.mae).toFixed(2) : '--';
          document.getElementById('arimaMaeStrong').textContent = metrics.mae !== undefined ? Number(metrics.mae).toFixed(2) : '--';
          document.getElementById('arimaRmseVal').textContent = metrics.rmse !== undefined ? Number(metrics.rmse).toFixed(2) : '--';
          document.getElementById('arimaMapeVal').textContent = metrics.mape !== undefined ? Number(metrics.mape).toFixed(1) : '--';

          const mapeNum = Number(metrics.mape || 0);
          const rating = metrics.accuracy_rating || (mapeNum < 10 ? 'High Accuracy' : (mapeNum < 20 ? 'Good Fit' : (mapeNum < 50 ? 'Reasonable' : 'High Variance')));
          document.getElementById('arimaRatingText').textContent = rating;
          document.getElementById('arimaRatingBadge').className = `inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border mt-0.5 ${mapeNum < 20 ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (mapeNum < 50 ? 'bg-blue-50 text-blue-800 border-blue-300' : 'bg-amber-50 text-amber-800 border-amber-300')}`;

          // 3. Update planning notes & averages
          const firstWeek = forecast.slice(0, Math.min(7, forecast.length));
          const laterPeriod = forecast.length > 7 ? forecast.slice(7) : [];
          const firstWeekAvg = firstWeek.length ? (firstWeek.reduce((a, b) => a + Number(b.value), 0) / firstWeek.length) : 0;
          const laterAvg = laterPeriod.length ? (laterPeriod.reduce((a, b) => a + Number(b.value), 0) / laterPeriod.length) : Number(summary.forecast_average || 0);

          document.getElementById('arimaFirstWeekAvg').textContent = firstWeekAvg.toFixed(1);
          document.getElementById('arimaLaterAvg').textContent = laterAvg.toFixed(1);

          const changeVsRecent = Number(summary.forecast_average || 0) - Number(summary.recent_average || 0);
          const changeWord = Math.abs(changeVsRecent) < 0.5 ? 'about the same as' : (changeVsRecent > 0 ? 'higher than' : 'lower than');

          document.getElementById('arimaPlanningNotes').innerHTML = `
            <li class="border-b border-slate-100 pb-3">Plan for around <strong>${Number(summary.forecast_average || 0).toFixed(1)} ${unit}</strong> per day.</li>
            <li class="border-b border-slate-100 pb-3">That is <strong>${changeWord}</strong> the recent daily average of ${Number(summary.recent_average || 0).toFixed(1)}.</li>
            <li class="border-b border-slate-100 pb-3">The peak single-day projection is <strong>${Number(summary.peak_value || 0).toFixed(1)}</strong> on ${summary.peak_date || '--'}.</li>
          `;

          // 4. Update upcoming daily cards
          const upcomingSlice = forecast.slice(0, 6);
          document.getElementById('arimaUpcomingCards').innerHTML = upcomingSlice.map(r => `
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
              <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold">${r.date}</div>
              <div class="text-2xl font-bold mt-1.5 text-slate-900">${Number(r.value).toFixed(1)}</div>
              <div class="text-xs text-slate-500 mt-1">${unit} expected</div>
              <div class="text-[11px] text-slate-400 mt-2 font-mono">Range: ${Number(r.lower).toFixed(1)} to ${Number(r.upper).toFixed(1)}</div>
            </div>
          `).join('');

          // 5. Update report summary list
          document.getElementById('arimaReportSummary').innerHTML = `
            <li class="border-b border-slate-100 pb-2.5">Forecast generated on ${data.generated_on || new Date().toISOString().split('T')[0]}.</li>
            <li class="border-b border-slate-100 pb-2.5">Series selected: ${label}.</li>
            <li class="border-b border-slate-100 pb-2.5">Planning horizon: ${horizon} days.</li>
            <li class="border-b border-slate-100 pb-2.5">Fitted Model: <strong>ARIMA${summary.model || '(auto)'}</strong>.</li>
            <li>Use this statistical projection for staffing, clinical inventory preparation, and scheduling.</li>
          `;

          // 6. Reveal section and update chart
          const resultsSection = document.getElementById('arimaResultsSection');
          resultsSection.classList.remove('hidden');
          initOrUpdateForecastChart(history, forecast);

          // 7. Smooth scroll into view
          resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

          if (typeof Swal !== 'undefined') {
            Swal.close();
            const Toast = Swal.mixin({
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 4000,
              timerProgressBar: true
            });
            Toast.fire({
              icon: 'success',
              title: `ARIMA Model Fitted: ARIMA${summary.model || ''} (${horizon} Days)`
            });
          }
        })
        .catch(err => {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'ARIMA Forecast Failed',
              text: err.message || 'An error occurred while running the ARIMA model.'
            });
          } else {
            alert(err.message || 'Failed to generate forecast.');
          }
        });
    });
  });
</script>

<script>
  const snapshotSeriesLabelMap = <?= json_encode($seriesOptions) ?>;
  const snapshotUnitMap = <?= json_encode($seriesUnits) ?>;
  const snapshotSeriesInput = document.querySelector('select[name="series_key"]');
  const snapshotHorizonInput = document.querySelector('input[name="horizon"]');
  const snapshotIntroEl = document.getElementById('snapshotIntro');
  const snapshotMetaEl = document.getElementById('snapshotMeta');
  const snapshotAverageEl = document.getElementById('snapshotAverage');
  const snapshotUnitEl = document.getElementById('snapshotUnit');
  const snapshotPeakEl = document.getElementById('snapshotPeak');
  const snapshotPeakDateEl = document.getElementById('snapshotPeakDate');
  const snapshotTotalEl = document.getElementById('snapshotTotal');
  const snapshotHorizonEl = document.getElementById('snapshotHorizon');
  const snapshotMapeEl = document.getElementById('snapshotMape');
  const snapshotMaeEl = document.getElementById('snapshotMae');
  const snapshotRatingBadgeEl = document.getElementById('snapshotRatingBadge');
  const snapshotDaysEl = document.getElementById('snapshotDays');
  const snapshotSummaryEl = document.getElementById('snapshotSummary');

  function renderSnapshotError(message) {
    snapshotIntroEl.textContent = message;
    snapshotAverageEl.textContent = '--';
    snapshotUnitEl.textContent = '--';
    snapshotPeakEl.textContent = '--';
    snapshotPeakDateEl.textContent = '--';
    snapshotTotalEl.textContent = '--';
    snapshotHorizonEl.textContent = '--';
    if (snapshotMapeEl) snapshotMapeEl.textContent = '--';
    if (snapshotMaeEl) snapshotMaeEl.textContent = '--';
    if (snapshotRatingBadgeEl) {
      snapshotRatingBadgeEl.textContent = 'Fit';
      snapshotRatingBadgeEl.className = 'text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200';
    }
    snapshotDaysEl.innerHTML = '';
    snapshotSummaryEl.innerHTML = '<li>Forecast snapshot is not available right now.</li>';
  }

  function renderSnapshot(data, seriesKey, horizon) {
    const summary = data.summary || {};
    const metrics = data.metrics || summary;
    const rows = (data.forecast || []).slice(0, 6);
    const unit = snapshotUnitMap[seriesKey] || 'items';
    const label = snapshotSeriesLabelMap[seriesKey] || seriesKey;

    snapshotIntroEl.textContent = summary.intro || 'Quick forecast ready.';
    snapshotMetaEl.textContent = `${label} for the next ${horizon} days`;
    snapshotAverageEl.textContent = Number(summary.forecast_average || 0).toFixed(1);
    snapshotUnitEl.textContent = `${unit} expected each day`;
    snapshotPeakEl.textContent = Number(summary.peak_value || 0).toFixed(1);
    snapshotPeakDateEl.textContent = summary.peak_date || '--';
    snapshotTotalEl.textContent = Number(summary.expected_total || 0).toFixed(1);
    snapshotHorizonEl.textContent = `Across ${horizon} days`;

    if (snapshotMapeEl) {
      snapshotMapeEl.textContent = (metrics.mape !== undefined && metrics.mape !== null) ? `${Number(metrics.mape).toFixed(1)}%` : '--';
    }
    if (snapshotMaeEl) {
      snapshotMaeEl.textContent = (metrics.mae !== undefined && metrics.mae !== null) ? `MAE: ±${Number(metrics.mae).toFixed(1)} ${unit}` : '--';
    }
    if (snapshotRatingBadgeEl && metrics.mape !== undefined && metrics.mape !== null) {
      const m = Number(metrics.mape);
      const rating = metrics.accuracy_rating || (m < 10 ? 'High Accuracy' : (m < 20 ? 'Good Fit' : (m < 50 ? 'Reasonable' : 'High Variance')));
      snapshotRatingBadgeEl.textContent = rating;
      snapshotRatingBadgeEl.className = `text-[10px] font-bold px-2 py-0.5 rounded border ${m < 20 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (m < 50 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200')}`;
    }

    snapshotDaysEl.innerHTML = rows.map((row) => `
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">${row.date}</div>
        <div class="text-2xl font-semibold mt-2">${Number(row.value).toFixed(1)}</div>
        <div class="text-sm text-slate-500 mt-1">${unit} expected</div>
        <div class="text-xs text-slate-400 mt-3">Range: ${Number(row.lower).toFixed(1)} to ${Number(row.upper).toFixed(1)}</div>
      </div>
    `).join('');

    snapshotSummaryEl.innerHTML = [
      `Forecast generated on ${data.generated_on || '--'}.`,
      `Series selected: ${label}.`,
      `Planning horizon: ${horizon} days.`,
      'Use this snapshot for quick planning before running the full forecast report.'
    ].map((line) => `<li class="border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">${line}</li>`).join('');
  }

  function loadSnapshot() {
    const seriesKey = snapshotSeriesInput.value;
    const horizon = snapshotHorizonInput.value || '30';
    const body = new URLSearchParams({ series_key: seriesKey, horizon, fast: '1' });

    fetch('/HealthLogs/public/forecast_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error || !data.summary || !data.forecast) {
          throw new Error(data.error || 'Snapshot unavailable');
        }
        renderSnapshot(data, seriesKey, horizon);
      })
      .catch(() => {
        renderSnapshotError('Forecast snapshot is not ready yet.');
      });
  }

  snapshotSeriesInput.addEventListener('change', loadSnapshot);
  snapshotHorizonInput.addEventListener('change', loadSnapshot);
  loadSnapshot();
</script>

<script>
  const seasonalDiseaseRows = <?= json_encode($seasonalDiseaseRows) ?>;
  const diseaseTrendMonths = <?= json_encode($diseaseTrendMonths) ?>;
  const diseaseTrendSeries = <?= json_encode($diseaseTrendSeries) ?>;

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

  // 1. Medicine 3-Month Demand Chart
  const topDemandMeds = <?= json_encode($topDemandMeds ?? []) ?>;
  const medChartCanvas = document.getElementById('medicineForecastBarChart');
  if (medChartCanvas && topDemandMeds.length) {
    const medLabels = topDemandMeds.map(m => m.name.length > 20 ? m.name.substring(0, 18) + '...' : m.name);
    new Chart(medChartCanvas, {
      type: 'bar',
      data: {
        labels: medLabels,
        datasets: [
          {
            label: 'Month 1 (+30d)',
            data: topDemandMeds.map(m => m.forecast_m1),
            backgroundColor: 'rgba(56, 189, 248, 0.85)',
            borderColor: '#0284c7',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Month 2 (+60d)',
            data: topDemandMeds.map(m => m.forecast_m2),
            backgroundColor: 'rgba(14, 165, 233, 0.85)',
            borderColor: '#0369a1',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Month 3 (+90d)',
            data: topDemandMeds.map(m => m.forecast_m3),
            backgroundColor: 'rgba(2, 132, 199, 0.85)',
            borderColor: '#075985',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Current Stock',
            data: topDemandMeds.map(m => m.current_stock),
            type: 'line',
            borderColor: '#0f172a',
            backgroundColor: '#0f172a',
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              afterLabel: function(ctx) {
                const med = topDemandMeds[ctx.dataIndex];
                if (ctx.datasetIndex === 0) {
                  return `Unit: ${med.unit} | Status: ${med.status}`;
                }
                return '';
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
      }
    });
  }

  // Medicine Risk Filter Buttons
  const medFilterBtns = document.querySelectorAll('.med-filter-btn');
  const medRows = document.querySelectorAll('.med-row');
  medFilterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const filter = this.getAttribute('data-med-filter');
      
      medFilterBtns.forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white', 'font-semibold');
        b.classList.add('bg-slate-100', 'text-slate-700');
      });
      this.classList.remove('bg-slate-100', 'text-slate-700');
      this.classList.add('bg-slate-900', 'text-white', 'font-semibold');

      medRows.forEach(row => {
        const status = row.getAttribute('data-med-status');
        if (filter === 'all' || status === filter) {
          row.classList.remove('hidden');
        } else {
          row.classList.add('hidden');
        }
      });
    });
  });

  // 2. Patient Category 3-Month Forecast Chart
  const categoryForecastData = <?= json_encode($categoryForecast ?? []) ?>;
  const catChartCanvas = document.getElementById('categoryForecastBarChart');
  if (catChartCanvas && Object.keys(categoryForecastData).length) {
    const catKeys = Object.keys(categoryForecastData);
    const catLabels = catKeys.map(k => categoryForecastData[k].title.split(' (')[0]);
    new Chart(catChartCanvas, {
      type: 'bar',
      data: {
        labels: catLabels,
        datasets: [
          {
            label: 'Month 1 (+30d)',
            data: catKeys.map(k => categoryForecastData[k].m1),
            backgroundColor: 'rgba(251, 113, 133, 0.85)',
            borderColor: '#e11d48',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Month 2 (+60d)',
            data: catKeys.map(k => categoryForecastData[k].m2),
            backgroundColor: 'rgba(244, 63, 94, 0.85)',
            borderColor: '#be123c',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Month 3 (+90d)',
            data: catKeys.map(k => categoryForecastData[k].m3),
            backgroundColor: 'rgba(225, 29, 72, 0.85)',
            borderColor: '#9f1239',
            borderWidth: 1,
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              afterLabel: function(ctx) {
                const k = catKeys[ctx.dataIndex];
                const cat = categoryForecastData[k];
                if (ctx.datasetIndex === 0) {
                  return `3M Total: ${cat.total_3m} visits | ${cat.active_cohort}`;
                }
                return '';
              }
            }
          }
        },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
        }
      }
    });
  }

  // Model Log Modal Controls
  const modelLogModal = document.getElementById('modelLogModal');
  const modelLogModalBackdrop = document.getElementById('modelLogModalBackdrop');
  const modelLogModalClose = document.getElementById('modelLogModalClose');
  const modelLogModalCloseBtn = document.getElementById('modelLogModalCloseBtn');
  
  function openModelLogModal() {
    if (modelLogModal) modelLogModal.classList.remove('hidden');
  }

  function closeModelLogModal() {
    if (modelLogModal) modelLogModal.classList.add('hidden');
  }

  if (modelLogModalBackdrop) modelLogModalBackdrop.addEventListener('click', closeModelLogModal);
  if (modelLogModalClose) modelLogModalClose.addEventListener('click', closeModelLogModal);
  if (modelLogModalCloseBtn) modelLogModalCloseBtn.addEventListener('click', closeModelLogModal);
  
  // Show error messages using sweetalert
  document.querySelectorAll('.view-run-error-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const errorMsg = this.getAttribute('data-error-msg') || 'Unknown error occurred.';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Model Execution Failed',
          text: errorMsg,
          confirmButtonColor: '#0f172a'
        });
      } else {
        alert("Model Execution Failed:\n" + errorMsg);
      }
    });
  });

  // Fetch and show run details
  document.querySelectorAll('.view-run-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const runId = this.getAttribute('data-run-id');
      if (!runId) return;
      
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Loading Model Details',
          text: 'Retrieving parameters and results from database...',
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => Swal.showLoading()
        });
      }
      
      fetch(`/HealthLogs/public/forecast_run_details.php?run_id=${runId}`)
        .then(res => {
          if (!res.ok) throw new Error('Failed to fetch details');
          return res.json();
        })
        .then(data => {
          if (typeof Swal !== 'undefined') Swal.close();
          
          // Populate run info
          const seriesName = data.run.series_key === 'visits_total' ? 'Patient Visits' : 'Medicine Demand';
          document.getElementById('modalTitle').textContent = `Forecast Details - ${seriesName}`;
          document.getElementById('modalSubtitle').textContent = `Run ID: #${data.run.id} | Generated on ${data.run.created_at}`;
          document.getElementById('modalModelType').textContent = data.run.model_type === 'ARIMA' ? 'ARIMA (Auto-fit)' : 'Fast Average';
          document.getElementById('modalExecTime').textContent = `${data.run.execution_time_seconds || '0.000'} seconds`;
          document.getElementById('modalHistorySize').textContent = data.run.history_points ? `${data.run.history_points} days` : 'N/A';
          document.getElementById('modalTrainingPoints').textContent = data.run.training_points ? `${data.run.training_points} days` : 'N/A';
          
          // Populate accuracy & error metrics
          const maeVal = data.run.mae || (data.parameters && data.parameters.mae);
          const rmseVal = data.run.rmse || (data.parameters && data.parameters.rmse);
          const mapeVal = data.run.mape || (data.parameters && data.parameters.mape);
          const metricUnit = data.run.series_key === 'visits_total' ? 'visits' : 'units';
          
          const modalMaeEl = document.getElementById('modalMae');
          const modalRmseEl = document.getElementById('modalRmse');
          const modalMapeEl = document.getElementById('modalMape');
          const modalRatingEl = document.getElementById('modalAccuracyRating');
          
          if (modalMaeEl) modalMaeEl.textContent = (maeVal !== null && maeVal !== undefined) ? `${Number(maeVal).toFixed(2)} ${metricUnit}` : 'N/A';
          if (modalRmseEl) modalRmseEl.textContent = (rmseVal !== null && rmseVal !== undefined) ? `${Number(rmseVal).toFixed(2)} ${metricUnit}` : 'N/A';
          if (modalMapeEl) modalMapeEl.textContent = (mapeVal !== null && mapeVal !== undefined) ? `${Number(mapeVal).toFixed(1)}%` : 'N/A';
          
          if (modalRatingEl) {
            if (mapeVal !== null && mapeVal !== undefined) {
              const m = Number(mapeVal);
              const rating = m < 10 ? 'High Accuracy' : (m < 20 ? 'Good Fit' : (m < 50 ? 'Reasonable' : 'High Variance'));
              modalRatingEl.textContent = rating;
              modalRatingEl.className = `px-2.5 py-0.5 rounded text-xs font-semibold border ${m < 20 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (m < 50 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200')}`;
            } else {
              modalRatingEl.textContent = 'Statistical Fit';
              modalRatingEl.className = 'px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200';
            }
          }
          
          // Calculate insights
          let total = 0;
          let peakVal = -1;
          let peakDate = '';
          let quietVal = Infinity;
          let quietDate = '';
          let lowerSum = 0;
          let upperSum = 0;
          
          if (data.results && data.results.length > 0) {
            data.results.forEach(res => {
              const val = Number(res.forecast_value);
              total += val;
              lowerSum += Number(res.lower_bound);
              upperSum += Number(res.upper_bound);
              
              if (val > peakVal) {
                peakVal = val;
                peakDate = res.forecast_date;
              }
              if (val < quietVal) {
                quietVal = val;
                quietDate = res.forecast_date;
              }
            });
            
            const avg = total / data.results.length;
            const avgLower = lowerSum / data.results.length;
            const avgUpper = upperSum / data.results.length;
            const unit = data.run.series_key === 'visits_total' ? 'visits' : 'units';
            
            document.getElementById('modalInsightAvg').textContent = `${avg.toFixed(1)} ${unit} / day`;
            document.getElementById('modalInsightTotal').textContent = `${total.toFixed(0)} ${unit}`;
            document.getElementById('modalInsightHorizon').textContent = `${data.results.length} days`;
            document.getElementById('modalInsightPeak').textContent = `${peakVal.toFixed(1)} ${unit} (on ${peakDate})`;
            document.getElementById('modalInsightQuiet').textContent = `${quietVal.toFixed(1)} ${unit} (on ${quietDate})`;
            document.getElementById('modalInsightRange').textContent = `${avgLower.toFixed(1)} to ${avgUpper.toFixed(1)} ${unit}`;
          }
          
          // Render predictions table
          const resultsBody = document.getElementById('modalPredictionsBody');
          resultsBody.innerHTML = '';
          
          if (data.results && data.results.length > 0) {
            data.results.forEach(res => {
              const row = document.createElement('tr');
              row.className = 'hover:bg-slate-50/50 transition-colors';
              row.innerHTML = `
                <td class="py-2 px-4 font-semibold text-slate-600 font-mono">${res.forecast_date}</td>
                <td class="py-2 px-4 text-right font-semibold text-slate-900 font-mono">${Number(res.forecast_value).toFixed(1)}</td>
                <td class="py-2 px-4 text-right text-slate-500 font-mono">${Number(res.lower_bound).toFixed(1)}</td>
                <td class="py-2 px-4 text-right text-slate-500 font-mono">${Number(res.upper_bound).toFixed(1)}</td>
              `;
              resultsBody.appendChild(row);
            });
          } else {
            resultsBody.innerHTML = `<tr><td colspan="4" class="py-4 px-4 text-center text-slate-400">No predictions recorded.</td></tr>`;
          }
          
          openModelLogModal();
        })
        .catch(err => {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error Loading Details',
              text: err.message
            });
          } else {
            alert('Error loading details: ' + err.message);
          }
        });
    });
  });

  // Log Filter Tab Switching (Successful by default)
  const logFilterBtns = document.querySelectorAll('.log-filter-btn');
  const logRows = document.querySelectorAll('.log-row');
  const noFilteredLogsRow = document.getElementById('noFilteredLogsRow');

  function applyLogFilter(filter) {
    let visibleCount = 0;
    logRows.forEach(row => {
      const status = row.getAttribute('data-status');
      if (filter === 'all' || status === filter) {
        row.classList.remove('hidden');
        visibleCount++;
      } else {
        row.classList.add('hidden');
      }
    });

    if (noFilteredLogsRow) {
      if (visibleCount === 0) {
        noFilteredLogsRow.classList.remove('hidden');
      } else {
        noFilteredLogsRow.classList.add('hidden');
      }
    }

    logFilterBtns.forEach(btn => {
      const isSelected = btn.getAttribute('data-filter') === filter;
      if (isSelected) {
        btn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'font-semibold');
        btn.classList.remove('text-slate-600');
      } else {
        btn.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'font-semibold');
        btn.classList.add('text-slate-600');
      }
    });
  }

  logFilterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      applyLogFilter(this.getAttribute('data-filter'));
    });
  });

  // Set default view to 'success'
  applyLogFilter('success');
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
