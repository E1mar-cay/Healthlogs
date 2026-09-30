<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM medicine_transactions WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        $_SESSION['error_message'] = 'Transaction not found';
        header('Location: /HealthLogs/public/inventory/transactions/index.php');
        exit;
    }
}

$displayQuantity = old('quantity', $rec ? abs((int)$rec['quantity']) : '');
$adjustmentMode = old('adjustment_mode', ($rec && ($rec['transaction_type'] ?? '') === 'adjustment' && (int)$rec['quantity'] < 0)
    ? 'decrease'
    : 'increase');

$meds = $pdo->query("SELECT id, name FROM medicines ORDER BY name ASC")->fetchAll();
$batches = $pdo->query("
    SELECT b.id, b.medicine_id, b.batch_no, b.expiry_date, m.name AS medicine_name,
           COALESCE(SUM(t.quantity), 0) AS on_hand,
           CASE WHEN b.expiry_date < CURDATE() THEN 1 ELSE 0 END AS is_expired,
           CASE
               WHEN b.expiry_date < CURDATE() THEN 'Expired'
               WHEN b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Near Expiry'
               ELSE 'Valid'
           END AS expiry_classification,
           DATEDIFF(b.expiry_date, CURDATE()) AS days_remaining
    FROM medicine_batches b
    JOIN medicines m ON m.id = b.medicine_id
    LEFT JOIN medicine_transactions t ON t.batch_id = b.id
    GROUP BY b.id
    ORDER BY is_expired ASC, b.expiry_date ASC
")->fetchAll();

$pageTitle = $rec ? 'Edit Transaction' : 'New Transaction';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs max-w-4xl mx-auto">
  <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-slate-900"><?= h($pageTitle) ?></h1>
      <p class="text-xs text-slate-500 mt-0.5">Record medicine stock movement with automatic expiration & issuance safety.</p>
    </div>
    <span class="text-xs px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 font-semibold border border-teal-200">
      <i class="fas fa-shield-halved mr-1"></i> Issuance Safety Enforced
    </span>
  </div>

  <?php display_flash_messages(true, true); ?>
  <form id="transactionForm" method="post" action="/HealthLogs/public/inventory/transactions/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Medicine <span class="text-rose-500">*</span></label>
      <select id="medicineSelect" name="medicine_id" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <?php foreach ($meds as $m): ?>
          <?php $sel = old('medicine_id', $rec['medicine_id'] ?? 0) == $m['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Batch (Optional)</label>
      <select id="batchSelect" name="batch_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="">-- No specific batch --</option>
        <?php foreach ($batches as $b): ?>
          <?php
            $isExp = (int)$b['is_expired'] === 1;
            $sel = (string)old('batch_id', $rec['batch_id'] ?? '') === (string)$b['id'] ? 'selected' : '';
            $statusText = $isExp ? ' [EXPIRED - CANNOT ISSUE]' : " [{$b['expiry_classification']} • {$b['days_remaining']}d left]";
          ?>
          <option value="<?= (int)$b['id'] ?>"
            data-medicine-id="<?= (int)$b['medicine_id'] ?>"
            data-expired="<?= $isExp ? '1' : '0' ?>"
            data-on-hand="<?= (int)$b['on_hand'] ?>"
            data-expiry-date="<?= h($b['expiry_date']) ?>"
            <?= $sel ?>>
            <?= h($b['medicine_name'] . ' - ' . $b['batch_no'] . ' (On hand: ' . $b['on_hand'] . ' | Exp: ' . $b['expiry_date'] . $statusText . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div id="batchExpiryAlert" class="hidden mt-1.5 p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-1.5">
        <i class="fas fa-ban text-rose-600"></i>
        <span>This batch has expired. It cannot be selected for issuance/dispensing.</span>
      </div>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Transaction Type <span class="text-rose-500">*</span></label>
      <?php $type = old('transaction_type', $rec['transaction_type'] ?? 'received'); ?>
      <select id="transactionTypeSelect" name="transaction_type" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500">
        <option value="received" <?= $type === 'received' ? 'selected' : '' ?>>Received (Stock In / Delivery)</option>
        <option value="dispensed" <?= $type === 'dispensed' ? 'selected' : '' ?>>Dispensed (Release / Issuance to Patient)</option>
        <option value="adjustment" <?= $type === 'adjustment' ? 'selected' : '' ?>>Adjustment (Physical Count Reconciliation)</option>
        <option value="returned" <?= $type === 'returned' ? 'selected' : '' ?>>Returned (Unused Medicine Returned to Center)</option>
        <option value="expired" <?= $type === 'expired' ? 'selected' : '' ?>>Expired (Disposal / Write-off)</option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Quantity <span class="text-rose-500">*</span></label>
      <input name="quantity" type="number" min="1" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h($displayQuantity) ?>" placeholder="e.g. 50" />
      <p class="mt-1 text-[11px] text-slate-500">Enter a positive amount. Direction (add/deduct) is applied automatically based on transaction type.</p>
    </div>

    <div id="adjustmentModeContainer">
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Adjustment Direction</label>
      <select name="adjustment_mode" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="increase" <?= $adjustmentMode === 'increase' ? 'selected' : '' ?>>Add stock (+)</option>
        <option value="decrease" <?= $adjustmentMode === 'decrease' ? 'selected' : '' ?>>Reduce stock (-)</option>
      </select>
      <p class="mt-1 text-[11px] text-slate-500">Used only when type is set to Adjustment.</p>
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Transaction Date / Time <span class="text-rose-500">*</span></label>
      <input name="transaction_datetime" type="datetime-local" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= h(old('transaction_datetime', str_replace(' ', 'T', $rec['transaction_datetime'] ?? date('Y-m-d\TH:i')))) ?>" />
    </div>

    <div>
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reference Number / Code</label>
      <input name="reference" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. RX-2026-001, PO-1049, ADJ-02" value="<?= h(old('reference', $rec['reference'] ?? '')) ?>" />
    </div>

    <div class="md:col-span-2">
      <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Notes / Remarks</label>
      <textarea name="notes" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm" rows="2" placeholder="Reason for transaction, patient name, or program details..."><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
    </div>

    <div class="md:col-span-2 flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
      <a class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition" href="/HealthLogs/public/inventory/transactions/index.php">Cancel</a>
      <button class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2 rounded-xl text-xs font-semibold transition shadow-xs" type="submit">Save Transaction</button>
    </div>
  </form>
</div>

<script>
(function() {
  const typeSelect = document.getElementById('transactionTypeSelect');
  const batchSelect = document.getElementById('batchSelect');
  const medSelect = document.getElementById('medicineSelect');
  const alertBox = document.getElementById('batchExpiryAlert');

  function updateBatchOptions() {
    const isDispensed = typeSelect.value === 'dispensed';
    const selectedMedId = medSelect.value;
    let hasSelectedExpired = false;

    Array.from(batchSelect.options).forEach((opt, idx) => {
      if (idx === 0) return; // skip default
      const optMedId = opt.getAttribute('data-medicine-id');
      const isExpired = opt.getAttribute('data-expired') === '1';

      // Match medicine
      const medMatches = (!selectedMedId || optMedId === selectedMedId);

      if (isDispensed && isExpired) {
        opt.disabled = true;
        opt.classList.add('text-slate-300', 'bg-slate-100');
        if (opt.selected) {
          hasSelectedExpired = true;
        }
      } else {
        opt.disabled = false;
        opt.classList.remove('text-slate-300', 'bg-slate-100');
      }

      // Hide if doesn't match selected medicine
      opt.hidden = !medMatches;
    });

    if (hasSelectedExpired && isDispensed) {
      batchSelect.value = '';
      if (alertBox) alertBox.classList.remove('hidden');
    } else {
      const currentOpt = batchSelect.options[batchSelect.selectedIndex];
      if (currentOpt && currentOpt.getAttribute('data-expired') === '1' && isDispensed) {
        if (alertBox) alertBox.classList.remove('hidden');
      } else {
        if (alertBox) alertBox.classList.add('hidden');
      }
    }
  }

  typeSelect.addEventListener('change', updateBatchOptions);
  medSelect.addEventListener('change', updateBatchOptions);
  batchSelect.addEventListener('change', function() {
    const currentOpt = batchSelect.options[batchSelect.selectedIndex];
    if (currentOpt && currentOpt.getAttribute('data-expired') === '1' && typeSelect.value === 'dispensed') {
      if (alertBox) alertBox.classList.remove('hidden');
      batchSelect.value = '';
      alert('Cannot select an expired batch for issuance/dispensing.');
    } else {
      if (alertBox) alertBox.classList.add('hidden');
    }
  });

  updateBatchOptions();
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
