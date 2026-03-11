<?php
$pageTitle = 'Administrator Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Patients</div>
    <div class="text-2xl font-semibold mt-1">1,250</div>
    <div class="text-sm text-slate-500">Total registered</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-emerald-400 w-2/3"></div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Visits</div>
    <div class="text-2xl font-semibold mt-1">320</div>
    <div class="text-sm text-slate-500">This month</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-blue-500 w-1/2"></div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Low Stock</div>
    <div class="text-2xl font-semibold mt-1">12</div>
    <div class="text-sm text-slate-500">Items below reorder</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-amber-400 w-1/3"></div>
    </div>
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
        <div class="text-xs uppercase tracking-widest text-slate-400">TB Monitoring</div>
        <div class="text-xl font-semibold mt-1">Needs Review</div>
        <p class="text-slate-500 text-sm mt-1">Follow-ups missed in 7 days.</p>
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
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/inventory.php">Review Inventory</a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/reminders.php">Review Reminders</a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/forecast.php">Run Forecast</a>
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
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
      <div class="text-xs uppercase tracking-widest text-slate-400">Monitoring</div>
      <div class="text-xl font-semibold mt-1">TB Follow-ups</div>
      <p class="text-slate-500 text-sm mt-1">Review adherence and open follow-up tasks.</p>
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
      <li>2 TB cases missed recent follow-up.</li>
    </ul>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Forecast Snapshot</div>
    <div class="text-lg font-semibold">Next 30 Days</div>
    <div class="mt-4 h-40 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-sm">
      Chart placeholder (ARIMA)
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
