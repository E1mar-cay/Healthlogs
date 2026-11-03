<?php
$pageTitle = 'Inventory Transactions';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$clauses = [];
$params = [];
if ($q !== '') {
    $clauses[] = "(m.name LIKE ? OR COALESCE(t.reference, '') LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like];
}
if (in_array($typeFilter, ['received', 'dispensed', 'adjustment', 'expired', 'returned'], true)) {
    $clauses[] = "t.transaction_type = ?";
    $params[] = $typeFilter;
}
$where = !empty($clauses) ? "WHERE " . implode(' AND ', $clauses) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_transactions t JOIN medicines m ON m.id = t.medicine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 20);

$stmt = $pdo->prepare("SELECT t.*, m.name AS medicine_name, m.unit,
    b.batch_no, b.expiry_date
    FROM medicine_transactions t
    JOIN medicines m ON m.id = t.medicine_id
    LEFT JOIN medicine_batches b ON b.id = t.batch_id
    $where ORDER BY t.transaction_datetime DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$typeBadge = [
    'received'   => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'dispensed'  => 'bg-blue-100 text-blue-800 border-blue-200',
    'adjustment' => 'bg-amber-100 text-amber-800 border-amber-200',
    'expired'    => 'bg-rose-100 text-rose-800 border-rose-200',
    'returned'   => 'bg-purple-100 text-purple-800 border-purple-200',
];
?>
<?php display_flash_messages(); ?>

<!-- Page Header -->
<div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs mb-5">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="text-xs font-semibold text-teal-600 uppercase tracking-widest"><i class="fas fa-right-left mr-1"></i> Medicine Supply</div>
      <div class="text-xl font-bold text-slate-900 mt-0.5">Inventory Transactions</div>
      <p class="text-xs text-slate-500 mt-0.5">Stock movements — received, dispensed, adjustments, and write-offs.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="/HealthLogs/public/inventory/batches/index.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        <i class="fas fa-boxes-stacked mr-1.5 text-teal-600"></i> Batches
      </a>
      <?php if (can_manage_clinical_records()): ?>
        <button type="button" id="transactionModalOpenNew" data-embed-url="/HealthLogs/public/inventory/transactions/form_embed.php"
          class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition shadow-xs">
          <i class="fas fa-plus mr-1"></i> New Transaction
        </button>
      <?php else: ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
          <i class="fas fa-eye mr-1.5 text-slate-400"></i> Monitoring Mode
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row gap-3 mb-5">
  <div class="relative flex-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none"><i class="fas fa-search text-xs"></i></span>
    <input name="q" value="<?= h($q) ?>" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="Search medicine name or reference" />
  </div>
  <select name="type" class="w-full md:w-52 border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
    <option value="">All transaction types</option>
    <option value="received"   <?= $typeFilter === 'received'   ? 'selected' : '' ?>>Received</option>
    <option value="dispensed"  <?= $typeFilter === 'dispensed'  ? 'selected' : '' ?>>Dispensed</option>
    <option value="adjustment" <?= $typeFilter === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
    <option value="expired"    <?= $typeFilter === 'expired'    ? 'selected' : '' ?>>Expired / Write-off</option>
    <option value="returned"   <?= $typeFilter === 'returned'   ? 'selected' : '' ?>>Returned</option>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-slate-800 transition" type="submit">Filter</button>
    <a class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition" href="/HealthLogs/public/inventory/transactions/index.php">Clear</a>
  </div>
</form>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-5">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left border-collapse">
      <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
        <tr>
          <th class="py-3.5 px-4">Medicine</th>
          <th class="py-3.5 px-4">Type</th>
          <th class="py-3.5 px-4">Quantity</th>
          <th class="py-3.5 px-4">Batch / Expiry</th>
          <th class="py-3.5 px-4">Date & Time</th>
          <th class="py-3.5 px-4">Reference</th>
          <th class="py-3.5 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-xs">
        <?php if (empty($transactions)): ?>
          <tr><td class="px-4 py-8 text-center text-slate-500" colspan="7">No transactions found matching the selected filters.</td></tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): ?>
            <?php
              $isOutgoing = in_array($t['transaction_type'], ['dispensed', 'expired'], true);
              $qty = (int)$t['quantity'];
              $qtyDisplay = ($qty > 0 ? '+' : '') . $qty;
              $badge = $typeBadge[$t['transaction_type']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
            ?>
            <tr class="hover:bg-slate-50/60 transition">
              <td class="px-4 py-3 font-semibold text-slate-900">
                <?= h($t['medicine_name']) ?>
                <?php if (!empty($t['unit'])): ?>
                  <span class="text-[10px] text-slate-400 font-normal ml-1"><?= h($t['unit']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border <?= $badge ?>">
                  <?= ucfirst(h($t['transaction_type'])) ?>
                </span>
              </td>
              <td class="px-4 py-3 font-mono font-bold whitespace-nowrap <?= $isOutgoing ? 'text-rose-600' : 'text-emerald-700' ?>">
                <?= h($qtyDisplay) ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                <?php if (!empty($t['batch_no'])): ?>
                  <span class="font-mono text-[11px] bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200"><?= h($t['batch_no']) ?></span>
                  <?php if (!empty($t['expiry_date'])): ?>
                    <div class="text-[10px] text-slate-400 mt-0.5">Exp: <?= date('M Y', strtotime($t['expiry_date'])) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                <?= date('M d, Y', strtotime($t['transaction_datetime'])) ?>
                <div class="text-[10px] text-slate-400"><?= date('h:i A', strtotime($t['transaction_datetime'])) ?></div>
              </td>
              <td class="px-4 py-3 text-slate-500 max-w-[140px] truncate" title="<?= h($t['reference'] ?? '') ?>">
                <?= h($t['reference'] ?: '—') ?>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if (can_manage_clinical_records()): ?>
                  <button type="button" class="transaction-modal-edit px-2.5 py-1 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold mr-1"
                    data-embed-url="/HealthLogs/public/inventory/transactions/form_embed.php?id=<?= (int)$t['id'] ?>">Edit</button>
                  <form method="post" action="/HealthLogs/public/inventory/transactions/delete.php" class="inline"
                    data-confirm="Delete this transaction?" data-confirm-title="Delete transaction" data-confirm-cta="Yes, delete">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                    <button class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold">Delete</button>
                  </form>
                <?php else: ?>
                  <span class="text-xs text-slate-400">Read-Only</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $paginator->render() ?>

<div id="transactionFormModal" class="fixed inset-0 z-[100] hidden print:hidden" aria-modal="true" role="dialog">
  <button type="button" class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-sm border-0 cursor-default" aria-label="Close modal" id="transactionFormModalBackdrop"></button>
  <div class="relative z-10 mx-auto mt-10 max-w-5xl px-4">
    <div class="rounded-xl bg-white shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[calc(100vh-5rem)]">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Transaction form</div>
        <button type="button" id="transactionFormModalClose" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      <iframe id="transactionFormModalFrame" class="w-full min-h-[74vh] border-0 flex-1" title="Transaction form"></iframe>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('transactionFormModal');
  var frame = document.getElementById('transactionFormModalFrame');
  var backdrop = document.getElementById('transactionFormModalBackdrop');
  var closeBtn = document.getElementById('transactionFormModalClose');
  function openModal(url) { if (!modal || !frame || !url) return; frame.src = url; modal.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); if (closeBtn) closeBtn.focus(); }
  function closeModal() { if (!modal || !frame) return; frame.src = 'about:blank'; modal.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
  var newBtn = document.getElementById('transactionModalOpenNew');
  if (newBtn) newBtn.addEventListener('click', function () { openModal(newBtn.getAttribute('data-embed-url') || ''); });
  document.querySelectorAll('.transaction-modal-edit').forEach(function (btn) { btn.addEventListener('click', function () { openModal(btn.getAttribute('data-embed-url') || ''); }); });
  if (backdrop) backdrop.addEventListener('click', closeModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  window.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
