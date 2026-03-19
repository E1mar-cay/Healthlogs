<?php
$pageTitle = 'Batches';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$availabilityFilter = $_GET['availability'] ?? '';
$where = '';
$params = [];
if ($q !== '') { $where = "WHERE m.name LIKE ? OR b.batch_no LIKE ?"; $like = '%' . $q . '%'; $params = [$like, $like]; }
$havingSql = '';
if ($availabilityFilter === 'in_stock') { $havingSql = ' HAVING on_hand > 0'; }
elseif ($availabilityFilter === 'empty') { $havingSql = ' HAVING on_hand <= 0'; }
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT b.id, COALESCE(SUM(t.quantity), 0) AS on_hand FROM medicine_batches b JOIN medicines m ON m.id = b.medicine_id LEFT JOIN medicine_transactions t ON t.batch_id = b.id $where GROUP BY b.id$havingSql) filtered_batches");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT b.*, m.name AS medicine_name, COALESCE(SUM(t.quantity), 0) AS on_hand FROM medicine_batches b JOIN medicines m ON m.id = b.medicine_id LEFT JOIN medicine_transactions t ON t.batch_id = b.id $where GROUP BY b.id$havingSql ORDER BY b.id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Medicine Batches</div>
  <a href="/HealthLogs/public/inventory/batches/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Batch</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search medicine name or batch number" />
  <select name="availability" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All batches</option>
    <option value="in_stock" <?= $availabilityFilter === 'in_stock' ? 'selected' : '' ?>>With stock</option>
    <option value="empty" <?= $availabilityFilter === 'empty' ? 'selected' : '' ?>>No stock left</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/inventory/batches/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Medicine</th><th class="text-left px-4 py-2">Batch No</th><th class="text-left px-4 py-2">Expiry</th><th class="text-left px-4 py-2">Received</th><th class="text-left px-4 py-2">On Hand</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="6">No batches found.</td></tr><?php else: ?>
        <?php foreach ($rows as $b): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($b['medicine_name']) ?></td>
            <td class="px-4 py-2"><?= h($b['batch_no']) ?></td>
            <td class="px-4 py-2"><?= h($b['expiry_date']) ?></td>
            <td class="px-4 py-2"><?= h($b['quantity_received']) ?></td>
            <td class="px-4 py-2 <?= (float)$b['on_hand'] <= 0 ? 'text-red-600 font-semibold' : '' ?>"><?= h($b['on_hand']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/inventory/batches/form.php?id=<?= (int)$b['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/inventory/batches/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this batch?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
