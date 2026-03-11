<?php
$pageTitle = 'Health Worker Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Today</div>
    <div class="text-2xl font-semibold mt-1">Patient Intake</div>
    <div class="text-sm text-slate-500">Register or update records</div>
    <a class="inline-flex items-center justify-center mt-3 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/patients/index.php">Open Patients</a>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Programs</div>
    <div class="text-2xl font-semibold mt-1">Immunization</div>
    <div class="text-sm text-slate-500">Log vaccines & schedules</div>
    <a class="inline-flex items-center justify-center mt-3 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/immunization.php">Open Immunization</a>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-400">Monitoring</div>
    <div class="text-2xl font-semibold mt-1">TB Follow-ups</div>
    <div class="text-sm text-slate-500">Track adherence & symptoms</div>
    <a class="inline-flex items-center justify-center mt-3 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm shadow" href="/HealthLogs/public/tb.php">Open TB Module</a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
  <div class="bg-white p-5 rounded shadow xl:col-span-2">
    <div class="text-sm text-slate-500">Work Queue</div>
    <div class="text-lg font-semibold">Daily Checklist</div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Immunization</div>
        <div class="text-xl font-semibold mt-1">Schedules Due</div>
        <p class="text-slate-500 text-sm mt-1">Check today’s scheduled vaccines.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Maternal</div>
        <div class="text-xl font-semibold mt-1">Prenatal Visits</div>
        <p class="text-slate-500 text-sm mt-1">Record checkups for mothers.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="text-xs uppercase tracking-widest text-slate-400">Inventory</div>
        <div class="text-xl font-semibold mt-1">Dispense</div>
        <p class="text-slate-500 text-sm mt-1">Update dispensed medicines.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
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
    <ul class="mt-3 text-sm text-slate-600 space-y-2">
      <li>Check immunization schedules due today.</li>
      <li>Follow-up TB patients scheduled this week.</li>
      <li>Record prenatal visit outcomes.</li>
    </ul>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-sm text-slate-500">Help</div>
    <div class="text-lg font-semibold">Quick Guide</div>
    <div class="mt-4 h-40 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 text-sm">
      Workflow tips & shortcuts
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
