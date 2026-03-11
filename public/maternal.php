<?php
$pageTitle = 'Maternal Health Module';
require __DIR__ . '/partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Maternal care flow</div>
      <div class="text-2xl font-semibold">Maternal Health Module</div>
      <p class="text-sm text-slate-500 mt-1">Track pregnancies, monitor prenatal care, and document postnatal outcomes.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Continuity</span>
      <span class="app-chip">High Priority</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/pregnancies/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 8a4 4 0 0 1 4 4v4H8v-4a4 4 0 0 1 4-4Z"></path>
          <path d="M6 20h12"></path>
          <path d="M12 2v4"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Maternal</div>
        <div class="text-lg font-semibold">Pregnancies</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Register new pregnancies and track risk notes.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/prenatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 6h16v12H4z"></path>
          <path d="M8 10h8"></path>
          <path d="M8 14h6"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Maternal</div>
        <div class="text-lg font-semibold">Prenatal Visits</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Monitor vitals, labs, and counseling sessions.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/maternal/postnatal/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 3c4.4 0 8 3.6 8 8 0 4-3 7-6.9 7.8L12 22l-1.1-3.2C7 18 4 15 4 11c0-4.4 3.6-8 8-8Z"></path>
          <path d="M9.5 11.5h5"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Maternal</div>
        <div class="text-lg font-semibold">Postnatal Visits</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Track recovery, newborn checks, and follow-ups.</p>
  </a>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
