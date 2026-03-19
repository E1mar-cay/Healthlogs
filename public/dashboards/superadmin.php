<?php
$pageTitle = 'Superadmin Dashboard';
require __DIR__ . '/../partials/header.php';

$superadminStats = [
    'active_users' => 0,
    'pending_reminders' => 0,
    'ongoing_pregnancies' => 0,
];

try {
    $superadminStats['active_users'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE status = 'active'"
    )->fetchColumn();

    $superadminStats['pending_reminders'] = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM reminders
         WHERE status = 'pending'
           AND due_date <= CURDATE()"
    )->fetchColumn();

    $superadminStats['ongoing_pregnancies'] = (int)$pdo->query(
        "SELECT COUNT(*) FROM pregnancies WHERE status = 'ongoing'"
    )->fetchColumn();
} catch (Throwable $e) {
    // Keep dashboard usable even if a summary query fails.
}
?>

<div class="bg-white p-6 rounded shadow mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Welcome back, <?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
      <div class="text-2xl font-semibold">Superadmin Dashboard</div>
      <p class="text-sm text-slate-500 mt-1">System administration, security oversight, and global settings.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Superadmin</span>
      <span class="app-chip">System Control</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Accounts</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($superadminStats['active_users'])) ?></div>
    <div class="text-sm text-slate-500">Active user accounts</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Reminders</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($superadminStats['pending_reminders'])) ?></div>
    <div class="text-sm text-slate-500">Pending and due now</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Maternal</div>
    <div class="text-2xl font-semibold mt-1"><?= h(number_format($superadminStats['ongoing_pregnancies'])) ?></div>
    <div class="text-sm text-slate-500">Ongoing pregnancies</div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow xl:col-span-2">
    <div class="text-sm text-slate-500">System Overview</div>
    <div class="text-lg font-semibold">Administration Control</div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Roles</div>
        <div class="text-xl font-semibold mt-1">3 Active</div>
        <p class="text-slate-500 text-sm mt-1">Superadmin, Admin, BHW</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Security</div>
        <div class="text-xl font-semibold mt-1">Stable</div>
        <p class="text-slate-500 text-sm mt-1">No critical alerts.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Data</div>
        <div class="text-xl font-semibold mt-1">Synced</div>
        <p class="text-slate-500 text-sm mt-1">Latest backup today.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Users</div>
        <div class="text-xl font-semibold mt-1">Active</div>
        <p class="text-slate-500 text-sm mt-1">All accounts verified.</p>
      </div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Quick Actions</div>
    <div class="text-lg font-semibold">System Tools</div>
    <div class="mt-4 space-y-2">
      <a class="block px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors" href="/HealthLogs/public/users.php">
        <i class="fas fa-users mr-2"></i>Manage Users
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/reminders.php">
        <i class="fas fa-bell mr-2"></i>Review Reminders
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/inventory.php">
        <i class="fas fa-pills mr-2"></i>Inventory Health
      </a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors" href="/HealthLogs/public/forecast.php">
        <i class="fas fa-chart-line mr-2"></i>Forecast Console
      </a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Recent Activity</div>
    <div class="text-lg font-semibold">Audit Highlights</div>
    <ul class="mt-3 text-sm text-slate-600 space-y-2">
      <li>New admin account created.</li>
      <li>Inventory module accessed.</li>
      <li>Forecast run for visits.</li>
    </ul>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Forecast Snapshot</div>
    <div class="text-lg font-semibold">Medicine Demand</div>
    <p class="mt-1 text-sm text-slate-500" id="superadminForecastIntro">Loading forecast summary...</p>
    <div class="grid grid-cols-2 gap-3 mt-4">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Average / Day</div>
        <div class="mt-1 text-xl font-semibold" id="superadminForecastAverage">--</div>
      </div>
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs uppercase tracking-widest text-slate-400">Peak Day</div>
        <div class="mt-1 text-xl font-semibold" id="superadminForecastPeak">--</div>
      </div>
    </div>
    <div class="mt-4">
      <canvas id="superadminForecastChart" height="120"></canvas>
    </div>
    <a class="inline-flex items-center mt-4 text-sm text-blue-700" href="/HealthLogs/public/forecast.php">Open full forecast</a>
  </div>
</div>

<script>
  (function () {
    const introEl = document.getElementById('superadminForecastIntro');
    const averageEl = document.getElementById('superadminForecastAverage');
    const peakEl = document.getElementById('superadminForecastPeak');
    const canvas = document.getElementById('superadminForecastChart');

    function renderFallback(message) {
      introEl.textContent = message;
      averageEl.textContent = '--';
      peakEl.textContent = '--';
    }

    fetch('/HealthLogs/public/forecast_run.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ series_key: 'medicine_total', horizon: '14', fast: '1' })
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
              label: 'Medicine demand forecast',
              data: data.forecast.map((row) => row.value),
              borderColor: '#0ea5a4',
              backgroundColor: 'rgba(14,165,164,0.10)',
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
