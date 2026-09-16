<?php
$pageTitle = 'Target Client List for Family Planning (TCL-FP)';
require __DIR__ . '/../partials/bootstrap.php';

// Filters
$export = ($_GET['export'] ?? '') === 'csv';
$q = trim($_GET['q'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$methodFilter = trim($_GET['method'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$yearFilter = trim($_GET['year'] ?? date('Y'));

// Standard Puroks for Barangay Tangcul
$puroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7'];

// Query FP Records joined with patient and their visits
$whereClauses = ["1=1"];
$params = [];

if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
    $whereClauses[] = "YEAR(r.registration_date) = ?";
    $params[] = (int)$yearFilter;
}

if ($barangayFilter !== '') {
    $whereClauses[] = "p.barangay = ?";
    $params[] = $barangayFilter;
}

if ($methodFilter !== '') {
    $whereClauses[] = "r.method_accepted = ?";
    $params[] = $methodFilter;
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "r.status = ?";
    $params[] = $statusFilter;
}

if ($q !== '') {
    $whereClauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.middle_name LIKE ? OR r.client_code LIKE ? OR r.partner_name LIKE ?)";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "
    SELECT r.*, p.first_name, p.last_name, p.middle_name, p.birth_date, p.barangay, p.address_line, p.contact_no, p.sex,
           TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) AS age
    FROM fp_records r
    JOIN patients p ON p.id = r.patient_id
    WHERE {$whereSql}
    ORDER BY r.registration_date DESC, r.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all visits for these records to display service timeline
$recordIds = array_column($records, 'id');
$visitsByRecord = [];

if (!empty($recordIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($recordIds), '?'));
    $vSql = "SELECT * FROM fp_visits WHERE fp_record_id IN ($inPlaceholders) ORDER BY visit_date ASC";
    $vStmt = $pdo->prepare($vSql);
    $vStmt->execute($recordIds);
    while ($row = $vStmt->fetch(PDO::FETCH_ASSOC)) {
        $visitsByRecord[$row['fp_record_id']][] = $row;
    }
}

// Method names map
function tcl_method_name(?string $m): string {
    $labels = [
        'pills_coc' => 'Pills (COC)',
        'pills_pop' => 'Pills (POP)',
        'injectable_dmpa' => 'DMPA Injectable',
        'implant' => 'Implant',
        'iud_interval' => 'IUD (Interval)',
        'iud_postpartum' => 'IUD (Postpartum)',
        'condom' => 'Condom',
        'btl' => 'BTL',
        'nsv' => 'NSV',
        'natural_lam' => 'LAM',
        'natural_sdm' => 'SDM',
        'natural_stm' => 'STM',
    ];
    return $labels[$m] ?? ucwords(str_replace('_', ' ', (string)$m));
}

// Handle CSV Export
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=TCL_Family_Planning_Tangcul_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['PHILIPPINE DEPARTMENT OF HEALTH - TARGET CLIENT LIST FOR FAMILY PLANNING (TCL-FP)']);
    fputcsv($output, ['Barangay Tangcul, City of Ilagan, Isabela', 'Purok: ' . ($barangayFilter ?: 'All Puroks'), 'Year: ' . $yearFilter, 'Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    fputcsv($output, [
        'Client Code',
        'Registration Date',
        'Client Name',
        'Age',
        'Purok',
        'Client Type',
        'Method Accepted',
        'Previous Method',
        'Source',
        'Partner Name',
        'Living Children',
        'Plan More Children',
        'Status',
        'Drop Out Date',
        'Drop Out Reason',
        'Total Visits Logged',
        'Last Visit Date',
        'Next Appointment Due',
        'Remarks'
    ]);

    foreach ($records as $r) {
        $recVisits = $visitsByRecord[$r['id']] ?? [];
        $lastVisit = !empty($recVisits) ? end($recVisits) : null;
        fputcsv($output, [
            $r['client_code'],
            $r['registration_date'],
            $r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . $r['middle_name'] : ''),
            $r['age'],
            $r['barangay'] ?: 'Barangay Tangcul',
            strtoupper(str_replace('_', ' ', $r['client_type'])),
            tcl_method_name($r['method_accepted']),
            $r['previous_method'] ?: 'None',
            ucfirst($r['source']),
            $r['partner_name'] ?: 'N/A',
            $r['num_living_children'],
            ucfirst($r['plan_more_children']),
            ucfirst($r['status']),
            $r['drop_out_date'] ?: '',
            $r['drop_out_reason'] ? ucwords(str_replace('_', ' ', $r['drop_out_reason'])) : '',
            count($recVisits),
            $lastVisit ? $lastVisit['visit_date'] : '',
            $lastVisit ? $lastVisit['next_appointment_date'] : '',
            $r['remarks'] ?: ''
        ]);
    }

    fclose($output);
    exit;
}

require __DIR__ . '/../partials/header.php';
?>

<style>
  @media print {
    @page {
      size: A4 landscape;
      margin: 4mm;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 7px !important;
    }
    .print\:hidden, #appSidebar, .app-topbar, header, nav {
      display: none !important;
    }
    .app-main, .app-content {
      padding: 0 !important;
      margin: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      overflow: visible !important;
    }
    .overflow-x-auto {
      overflow: visible !important;
    }
    .tcl-table {
      width: 100% !important;
      min-width: 0 !important;
      max-width: 100% !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
      font-size: 6.5px !important;
    }
    .tcl-table th, .tcl-table td {
      border: 1px solid #000 !important;
      padding: 1px 1.5px !important;
      color: #000 !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      line-height: 1.05 !important;
    }
    .screen-tcl-table {
      display: none !important;
    }
    .print-tcl-table {
      display: table !important;
      font-size: 9px !important;
    }
    .print-tcl-header {
      display: block !important;
    }
    .print-tcl-table th,
    .print-tcl-table td {
      padding: 2px 3px !important;
      line-height: 1.2 !important;
    }
  }

  .print-tcl-table {
    display: none;
  }

  .print-tcl-header {
    display: none;
  }
</style>

<div class="bg-white p-4 sm:p-6 rounded-xl shadow mb-6 print:hidden">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <div class="text-sm text-slate-500 font-semibold">
        <a href="/HealthLogs/public/family_planning.php" class="text-slate-500 hover:text-slate-800 hover:underline">&larr; Back to FP Dashboard</a>
      </div>
      <div class="text-2xl font-bold text-slate-900 mt-1">Target Client List for Family Planning (TCL-FP)</div>
      <p class="text-sm text-slate-500 mt-1">DOH Official Clinical Form 1 &bull; Monitoring of Acceptors, Contraceptive Services, and Drop-outs for Barangay Tangcul.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <!-- Export CSV link -->
      <?php
        $exportQuery = $_GET;
        $exportQuery['export'] = 'csv';
      ?>
      <a href="?<?= http_build_query($exportQuery) ?>" class="inline-flex items-center px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition">
        <i class="fas fa-file-csv mr-1.5 text-slate-600"></i> Export to CSV
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow transition">
        <i class="fas fa-print mr-1.5"></i> Print TCL Register (Landscape)
      </button>
      <button type="button" onclick="openEnrollModal()" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition">
        <i class="fas fa-user-plus mr-1.5 text-xs"></i> Enroll New Client
      </button>
    </div>
  </div>
</div>

<?php display_flash_messages(); ?>

<!-- Filters Bar -->
<div class="bg-white rounded-xl shadow p-4 sm:p-5 mb-6 print:hidden">
  <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
    <div class="lg:col-span-2">
      <label class="block text-xs font-semibold text-slate-600 mb-1">Search Client / Partner</label>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name, code, partner..." class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-slate-400" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Purok (Brgy. Tangcul)</label>
      <select name="barangay" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Puroks</option>
        <?php foreach ($puroks as $p): ?>
          <option value="<?= h($p) ?>" <?= $barangayFilter === $p ? 'selected' : '' ?>><?= h($p) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Method</label>
      <select name="method" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Methods</option>
        <option value="pills_coc" <?= $methodFilter === 'pills_coc' ? 'selected' : '' ?>>Pills (COC)</option>
        <option value="pills_pop" <?= $methodFilter === 'pills_pop' ? 'selected' : '' ?>>Pills (POP)</option>
        <option value="injectable_dmpa" <?= $methodFilter === 'injectable_dmpa' ? 'selected' : '' ?>>DMPA Injectable</option>
        <option value="implant" <?= $methodFilter === 'implant' ? 'selected' : '' ?>>Subdermal Implant</option>
        <option value="iud_interval" <?= $methodFilter === 'iud_interval' ? 'selected' : '' ?>>IUD (Interval)</option>
        <option value="iud_postpartum" <?= $methodFilter === 'iud_postpartum' ? 'selected' : '' ?>>IUD (Postpartum)</option>
        <option value="condom" <?= $methodFilter === 'condom' ? 'selected' : '' ?>>Condom</option>
        <option value="btl" <?= $methodFilter === 'btl' ? 'selected' : '' ?>>BTL</option>
        <option value="natural_lam" <?= $methodFilter === 'natural_lam' ? 'selected' : '' ?>>Natural (LAM)</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Year Enrolled</label>
      <select name="year" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All Years</option>
        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 4; $y--): ?>
          <option value="<?= $y ?>" <?= (string)$yearFilter === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="flex items-end gap-2">
      <button type="submit" class="w-full bg-slate-900 text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
        Filter
      </button>
      <?php if ($q !== '' || $barangayFilter !== '' || $methodFilter !== '' || $yearFilter !== date('Y') || $statusFilter !== 'all'): ?>
        <a href="?" class="px-3 py-2 border rounded-lg text-xs text-slate-600 hover:bg-slate-50 transition">Clear</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TCL Master Table -->
<div class="bg-white rounded-xl shadow overflow-hidden">
  <div class="print-tcl-header text-center pb-3 mb-3 border-b border-slate-300">
    <div class="flex items-center justify-center gap-3 mb-2">
      <img src="/HealthLogs/public/assets/images/logo.jpeg" alt="HealthLogs Logo" class="w-12 h-12 rounded-full object-cover border border-slate-300 shadow-2xs">
      <div class="text-left">
        <div class="text-[11px] uppercase tracking-widest text-slate-600 font-semibold">Republic of the Philippines &bull; Province of Isabela &bull; City of Ilagan</div>
        <div class="text-xs font-bold text-slate-800">Barangay Tangcul Health Station & Care Hub &bull; HealthLogs</div>
      </div>
    </div>
    <h1 class="text-lg font-extrabold uppercase text-slate-900 tracking-wider mt-1">TARGET CLIENT LIST FOR FAMILY PLANNING (TCL-FP)</h1>
    <div class="text-xs text-slate-600 mt-1">
      Barangay: <strong>Barangay Tangcul, City of Ilagan</strong>
      <span class="mx-2">&bull;</span>
      Purok: <strong><?= h($barangayFilter ?: 'All Puroks') ?></strong>
      <span class="mx-2">&bull;</span>
      Year Enrolled: <strong><?= h($yearFilter === 'all' ? 'All Records' : $yearFilter) ?></strong>
      <span class="mx-2">&bull;</span>
      Date Generated: <strong><?= date('F d, Y') ?></strong>
    </div>
  </div>
  <div class="p-4 border-b border-slate-100 flex items-center justify-between print:hidden">
    <div class="text-sm text-slate-600 font-semibold">
      Showing <strong><?= count($records) ?></strong> registered client row(s)
    </div>
    <span class="text-xs text-slate-400 font-mono">DOH Form 1 Format</span>
  </div>

  <?php if (empty($records)): ?>
    <div class="text-center py-12 bg-slate-50">
      <i class="fas fa-table text-3xl text-slate-400 mb-2"></i>
      <p class="text-sm font-semibold text-slate-700">No Target Client List records found for the selected criteria.</p>
      <p class="text-xs text-slate-500 mt-1">Adjust filters or enroll clients to populate the TCL sheet.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full tcl-table screen-tcl-table text-left text-xs min-w-[1200px] border-collapse">
        <thead>
          <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 text-[11px] uppercase tracking-wider">
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Date Reg</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Client Code</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200 min-w-[180px]">Client Name &amp; Age</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Purok</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Type</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Method Accepted</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Partner Info</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200 text-center">Living Kids</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200 min-w-[220px]">Visit Timeline &amp; Dispensed</th>
            <th class="py-3 px-3 font-semibold border-r border-slate-200">Next Service Due</th>
            <th class="py-3 px-3 font-semibold">Status / Drop-out</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-slate-700">
          <?php foreach ($records as $r): 
            $recVisits = $visitsByRecord[$r['id']] ?? [];
            $lastVisit = !empty($recVisits) ? end($recVisits) : null;
            $nextDue = $lastVisit['next_appointment_date'] ?? null;
          ?>
            <tr class="hover:bg-slate-50 transition">
              <!-- Date Registered -->
              <td class="py-2.5 px-3 whitespace-nowrap font-mono text-slate-600 border-r border-slate-200">
                <?= h($r['registration_date']) ?>
              </td>

              <!-- Client Code -->
              <td class="py-2.5 px-3 whitespace-nowrap font-mono font-semibold text-slate-800 border-r border-slate-200">
                <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $r['id'] ?>" class="hover:underline hover:text-slate-950">
                  <?= h($r['client_code']) ?>
                </a>
              </td>

              <!-- Name & Age -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200">
                <div class="font-bold text-slate-900">
                  <?= h($r['last_name'] . ', ' . $r['first_name']) ?>
                  <?php if (!empty($r['middle_name'])): ?><?= h(' ' . substr($r['middle_name'], 0, 1) . '.') ?><?php endif; ?>
                </div>
                <div class="text-[10px] text-slate-400">
                  <?= (int)$r['age'] ?> yo &bull; <?= h($r['contact_no'] ?: 'No contact') ?>
                </div>
              </td>

              <!-- Purok -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 text-slate-600">
                <?= h($r['barangay'] ?: '—') ?>
              </td>

              <!-- Client Type -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 uppercase font-semibold text-[10px]">
                <?php
                  $typeBadges = [
                      'new_acceptor' => 'text-emerald-700 bg-emerald-50 border border-emerald-200/60',
                      'current_user' => 'text-blue-700 bg-blue-50 border border-blue-200/60',
                      'restart' => 'text-amber-700 bg-amber-50 border border-amber-200/60',
                      'changing_method' => 'text-purple-700 bg-purple-50 border border-purple-200/60',
                      'changing_clinic' => 'text-indigo-700 bg-indigo-50 border border-indigo-200/60',
                  ];
                  $badge = $typeBadges[$r['client_type']] ?? 'text-slate-700 bg-slate-50 border border-slate-200';
                ?>
                <span class="px-2 py-0.5 rounded <?= $badge ?>">
                  <?= h(str_replace('_', ' ', $r['client_type'])) ?>
                </span>
              </td>

              <!-- Method Accepted -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 font-semibold text-slate-900">
                <?= tcl_method_name($r['method_accepted']) ?>
                <?php if (!empty($r['previous_method'])): ?>
                  <div class="text-[10px] text-slate-400 font-normal">Prev: <?= h($r['previous_method']) ?></div>
                <?php endif; ?>
              </td>

              <!-- Partner Info -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 text-slate-600">
                <div><?= h($r['partner_name'] ?: '—') ?></div>
                <div class="text-[10px] text-slate-400"><?= h($r['partner_occupation'] ?: '') ?></div>
              </td>

              <!-- Living Kids -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 text-center font-bold">
                <?= (int)$r['num_living_children'] ?>
                <div class="text-[10px] text-slate-400 font-normal capitalize"><?= h($r['plan_more_children']) ?></div>
              </td>

              <!-- Visit Timeline -->
              <td class="py-2.5 px-3 border-r border-slate-200">
                <?php if (empty($recVisits)): ?>
                  <span class="text-slate-400 italic">No visits logged</span>
                <?php else: ?>
                  <div class="space-y-1">
                    <?php foreach (array_slice($recVisits, -2) as $v): ?>
                      <div class="text-[11px] flex items-center gap-1.5 text-slate-700">
                        <span class="font-mono font-semibold"><?= date('M d', strtotime($v['visit_date'])) ?>:</span>
                        <span><?= h($v['method_prescribed']) ?> (x<?= (int)$v['quantity_dispensed'] ?>)</span>
                      </div>
                    <?php endforeach; ?>
                    <?php if (count($recVisits) > 2): ?>
                      <a href="/HealthLogs/public/family_planning/visits/index.php?record_id=<?= $r['id'] ?>" class="text-[10px] text-slate-600 hover:text-slate-900 hover:underline">
                        +<?= count($recVisits) - 2 ?> earlier visit(s)
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Next Service Due -->
              <td class="py-2.5 px-3 whitespace-nowrap border-r border-slate-200 font-mono">
                <?php if (!empty($nextDue)): ?>
                  <?php $isOverdue = ($nextDue < date('Y-m-d')); ?>
                  <span class="<?= $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-800' ?>">
                    <?= date('M d, Y', strtotime($nextDue)) ?>
                  </span>
                  <?php if ($isOverdue): ?>
                    <div class="text-[9px] text-rose-600 font-bold uppercase">Overdue</div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>

              <!-- Status / Drop-out -->
              <td class="py-2.5 px-3 whitespace-nowrap">
                <?php if ($r['status'] === 'active'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Active</span>
                <?php elseif ($r['status'] === 'dropped_out'): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">Dropped Out</span>
                  <?php if (!empty($r['drop_out_reason'])): ?>
                    <div class="text-[10px] text-rose-600 capitalize mt-0.5"><?= h(str_replace('_', ' ', $r['drop_out_reason'])) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Inactive</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <table class="w-full tcl-table print-tcl-table text-left border-collapse">
        <thead>
          <tr>
            <th>No.</th>
            <th>Date Registered</th>
            <th>Client Name</th>
            <th>Age</th>
            <th>Purok</th>
            <th>Method Accepted</th>
            <th>Next Service Due</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $index => $r):
            $recVisits = $visitsByRecord[$r['id']] ?? [];
            $lastVisit = !empty($recVisits) ? end($recVisits) : null;
            $nextDue = $lastVisit['next_appointment_date'] ?? null;
          ?>
            <tr>
              <td class="text-center"><?= $index + 1 ?></td>
              <td><?= h(date('m/d/y', strtotime($r['registration_date']))) ?></td>
              <td><?= h($r['last_name'] . ', ' . $r['first_name'] . ($r['middle_name'] ? ' ' . substr($r['middle_name'], 0, 1) . '.' : '')) ?></td>
              <td class="text-center"><?= (int)$r['age'] ?></td>
              <td><?= h($r['barangay'] ?: '—') ?></td>
              <td><?= h(tcl_method_name($r['method_accepted'])) ?></td>
              <td><?= $nextDue ? h(date('m/d/y', strtotime($nextDue))) : '—' ?></td>
              <td><?= h(ucwords(str_replace('_', ' ', $r['status']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_enroll_modal.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
