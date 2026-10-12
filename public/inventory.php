<?php
$pageTitle = 'Medicine Inventory Module';
require __DIR__ . '/partials/header.php';

$inventorySummary = [
    'medicines' => 0,
    'oral' => 0,
    'liquids' => 0,
    'supplies' => 0,
];

$catalogList = [];
$categoryCounts = [
    'all' => 0,
    'oral' => 0,
    'liquids' => 0,
    'supplies' => 0,
];

try {
    $inventorySummary['medicines'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
    $inventorySummary['oral'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE LOWER(formulation) IN ('tablet', 'capsule')")->fetchColumn();
    $inventorySummary['liquids'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE LOWER(formulation) IN ('syrup', 'suspension', 'drops', 'solution')")->fetchColumn();
    $inventorySummary['supplies'] = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE LOWER(formulation) NOT IN ('tablet', 'capsule', 'syrup', 'suspension', 'drops', 'solution')")->fetchColumn();

    // Fetch full medicine catalog with valid on-hand stock indicators
    $catalogStmt = $pdo->query("
        SELECT 
            m.id,
            m.name,
            m.generic_name,
            m.formulation,
            m.strength,
            m.unit,
            COALESCE(m.reorder_level, 100) AS reorder_level,
            COALESCE(stk.valid_stock, 0) AS valid_stock,
            COALESCE(exp_stk.expired_stock, 0) AS expired_stock,
            COALESCE(b_count.batch_count, 0) AS batch_count
        FROM medicines m
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS valid_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date >= CURDATE()
            GROUP BY mb.medicine_id
        ) stk ON stk.medicine_id = m.id
        LEFT JOIN (
            SELECT mb.medicine_id, SUM(mt.quantity) AS expired_stock
            FROM medicine_batches mb
            JOIN medicine_transactions mt ON mt.batch_id = mb.id
            WHERE mb.expiry_date < CURDATE()
            GROUP BY mb.medicine_id
        ) exp_stk ON exp_stk.medicine_id = m.id
        LEFT JOIN (
            SELECT medicine_id, COUNT(*) AS batch_count
            FROM medicine_batches
            GROUP BY medicine_id
        ) b_count ON b_count.medicine_id = m.id
        ORDER BY m.name ASC, m.formulation ASC
    ");
    $rawCatalog = $catalogStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawCatalog as $row) {
        $f = strtolower(trim((string)$row['formulation']));
        if (in_array($f, ['tablet', 'capsule'], true)) {
            $cat = 'oral';
            $catLabel = 'Oral Solid';
        } elseif (in_array($f, ['syrup', 'suspension', 'drops', 'solution'], true)) {
            $cat = 'liquids';
            $catLabel = 'Syrups & Suspensions';
        } else {
            $cat = 'supplies';
            $catLabel = 'Medical Consumables';
        }

        $valid = (int)$row['valid_stock'];
        $reorder = (int)$row['reorder_level'];

        if ($valid <= 0) {
            $statusLabel = 'Out of Stock';
            $statusBadge = 'bg-rose-100 text-rose-800 border border-rose-300';
            $statusIcon = 'fa-circle-xmark text-rose-600';
        } elseif ($valid <= $reorder) {
            $statusLabel = 'Low Stock';
            $statusBadge = 'bg-amber-100 text-amber-800 border border-amber-300';
            $statusIcon = 'fa-triangle-exclamation text-amber-600';
        } else {
            $statusLabel = 'In Stock';
            $statusBadge = 'bg-emerald-100 text-emerald-800 border border-emerald-300';
            $statusIcon = 'fa-circle-check text-emerald-600';
        }

        $row['category_key'] = $cat;
        $row['category_label'] = $catLabel;
        $row['status_label'] = $statusLabel;
        $row['status_badge'] = $statusBadge;
        $row['status_icon'] = $statusIcon;

        $catalogList[] = $row;
        $categoryCounts['all']++;
        $categoryCounts[$cat]++;
    }
} catch (Throwable $e) {
    // Keep page functional
}
?>

<!-- Header Banner matching immunization.php theme -->
<div class="bg-white p-6 rounded shadow">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500">Medicine Masterlist & Profiles</div>
      <div class="text-2xl font-semibold">Medicine Inventory</div>
      <p class="text-sm text-slate-500 mt-1">Configure registered medicine profiles, generic formulations, dosage strengths, packaging units, and reorder levels.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="medicineModalOpenNew" data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-xs font-semibold shadow-xs transition">
          <i class="fas fa-plus"></i>
          <span>New Medicine</span>
        </button>
      <?php endif; ?>
      <a href="/HealthLogs/public/medicine_supplies.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-teal-600 text-white hover:bg-teal-700 text-xs font-semibold shadow-xs transition" title="View Physical Stock on Hand">
        <i class="fas fa-boxes-stacked"></i>
        <span>Medicine Supply (Stock)</span>
      </a>
      <a href="/HealthLogs/public/inventory/audit_trail.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-clipboard-check"></i>
        <span>Audit Trail</span>
      </a>
    </div>
  </div>
</div>

<!-- 4 Navigation Cards matching immunization.php -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/inventory/medicines/index.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M7 3h10v6H7z"></path>
          <path d="M5 9h14v12H5z"></path>
        </svg>
      </span>
      <div>
        <div class="text-sm text-slate-500">Catalog</div>
        <div class="text-lg font-semibold">Medicine Profiles</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Manage registered brand names, generic formulations, and units.</p>
  </a>

  <a class="bg-white p-6 rounded shadow block hover:-translate-y-0.5 transition" href="/HealthLogs/public/medicine_supplies.php">
    <div class="flex items-center gap-3">
      <span class="h-12 w-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
        <i class="fas fa-boxes-stacked text-xl"></i>
      </span>
      <div>
        <div class="text-sm text-slate-500">Physical Stock</div>
        <div class="text-lg font-semibold">Medicine Supply</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Monitor on-hand inventory levels, batches, and remaining stock.</p>
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
        <div class="text-sm text-slate-500">Batches</div>
        <div class="text-lg font-semibold">Batch Tracking</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Monitor batch numbers, expiry dates, and lot quarantine status.</p>
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
        <div class="text-sm text-slate-500">Movements</div>
        <div class="text-lg font-semibold">Transactions</div>
      </div>
    </div>
    <p class="text-sm text-slate-500 mt-4">Inspect stock deliveries, patient dispensations, and adjustments.</p>
  </a>
</div>

<!-- 4 KPI Summary Cards matching immunization.php -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Medicine Profiles</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['medicines'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Total registered profiles</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Oral Solids</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['oral'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Tablets and capsules</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Syrups & Liquids</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['liquids'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Pediatric syrups and drops</div>
  </div>
  <div class="bg-white p-5 rounded shadow">
    <div class="text-xs uppercase tracking-widest text-slate-500">Medical Consumables</div>
    <div class="text-2xl font-semibold mt-2"><?= h(number_format($inventorySummary['supplies'])) ?></div>
    <div class="text-sm text-slate-500 mt-1">Creams, sachets and supplies</div>
  </div>
</div>

<!-- SECTION: Medicine Profiles Catalog Table -->
<div id="catalog-section" class="bg-white p-6 rounded shadow mt-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b">
    <div>
      <div class="text-sm text-slate-500">Medicine catalog definitions</div>
      <div class="text-xl font-semibold text-slate-900">Registered Medicine Profiles</div>
      <p class="text-sm text-slate-500 mt-1">Search, view, and configure medicine specifications, packaging units, and reorder levels.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="app-chip">Oral: <?= $categoryCounts['oral'] ?></span>
      <span class="app-chip">Liquids: <?= $categoryCounts['liquids'] ?></span>
      <span class="app-chip">Supplies: <?= $categoryCounts['supplies'] ?></span>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="mt-4 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
    <!-- Category Tabs -->
    <div class="flex flex-wrap items-center gap-2">
      <button type="button" class="catalog-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-900 text-white transition" data-category="all">
        All Profiles (<?= count($catalogList) ?>)
      </button>
      <button type="button" class="catalog-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="oral">
        Oral Solids (<?= $categoryCounts['oral'] ?>)
      </button>
      <button type="button" class="catalog-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="liquids">
        Syrups & Suspensions (<?= $categoryCounts['liquids'] ?>)
      </button>
      <button type="button" class="catalog-cat-btn px-3 py-1.5 rounded text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" data-category="supplies">
        Medical Consumables (<?= $categoryCounts['supplies'] ?>)
      </button>
    </div>

    <!-- Search Input & Per Page -->
    <div class="flex items-center gap-2">
      <div class="relative w-full sm:w-80">
        <input type="text" id="catalogSearchInput" placeholder="Search medicine, generic, formulation, unit..." class="w-full border border-slate-300 rounded px-3 py-2 text-xs focus:outline-hidden focus:border-slate-500" />
        <button type="button" id="catalogSearchClear" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs">
          <i class="fas fa-times-circle"></i>
        </button>
      </div>
      <select id="catalogPerPage" class="border border-slate-300 rounded px-2 py-1 text-xs bg-white text-slate-700">
        <option value="15" selected>15</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="all">All</option>
      </select>
    </div>
  </div>

  <!-- Catalog Table (Styled like immunization tables) -->
  <div class="mt-4 bg-white rounded border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm" id="catalogTable">
      <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
        <tr>
          <th class="text-left px-4 py-3">Medicine & Strength</th>
          <th class="text-left px-4 py-3">Generic Name</th>
          <th class="text-left px-4 py-3">Formulation & Unit</th>
          <th class="text-center px-4 py-3">Reorder Threshold</th>
          <th class="text-left px-4 py-3">Supply Stock Status</th>
          <th class="text-center px-4 py-3">Batches</th>
          <th class="text-center px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-slate-700" id="catalogTableBody">
        <?php if (empty($catalogList)): ?>
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
              No medicine profiles registered.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($catalogList as $item): ?>
            <?php
              $valid = (int)$item['valid_stock'];
              $reorder = (int)$item['reorder_level'];
            ?>
            <tr class="catalog-row hover:bg-slate-50 transition"
                data-category="<?= h($item['category_key']) ?>"
                data-search="<?= h(strtolower($item['name'] . ' ' . $item['generic_name'] . ' ' . $item['formulation'] . ' ' . $item['strength'] . ' ' . $item['unit'])) ?>">
              
              <!-- Medicine & Strength -->
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900 flex items-center gap-1.5">
                  <span><?= h($item['name']) ?></span>
                  <?php if (!empty($item['strength'])): ?>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200 font-normal">
                      <?= h($item['strength']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Generic Name -->
              <td class="px-4 py-3 text-xs text-slate-600">
                <?= h($item['generic_name'] ?: 'None specified') ?>
              </td>

              <!-- Formulation & Unit -->
              <td class="px-4 py-3 whitespace-nowrap text-xs">
                <div class="font-medium text-slate-800"><?= h($item['formulation'] ?: 'Standard') ?></div>
                <div class="text-slate-500 mt-0.5">Unit: <?= h($item['unit']) ?> (<?= h($item['category_label']) ?>)</div>
              </td>

              <!-- Reorder Threshold -->
              <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-slate-600">
                <?= h(number_format($reorder)) ?> <?= h($item['unit']) ?>
              </td>

              <!-- Stock Health -->
              <td class="px-4 py-3 whitespace-nowrap text-xs">
                <a href="/HealthLogs/public/medicine_supplies.php?q=<?= urlencode($item['name']) ?>" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium <?= $item['status_badge'] ?>" title="View in Medicine Supply">
                  <i class="fas <?= $item['status_icon'] ?>"></i>
                  <span><?= h(number_format($valid)) ?> <?= h($item['unit']) ?> (<?= h($item['status_label']) ?>)</span>
                </a>
              </td>

              <!-- Batches -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <a href="/HealthLogs/public/inventory/batches/index.php?q=<?= urlencode($item['name']) ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition" title="View batches">
                  <i class="fas fa-tag text-[10px] text-slate-400"></i>
                  <span><?= (int)$item['batch_count'] ?></span>
                </a>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-center whitespace-nowrap text-xs">
                <div class="inline-flex items-center gap-2">
                  <?php if (can_manage_clinical_records()): ?>
                    <button type="button" class="medicine-modal-edit text-blue-600 hover:underline" data-embed-url="/HealthLogs/public/inventory/medicines/form_embed.php?id=<?= (int)$item['id'] ?>">
                      Edit
                    </button>
                    <span class="text-slate-300">|</span>
                  <?php endif; ?>
                  <a href="/HealthLogs/public/medicine_supplies.php?q=<?= urlencode($item['name']) ?>" class="text-teal-700 hover:text-teal-900 hover:underline font-medium">
                    Supply Stock
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div id="catalogEmptyState" class="hidden py-8 text-center text-slate-500 text-sm">
      No medicine profiles match your filter or search keyword.
      <div class="mt-2">
        <button type="button" id="catalogResetBtn" class="px-3 py-1 bg-slate-900 text-white rounded text-xs hover:bg-slate-800 transition">
          Reset Filters
        </button>
      </div>
    </div>
  </div>

  <!-- Pagination & Counter -->
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 text-xs text-slate-500">
    <div id="catalogShowingCount">Showing medicines...</div>
    <div class="flex items-center gap-1" id="catalogPagination"></div>
  </div>
</div>

<!-- Embedded Modal for Editing/Adding Medicine Profiles -->
<div id="medicineFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="medicineFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-4xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Medicine profile form</div>
        <button type="button" id="medicineFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="medicineFormModalFrame" class="w-full min-h-[72vh] border-0 flex-1" title="Medicine form"></iframe>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Modal handling for Medicine Form
  var medModal = document.getElementById('medicineFormModal');
  var medFrame = document.getElementById('medicineFormModalFrame');
  var medBackdrop = document.getElementById('medicineFormModalBackdrop');
  var medClose = document.getElementById('medicineFormModalClose');

  function openMedModal(url) {
    if (!medModal || !medFrame || !url) return;
    medFrame.src = url;
    medModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeMedModal() {
    if (!medModal || !medFrame) return;
    medFrame.src = 'about:blank';
    medModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  var newMedBtn = document.getElementById('medicineModalOpenNew');
  if (newMedBtn) {
    newMedBtn.addEventListener('click', function() {
      openMedModal(newMedBtn.getAttribute('data-embed-url') || '');
    });
  }

  document.querySelectorAll('.medicine-modal-edit').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openMedModal(btn.getAttribute('data-embed-url') || '');
    });
  });

  if (medBackdrop) medBackdrop.addEventListener('click', closeMedModal);
  if (medClose) medClose.addEventListener('click', closeMedModal);

  // Table Engine
  const catBtns = document.querySelectorAll('.catalog-cat-btn');
  const searchInput = document.getElementById('catalogSearchInput');
  const searchClear = document.getElementById('catalogSearchClear');
  const perPageSelect = document.getElementById('catalogPerPage');
  const tableRows = Array.from(document.querySelectorAll('.catalog-row'));
  const emptyState = document.getElementById('catalogEmptyState');
  const showingCount = document.getElementById('catalogShowingCount');
  const paginationContainer = document.getElementById('catalogPagination');
  const resetBtn = document.getElementById('catalogResetBtn');

  let currentCategory = 'all';
  let currentSearch = '';
  let currentPage = 1;
  let itemsPerPage = 15;

  function filterAndPaginate() {
    let matchingRows = [];

    tableRows.forEach(row => {
      const cat = row.getAttribute('data-category');
      const searchText = row.getAttribute('data-search') || '';

      const matchCat = (currentCategory === 'all' || cat === currentCategory);
      const matchSearch = (currentSearch === '' || searchText.includes(currentSearch));

      if (matchCat && matchSearch) {
        matchingRows.push(row);
      } else {
        row.style.display = 'none';
      }
    });

    const totalMatches = matchingRows.length;

    if (emptyState) {
      emptyState.classList.toggle('hidden', totalMatches > 0);
    }

    const limit = (itemsPerPage === 'all') ? totalMatches : parseInt(itemsPerPage, 10);
    const totalPages = (limit > 0 && totalMatches > 0) ? Math.ceil(totalMatches / limit) : 1;

    if (currentPage > totalPages) {
      currentPage = 1;
    }

    const startIndex = (limit === totalMatches) ? 0 : (currentPage - 1) * limit;
    const endIndex = (limit === totalMatches) ? totalMatches : Math.min(startIndex + limit, totalMatches);

    matchingRows.forEach((row, idx) => {
      if (idx >= startIndex && idx < endIndex) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });

    if (showingCount) {
      if (totalMatches === 0) {
        showingCount.textContent = 'Showing 0 of 0 medicines';
      } else {
        showingCount.innerHTML = `Showing <strong>${startIndex + 1}</strong> to <strong>${endIndex}</strong> of <strong>${totalMatches}</strong> medicines (from ${tableRows.length} total)`;
      }
    }

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    if (!paginationContainer) return;
    paginationContainer.innerHTML = '';

    if (totalPages <= 1) return;

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = `px-2.5 py-1 rounded text-xs border border-slate-300 ${currentPage === 1 ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-50'}`;
    prevBtn.innerHTML = '<i class="fas fa-chevron-left text-[10px]"></i>';
    prevBtn.disabled = (currentPage === 1);
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        filterAndPaginate();
      }
    });
    paginationContainer.appendChild(prevBtn);

    for (let p = 1; p <= totalPages; p++) {
      if (totalPages > 7) {
        if (p !== 1 && p !== totalPages && Math.abs(p - currentPage) > 2) {
          if (p === 2 || p === totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-1 text-slate-400';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
          }
          continue;
        }
      }

      const pageBtn = document.createElement('button');
      pageBtn.type = 'button';
      pageBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${p === currentPage ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-50'}`;
      pageBtn.textContent = p;
      pageBtn.addEventListener('click', () => {
        currentPage = p;
        filterAndPaginate();
      });
      paginationContainer.appendChild(pageBtn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = `px-2.5 py-1 rounded text-xs border border-slate-300 ${currentPage === totalPages ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-50'}`;
    nextBtn.innerHTML = '<i class="fas fa-chevron-right text-[10px]"></i>';
    nextBtn.disabled = (currentPage === totalPages);
    nextBtn.addEventListener('click', () => {
      if (currentPage < totalPages) {
        currentPage++;
        filterAndPaginate();
      }
    });
    paginationContainer.appendChild(nextBtn);
  }

  catBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      currentCategory = this.getAttribute('data-category');
      currentPage = 1;

      catBtns.forEach(b => {
        b.classList.remove('bg-slate-900', 'text-white');
        b.classList.add('bg-slate-100', 'text-slate-700');
      });
      this.classList.remove('bg-slate-100', 'text-slate-700');
      this.classList.add('bg-slate-900', 'text-white');

      filterAndPaginate();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      currentSearch = this.value.trim().toLowerCase();
      currentPage = 1;
      if (searchClear) {
        searchClear.classList.toggle('hidden', currentSearch === '');
      }
      filterAndPaginate();
    });
  }

  if (searchClear) {
    searchClear.addEventListener('click', function() {
      if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
        this.classList.add('hidden');
        currentPage = 1;
        filterAndPaginate();
      }
    });
  }

  if (perPageSelect) {
    perPageSelect.addEventListener('change', function() {
      itemsPerPage = this.value;
      currentPage = 1;
      filterAndPaginate();
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function() {
      currentCategory = 'all';
      currentSearch = '';
      currentPage = 1;

      if (searchInput) {
        searchInput.value = '';
      }
      if (searchClear) {
        searchClear.classList.add('hidden');
      }

      catBtns.forEach((b, idx) => {
        if (idx === 0) {
          b.classList.add('bg-slate-900', 'text-white');
          b.classList.remove('bg-slate-100', 'text-slate-700');
        } else {
          b.classList.remove('bg-slate-900', 'text-white');
          b.classList.add('bg-slate-100', 'text-slate-700');
        }
      });

      filterAndPaginate();
    });
  }

  filterAndPaginate();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
