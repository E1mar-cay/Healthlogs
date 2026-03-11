<?php
$pageTitle = 'Superadmin Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Accounts</div>
    <div class="text-2xl font-semibold mt-1">User Access</div>
    <div class="text-sm text-slate-500">Create and disable accounts</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-indigo-400 w-3/5"></div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">System</div>
    <div class="text-2xl font-semibold mt-1">Operational</div>
    <div class="text-sm text-slate-500">Backups and health checks</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-emerald-400 w-4/5"></div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Audit</div>
    <div class="text-2xl font-semibold mt-1">Review Logs</div>
    <div class="text-sm text-slate-500">Recent updates overview</div>
    <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden">
      <div class="h-2 bg-amber-400 w-2/5"></div>
    </div>
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
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/reminders.php">Review Reminders</a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/inventory.php">Inventory Health</a>
      <a class="block px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700" href="/HealthLogs/public/forecast.php">Forecast Console</a>
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
    <div class="text-sm text-slate-500">Compliance</div>
    <div class="text-lg font-semibold">Security Checklist</div>
    <div class="mt-4 h-40 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-sm">
      Audit & backup status
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
