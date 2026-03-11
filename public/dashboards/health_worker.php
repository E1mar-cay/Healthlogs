<?php
$pageTitle = 'Health Worker Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">BHU Operations</div>
      <div class="text-2xl font-semibold">Health Worker Dashboard</div>
      <p class="text-sm text-slate-500 mt-1">Daily intake, priority programs, and care follow-ups.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Shift Ready</span>
      <span class="app-chip">Field Tasks</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow border border-slate-100">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-semibold">PT</span>
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-400">Today</div>
        <div class="text-2xl font-semibold mt-1">Patient Intake</div>
      </div>
    </div>
    <div class="text-sm text-slate-500 mt-3">Register or update records.</div>
    <a class="inline-flex items-center justify-center mt-4 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/patients/index.php">Open Patients</a>
  </div>
  <div class="bg-white p-5 rounded shadow border border-slate-100">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold">IM</span>
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-400">Programs</div>
        <div class="text-2xl font-semibold mt-1">Immunization</div>
      </div>
    </div>
    <div class="text-sm text-slate-500 mt-3">Log vaccines and schedules.</div>
    <a class="inline-flex items-center justify-center mt-4 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/immunization.php">Open Immunization</a>
  </div>
  <div class="bg-white p-5 rounded shadow border border-slate-100">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">TB</span>
      <div>
        <div class="text-xs uppercase tracking-widest text-slate-400">Monitoring</div>
        <div class="text-2xl font-semibold mt-1">TB Follow-ups</div>
      </div>
    </div>
    <div class="text-sm text-slate-500 mt-3">Track adherence and symptoms.</div>
    <a class="inline-flex items-center justify-center mt-4 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/tb.php">Open TB Module</a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow xl:col-span-2">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-500">Work Queue</div>
        <div class="text-lg font-semibold">Daily Checklist</div>
      </div>
      <span class="text-xs text-slate-400 uppercase tracking-widest">Today</span>
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Immunization</div>
        <div class="text-xl font-semibold mt-1">Schedules Due</div>
        <p class="text-slate-500 text-sm mt-1">Check today’s scheduled vaccines.</p>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Maternal</div>
        <div class="text-xl font-semibold mt-1">Prenatal Visits</div>
        <p class="text-slate-500 text-sm mt-1">Record checkups for mothers.</p>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Inventory</div>
        <div class="text-xl font-semibold mt-1">Dispense</div>
        <p class="text-slate-500 text-sm mt-1">Update dispensed medicines.</p>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">TB</div>
        <div class="text-xl font-semibold mt-1">Follow-ups</div>
        <p class="text-slate-500 text-sm mt-1">Log adherence updates.</p>
      </div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Quick Actions</div>
    <div class="text-lg font-semibold">Shortcuts</div>
    <div class="mt-4 space-y-2">
      <a class="block px-4 py-3 rounded-xl bg-slate-900 text-white shadow" href="/HealthLogs/public/patients/form.php">New Patient</a>
      <a class="block px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition" href="/HealthLogs/public/immunization/records/form.php">Add Vaccine Record</a>
      <a class="block px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition" href="/HealthLogs/public/inventory/transactions/form.php">Dispense Medicine</a>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Reminders</div>
    <div class="text-lg font-semibold">Upcoming Tasks</div>
    <div class="mt-4 space-y-3">
      <div class="flex items-start gap-3">
        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
        <div class="text-sm text-slate-600">Check immunization schedules due today.</div>
      </div>
      <div class="flex items-start gap-3">
        <span class="mt-1 h-2 w-2 rounded-full bg-amber-400"></span>
        <div class="text-sm text-slate-600">Follow-up TB patients scheduled this week.</div>
      </div>
      <div class="flex items-start gap-3">
        <span class="mt-1 h-2 w-2 rounded-full bg-indigo-400"></span>
        <div class="text-sm text-slate-600">Record prenatal visit outcomes.</div>
      </div>
    </div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Help</div>
    <div class="text-lg font-semibold">Quick Guide</div>
    <div class="mt-4 h-40 bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-2xl flex items-center justify-center text-slate-400 text-sm">
      Workflow tips & shortcuts
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
