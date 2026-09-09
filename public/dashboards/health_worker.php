<?php
$pageTitle = 'Health Worker Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="bg-white/90 backdrop-blur-md p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80 mb-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-xs font-semibold uppercase tracking-wider text-teal-700 flex items-center gap-1.5 mb-1">
        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Primary Care Clinical Station
      </div>
      <div class="text-2xl sm:text-3xl font-bold text-slate-900 brand-font">Health Worker Dashboard</div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        Welcome back, <strong class="text-slate-800"><?= h($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong>. Daily patient intake, health programs, and care follow-ups.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
        <i class="fas fa-check-circle text-emerald-600"></i> Station Active
      </span>
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-800 text-xs font-semibold">
        <i class="fas fa-clipboard-user text-teal-600"></i> Field Intake Ready
      </span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
  <!-- Patient Intake Card -->
  <div class="bg-white/95 backdrop-blur-xs p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3.5 mb-3">
        <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100 text-sky-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
          <i class="fas fa-user-plus"></i>
        </div>
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Intake & Profiles</div>
          <div class="text-xl font-bold text-slate-900 brand-font">Patient Records</div>
        </div>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">Search profiles, register new household members, or update contact and medical info.</p>
    </div>
    <a class="w-full inline-flex items-center justify-center gap-2 mt-5 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition" href="/HealthLogs/public/patients/index.php">
      <span>Open Patients</span>
      <i class="fas fa-arrow-right text-xs"></i>
    </a>
  </div>

  <!-- Immunization Card -->
  <div class="bg-white/95 backdrop-blur-xs p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3.5 mb-3">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-100 text-teal-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
          <i class="fas fa-syringe"></i>
        </div>
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Child & Infant Care</div>
          <div class="text-xl font-bold text-slate-900 brand-font">Immunization</div>
        </div>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">Log administered vaccines (BCG, Pentavalent, OPV, IPV, Measles) and schedule dose follow-ups.</p>
    </div>
    <a class="w-full inline-flex items-center justify-center gap-2 mt-5 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition" href="/HealthLogs/public/immunization.php">
      <span>Open Immunization</span>
      <i class="fas fa-arrow-right text-xs"></i>
    </a>
  </div>

  <!-- Medicine Inventory Card -->
  <div class="bg-white/95 backdrop-blur-xs p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between">
    <div>
      <div class="flex items-center gap-3.5 mb-3">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100 text-amber-700 flex items-center justify-center text-xl shrink-0 shadow-xs">
          <i class="fas fa-pills"></i>
        </div>
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Pharmacy & Stocks</div>
          <div class="text-xl font-bold text-slate-900 brand-font">Medicine Inventory</div>
        </div>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">Dispense prescribed medications, record restocks, and monitor clinic inventory levels.</p>
    </div>
    <a class="w-full inline-flex items-center justify-center gap-2 mt-5 px-4 py-2.5 rounded-xl bg-amber-700 hover:bg-amber-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition" href="/HealthLogs/public/inventory.php">
      <span>Open Inventory</span>
      <i class="fas fa-arrow-right text-xs"></i>
    </a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
  <!-- Work Queue -->
  <div class="bg-white/95 backdrop-blur-xs p-6 rounded-2xl border border-slate-200/80 shadow-xs xl:col-span-2">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
      <div>
        <div class="text-xs uppercase tracking-wider font-bold text-teal-700">Clinical Focus</div>
        <div class="text-lg font-bold text-slate-900 brand-font">Daily Program Checklist</div>
      </div>
      <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium"><i class="far fa-clock mr-1"></i>Today's Routine</span>
    </div>
    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3.5">
      <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:bg-white transition-colors">
        <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs mb-2">
          <i class="fas fa-syringe"></i>
        </div>
        <div class="text-sm font-bold text-slate-800">Vaccines Due</div>
        <p class="text-slate-500 text-xs mt-1">Check today’s scheduled doses & notify parents.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:bg-white transition-colors">
        <div class="w-7 h-7 rounded-lg bg-pink-100 text-pink-700 flex items-center justify-center text-xs mb-2">
          <i class="fas fa-heart-pulse"></i>
        </div>
        <div class="text-sm font-bold text-slate-800">Prenatal Checkups</div>
        <p class="text-slate-500 text-xs mt-1">Review 24–31 week maternal patients and schedule visits.</p>
      </div>
      <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 hover:bg-white transition-colors">
        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs mb-2">
          <i class="fas fa-capsules"></i>
        </div>
        <div class="text-sm font-bold text-slate-800">Dispense Logs</div>
        <p class="text-slate-500 text-xs mt-1">Record dispensed medicines for daily clinic logs.</p>
      </div>
    </div>
  </div>

  <!-- Quick Shortcuts -->
  <div class="bg-white/95 backdrop-blur-xs p-6 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="text-xs uppercase tracking-wider font-bold text-teal-700">Fast Actions</div>
    <div class="text-lg font-bold text-slate-900 brand-font mb-4">Common Tasks</div>
    <div class="space-y-2.5">
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs sm:text-sm shadow-xs transition" href="/HealthLogs/public/patients/form.php">
        <span class="flex items-center gap-2"><i class="fas fa-user-plus text-xs"></i> New Patient Profile</span>
        <i class="fas fa-chevron-right text-xs text-slate-400"></i>
      </a>
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold text-xs sm:text-sm shadow-2xs transition" href="/HealthLogs/public/immunization/records/form.php">
        <span class="flex items-center gap-2"><i class="fas fa-plus-circle text-teal-600 text-xs"></i> Add Vaccine Record</span>
        <i class="fas fa-chevron-right text-xs text-slate-400"></i>
      </a>
      <a class="flex items-center justify-between px-4 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold text-xs sm:text-sm shadow-2xs transition" href="/HealthLogs/public/inventory/transactions/form.php">
        <span class="flex items-center gap-2"><i class="fas fa-hand-holding-medical text-blue-600 text-xs"></i> Dispense Medicine</span>
        <i class="fas fa-chevron-right text-xs text-slate-400"></i>
      </a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

