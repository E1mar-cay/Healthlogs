<?php
$pageTitle = 'Administrator Dashboard';
require __DIR__ . '/../partials/header.php';

$adminStats = [
    'total_patients' => 0,
    'monthly_visits' => 0,
    'low_stock_items' => 0,
];

try {
    $adminStats['total_patients'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM patients WHERE status = 'active'"
    )->fetchColumn();

    $adminStats['monthly_visits'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM visits
         WHERE MONTH(visit_datetime) = MONTH(CURDATE())
           AND YEAR(visit_datetime) = YEAR(CURDATE())"
    )->fetchColumn();

    $adminStats['low_stock_items'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM (
             SELECT m.id
             FROM medicines m
             LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id
             GROUP BY m.id, m.reorder_level
             HAVING COALESCE(SUM(mt.quantity), 0) <= COALESCE(m.reorder_level, 0)
         ) low_stock"
    )->fetchColumn();
} catch (Throwable $e) {
    // Keep dashboard usable even if a summary query fails.
}
?>

<div class="bg-white p-6 rounded shadow mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Welcome back, <?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
      <div class="text-2xl font-semibold">Administrator Dashboard</div>
      <p class="text-sm text-slate-500 mt-1">System overview, user management, and program analytics.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Admin Access</span>
      <span class="app-chip">Full Control</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Patients</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($adminStats['total_patients'])) ?></div>
    <div class="text-sm text-slate-500">Total registered</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Visits</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($adminStats['monthly_visits'])) ?></div>
    <div class="text-sm text-slate-500">This month</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Low Stock</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($adminStats['low_stock_items'])) ?></div>
    <div class="text-sm text-slate-500">Items below reorder</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow xl:col-span-2">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Program Performance</div>
        <div class="text-lg font-semibold">Visits & Outreach</div>
      </div>
      <a class="text-blue-700 text-sm" href="/HealthLogs/public/forecast.php">View Forecast</a>
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Immunization</div>
        <div class="text-xl font-semibold mt-1">On Track</div>
        <p class="text-slate-500 text-sm mt-1">Coverage above last quarter.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Maternal Health</div>
        <div class="text-xl font-semibold mt-1">Stable</div>
        <p class="text-slate-500 text-sm mt-1">Prenatal visits consistent.</p>
      </div>

      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Medicine Demand</div>
        <div class="text-xl font-semibold mt-1">Rising</div>
        <p class="text-slate-500 text-sm mt-1">Expect higher demand next month.</p>
      </div>
    </div>
  </div>

  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Quick Actions</div>
    <div class="text-lg font-semibold">Administrator Tools</div>
    <div class="mt-4 space-y-2">
      <a class="block px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors" href="/HealthLogs/public/users.php">
        <i class="fas fa-users mr-2"></i>Manage Users
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/inventory.php">
        <i class="fas fa-pills mr-2"></i>Review Inventory
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/reminders.php">
        <i class="fas fa-bell mr-2"></i>Review Reminders
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/forecast.php">
        <i class="fas fa-chart-line mr-2"></i>Run Forecast
      </a>
    </div>
  </div>
</div>

<div class="bg-white p-5 rounded shadow mt-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Reports</div>
      <div class="text-lg font-semibold">Administrative Summary</div>
      <p class="text-sm text-slate-500 mt-1">Quick printable overview of key program highlights.</p>
    </div>
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg shadow" onclick="window.print()">Print Report</button>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Coverage</div>
      <div class="text-xl font-semibold mt-1">Immunization</div>
      <p class="text-slate-500 text-sm mt-1">Monitor completion rates and missed schedules.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Care</div>
      <div class="text-xl font-semibold mt-1">Maternal Health</div>
      <p class="text-slate-500 text-sm mt-1">Track prenatal and postnatal visit consistency.</p>
    </div>

  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Inventory</div>
      <div class="text-xl font-semibold mt-1">Stock Health</div>
      <p class="text-slate-500 text-sm mt-1">Identify low-stock and expiring medicines.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Outreach</div>
      <div class="text-xl font-semibold mt-1">Reminders</div>
      <p class="text-slate-500 text-sm mt-1">Upcoming tasks and overdue follow-ups.</p>
    </div>
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Forecast</div>
      <div class="text-xl font-semibold mt-1">Next 30 Days</div>
      <p class="text-slate-500 text-sm mt-1">Projected demand for planning resources.</p>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Alerts</div>
    <div class="text-lg font-semibold">Needs Attention</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-2">
      <li>3 vaccines expiring within 60 days.</li>
      <li>5 patients overdue for immunization follow-up.</li>

    </ul>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Forecast Snapshot</div>
    <div class="text-lg font-semibold">Patient Visits</div>
    <p class="mt-1 text-sm text-slate-500" id="adminForecastIntro">Loading forecast summary...</p>
    <div class="grid grid-cols-2 gap-3 mt-4">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
        <div class="mt-1 text-xl font-semibold" id="adminForecastAverage">--</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
        <div class="mt-1 text-xl font-semibold" id="adminForecastPeak">--</div>
      </div>
    </div>
    <div class="mt-4">
      <canvas id="adminForecastChart" height="120"></canvas>
    </div>
    <a class="inline-flex items-center mt-4 text-sm text-blue-700" href="/HealthLogs/public/forecast.php">Open full forecast</a>
  </div>
</div>

<script>
  (function () {
    const introEl = document.getElementById('adminForecastIntro');
    const averageEl = document.getElementById('adminForecastAverage');
    const peakEl = document.getElementById('adminForecastPeak');
    const canvas = document.getElementById('adminForecastChart');

    function renderFallback(message) {
      introEl.textContent = message;
      averageEl.textContent = '--';
      peakEl.textContent = '--';
    }

    fetch('/HealthLogs/public/forecast_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ series_key: 'visits_total', horizon: '14', fast: '1' })
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error || !data.summary || !data.forecast) {
          throw new Error(data.error || 'Forecast unavailable');
        }

        introEl.textContent = data.summary.intro;
        averageEl.textContent = Number(data.summary.forecast_average).toFixed(1);
        peakEl.textContent = data.summary.peak_date;

        new Chart(canvas, {
          type: 'line',
          data: {
            labels: data.forecast.map((row) => row.date),
            datasets: [{
              label: 'Visits forecast',
              data: data.forecast.map((row) => row.value),
              borderColor: '#0f172a',
              backgroundColor: 'rgba(15,23,42,0.08)',
              fill: true,
              tension: 0.3,
              pointRadius: 1.5,
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
              x: { ticks: { maxTicksLimit: 6 } },
              y: { beginAtZero: true }
            }
          }
        });
      })
      .catch(() => {
        renderFallback('Forecast is not ready yet. Open the full forecast page to review details.');
      });
  }());
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
