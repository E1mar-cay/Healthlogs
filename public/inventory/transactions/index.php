<?php
$pageTitle = 'Inventory Transactions & Stock';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $typeFilter !== '') {
  $clauses = [];
  if ($q !== '') { $clauses[] = "(m.name LIKE ? OR COALESCE(t.reference, '') LIKE ?)"; $like = '%' . $q . '%'; $params = [$like, $like]; }
  if (in_array($typeFilter, ['received', 'dispensed', 'adjustment', 'expired', 'returned'], true)) { $clauses[] = "t.transaction_type = ?"; $params[] = $typeFilter; }
  $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_transactions t JOIN medicines m ON m.id = t.medicine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT t.*, m.name AS medicine_name FROM medicine_transactions t JOIN medicines m ON m.id = t.medicine_id $where ORDER BY t.transaction_datetime DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$transactions = $stmt->fetchAll();
$stock = $pdo->query("SELECT m.id, m.name, COALESCE(SUM(t.quantity), 0) AS on_hand FROM medicines m LEFT JOIN medicine_transactions t ON t.medicine_id = m.id GROUP BY m.id, m.name ORDER BY m.name")->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Transactions</div>
  <a href="/HealthLogs/public/inventory/transactions/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Transaction</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search medicine, type, or reference" />
  <select name="type" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All transaction types</option>
    <option value="received" <?= $typeFilter === 'received' ? 'selected' : '' ?>>Received</option>
    <option value="dispensed" <?= $typeFilter === 'dispensed' ? 'selected' : '' ?>>Dispensed</option>
    <option value="adjustment" <?= $typeFilter === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
    <option value="expired" <?= $typeFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
    <option value="returned" <?= $typeFilter === 'returned' ? 'selected' : '' ?>>Returned</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/inventory/transactions/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Medicine</th><th class="text-left px-4 py-2">Type</th><th class="text-left px-4 py-2">Qty</th><th class="text-left px-4 py-2">Date/Time</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($transactions)): ?><tr><td class="px-4 py-4" colspan="5">No transactions found.</td></tr><?php else: ?>
        <?php foreach ($transactions as $t): ?>
          <?php $isOutgoing = in_array($t['transaction_type'], ['dispensed', 'expired'], true); $qtyLabel = ($isOutgoing ? '-' : '+') . abs((int)$t['quantity']); ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($t['medicine_name']) ?></td>
            <td class="px-4 py-2"><?= h($t['transaction_type']) ?></td>
            <td class="px-4 py-2 <?= $isOutgoing ? 'text-red-600' : 'text-emerald-700' ?>"><?= h($qtyLabel) ?></td>
            <td class="px-4 py-2"><?= h($t['transaction_datetime']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/inventory/transactions/form.php?id=<?= (int)$t['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/inventory/transactions/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this transaction?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<div class="mt-8">
  <div class="text-lg font-semibold mb-2">Current Stock</div>
  <div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Medicine</th><th class="text-left px-4 py-2">On Hand</th></tr></thead>
      <tbody>
        <?php if (empty($stock)): ?><tr><td class="px-4 py-4" colspan="2">No stock data.</td></tr><?php else: ?>
          <?php foreach ($stock as $s): ?><tr class="border-t"><td class="px-4 py-2"><?= h($s['name']) ?></td><td class="px-4 py-2"><?= h($s['on_hand']) ?></td></tr><?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
