<?php
$pageTitle = 'Medicine Inventory Module';
require __DIR__ . '/partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Supply visibility</div>
      <div class="text-2xl font-semibold">Medicine Inventory Module</div>
      <p class="text-sm text-slate-500 mt-1">Monitor medicines, batches, and stock movement in one place.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="app-chip">Stock Health</span>
      <span class="app-chip">Batch Tracking</span>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/medicines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M7 3h10v6H7z"></path>
          <path d="M5 9h14v12H5z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Medicines</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Define medicine profiles and base stock info.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/batches/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-lime-100 text-lime-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="4" y="4" width="16" height="16" rx="2"></rect>
          <path d="M4 9h16"></path>
          <path d="M9 4v16"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Batches</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Track batch numbers, expiry, and availability.</p>
  </a>
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/transactions/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 6h16"></path>
          <path d="M7 10h10"></path>
          <path d="M9 14h6"></path>
          <path d="M11 18h2"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Inventory</div>
        <div class="text-lg font-semibold">Transactions & Stock</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Log incoming/outgoing movements and balances.</p>
  </a>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
