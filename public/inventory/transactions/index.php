<?php
$pageTitle = 'Inventory Transactions & Stock';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$transactions = $pdo->query("SELECT t.*, m.name AS medicine_name
    FROM medicine_transactions t
    JOIN medicines m ON m.id = t.medicine_id
    ORDER BY t.transaction_datetime DESC")->fetchAll();

$stockSql = "SELECT m.id, m.name,
    COALESCE(SUM(CASE WHEN t.transaction_type IN ('received','returned','adjustment') THEN t.quantity ELSE 0 END),0)
    - COALESCE(SUM(CASE WHEN t.transaction_type IN ('dispensed','expired') THEN t.quantity ELSE 0 END),0)
    AS on_hand
    FROM medicines m
    LEFT JOIN medicine_transactions t ON t.medicine_id = m.id
    GROUP BY m.id, m.name
    ORDER BY m.name";
$stock = $pdo->query($stockSql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Transactions</div>
  <a href="/HealthLogs/public/inventory/transactions/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Transaction</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Medicine</th>
        <th class="text-left px-4 py-2">Type</th>
        <th class="text-left px-4 py-2">Qty</th>
        <th class="text-left px-4 py-2">Date/Time</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($transactions)): ?>
        <tr><td class="px-4 py-4" colspan="5">No transactions found.</td></tr>
      <?php else: ?>
        <?php foreach ($transactions as $t): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($t['medicine_name']) ?></td>
            <td class="px-4 py-2"><?= h($t['transaction_type']) ?></td>
            <td class="px-4 py-2"><?= h($t['quantity']) ?></td>
            <td class="px-4 py-2"><?= h($t['transaction_datetime']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/inventory/transactions/form.php?id=<?= (int)$t['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/inventory/transactions/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this transaction?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="mt-8">
  <div class="text-lg font-semibold mb-2">Current Stock</div>
  <div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-2">Medicine</th>
          <th class="text-left px-4 py-2">On Hand</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($stock)): ?>
          <tr><td class="px-4 py-4" colspan="2">No stock data.</td></tr>
        <?php else: ?>
          <?php foreach ($stock as $s): ?>
            <tr class="border-t">
              <td class="px-4 py-2"><?= h($s['name']) ?></td>
              <td class="px-4 py-2"><?= h($s['on_hand']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

