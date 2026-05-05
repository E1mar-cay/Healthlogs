<?php
$pageTitle = 'Reports';
require __DIR__ . '/partials/bootstrap.php';
require __DIR__ . '/partials/header.php';

$consultationForecast = [];
$admissionForecast = [];
$seasonalDiseaseRows = [];
$seasonalTopMonths = [];
$diseaseTrendMonths = [];
$diseaseTrendSeries = [];

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
?>

<div class="bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">Admin Reports</div>
  <div class="text-2xl font-semibold">Disease and Activity Reports</div>
  <p class="text-sm text-slate-500 mt-1">Disease values come from patient condition records (`patient_conditions`).</p>
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
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
