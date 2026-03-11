<?php
$pageTitle = 'TB Monitoring Module';
require __DIR__ . '/partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Case management</div>
      <div class="text-2xl font-semibold">TB Monitoring Module</div>
      <p class="text-sm text-slate-500 mt-1">Monitor active cases and keep treatment adherence on schedule.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">DOTS</span>
      <span class="app-chip">Active Watch</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/cases/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-cyan-100 text-cyan-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 3h8l4 4v14H4V3h4Z"></path>
          <path d="M8 11h8"></path>
          <path d="M8 15h6"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">TB</div>
        <div class="text-lg font-semibold">TB Cases</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Register new cases, classifications, and notes.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/tb/followups/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M12 7v6l4 2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">TB</div>
        <div class="text-lg font-semibold">Follow-ups</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Track visits, medication intake, and outcomes.</p>
  </a>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
