<?php
$pageTitle = 'Immunization Module';
require __DIR__ . '/partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Care coordination</div>
      <div class="text-2xl font-semibold">Immunization Module</div>
      <p class="text-sm text-slate-500 mt-1">Manage vaccines, track records, and keep schedules on time.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Child Health</span>
      <span class="app-chip">Community Coverage</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/vaccines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 2h8l1 3H7l1-3Z"></path>
          <path d="M7 5h10v12a5 5 0 0 1-10 0V5Z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Setup</div>
        <div class="text-lg font-semibold">Vaccines</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Maintain vaccine registry and stock details.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/records/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M8 3h8l4 4v14H4V3h4Z"></path>
          <path d="M8 11h8"></path>
          <path d="M8 15h8"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Logs</div>
        <div class="text-lg font-semibold">Immunization Records</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Capture administered doses and patient history.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/immunization/schedules/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M12 7v6l4 2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Scheduling</div>
        <div class="text-lg font-semibold">Immunization Schedules</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Plan dose sequences and outreach follow-ups.</p>
  </a>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
