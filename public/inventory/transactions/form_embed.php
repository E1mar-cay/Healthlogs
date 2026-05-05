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
$adjustmentMode = old('adjustment_mode', ($rec && ($rec['transaction_type'] ?? '') === 'adjustment' && (int)$rec['quantity'] < 0) ? 'decrease' : 'increase');
$meds = $pdo->query("SELECT id, name FROM medicines ORDER BY name ASC")->fetchAll();
$batches = $pdo->query("
    SELECT b.id, b.batch_no, m.name AS medicine_name, COALESCE(SUM(t.quantity), 0) AS on_hand
    FROM medicine_batches b
    JOIN medicines m ON m.id = b.medicine_id
    LEFT JOIN medicine_transactions t ON t.batch_id = b.id
    GROUP BY b.id
    ORDER BY b.id DESC
")->fetchAll();
$title = $rec ? 'Edit Transaction' : 'New Transaction';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <base target="_parent">
  <title><?= h($title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 p-4 text-slate-900">
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
    <div class="mb-5">
      <h1 class="text-xl font-semibold"><?= h($title) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Record stock movement and balance changes.</p>
    </div>
    <?php display_flash_messages(); ?>
    <form method="post" action="/HealthLogs/public/inventory/transactions/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="form_context" value="embed">
      <?php if ($rec): ?><input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" /><?php endif; ?>
      <div>
        <label class="block text-sm text-slate-600">Medicine</label>
        <select name="medicine_id" required class="mt-1 w-full border rounded px-3 py-2">
          <?php foreach ($meds as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= old('medicine_id', $rec['medicine_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>><?= h($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Batch (optional)</label>
        <select name="batch_id" class="mt-1 w-full border rounded px-3 py-2">
          <option value="">-- none --</option>
          <?php foreach ($batches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= (string)old('batch_id', $rec['batch_id'] ?? '') === (string)$b['id'] ? 'selected' : '' ?>><?= h($b['medicine_name'] . ' - ' . $b['batch_no'] . ' (On hand: ' . $b['on_hand'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Type</label>
        <?php $type = old('transaction_type', $rec['transaction_type'] ?? 'received'); ?>
        <select name="transaction_type" class="mt-1 w-full border rounded px-3 py-2">
          <option value="received" <?= $type === 'received' ? 'selected' : '' ?>>Received</option>
          <option value="dispensed" <?= $type === 'dispensed' ? 'selected' : '' ?>>Dispensed</option>
          <option value="adjustment" <?= $type === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
          <option value="expired" <?= $type === 'expired' ? 'selected' : '' ?>>Expired</option>
          <option value="returned" <?= $type === 'returned' ? 'selected' : '' ?>>Returned</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Quantity</label>
        <input name="quantity" type="number" min="1" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($displayQuantity) ?>" />
        <p class="mt-1 text-xs text-slate-500">Enter a positive number. The system will apply stock in or stock out automatically based on the type.</p>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Adjustment Direction</label>
        <select name="adjustment_mode" class="mt-1 w-full border rounded px-3 py-2">
          <option value="increase" <?= $adjustmentMode === 'increase' ? 'selected' : '' ?>>Add stock</option>
          <option value="decrease" <?= $adjustmentMode === 'decrease' ? 'selected' : '' ?>>Reduce stock</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Used only when the type is Adjustment.</p>
      </div>
      <div>
        <label class="block text-sm text-slate-600">Transaction Date/Time</label>
        <input name="transaction_datetime" type="datetime-local" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('transaction_datetime', str_replace(' ', 'T', $rec['transaction_datetime'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="block text-sm text-slate-600">Reference</label>
        <input name="reference" class="mt-1 w-full border rounded px-3 py-2" value="<?= h(old('reference', $rec['reference'] ?? '')) ?>" />
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm text-slate-600">Notes</label>
        <textarea name="notes" class="mt-1 w-full border rounded px-3 py-2" rows="2"><?= h(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
      <div class="md:col-span-2 flex items-center gap-2 mt-2">
        <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
        <a class="text-slate-600" href="/HealthLogs/public/inventory/transactions/index.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
