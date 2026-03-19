<?php
$pageTitle = 'Medicines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$stockFilter = $_GET['stock'] ?? '';
$where = '';
$params = [];
if ($q !== '') { $where = "WHERE m.name LIKE ? OR m.generic_name LIKE ? OR m.unit LIKE ?"; $like = '%' . $q . '%'; $params = [$like, $like, $like]; }
$havingSql = '';
if ($stockFilter === 'low') { $havingSql = ' HAVING on_hand <= COALESCE(reorder_level, 0)'; }
elseif ($stockFilter === 'available') { $havingSql = ' HAVING on_hand > COALESCE(reorder_level, 0)'; }
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM (SELECT m.id, m.reorder_level, COALESCE(SUM(mt.quantity), 0) AS on_hand FROM medicines m LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id $where GROUP BY m.id$havingSql) filtered_medicines");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT m.*, COALESCE(SUM(mt.quantity), 0) AS on_hand FROM medicines m LEFT JOIN medicine_transactions mt ON mt.medicine_id = m.id $where GROUP BY m.id$havingSql ORDER BY m.id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Medicines</div>
  <a href="/HealthLogs/public/inventory/medicines/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Medicine</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search medicine name, generic name, or unit" />
  <select name="stock" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All stock levels</option>
    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low stock</option>
    <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Above reorder level</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/inventory/medicines/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Generic</th><th class="text-left px-4 py-2">Unit</th><th class="text-left px-4 py-2">On Hand</th><th class="text-left px-4 py-2">Reorder Level</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="6">No medicines found.</td></tr><?php else: ?>
        <?php foreach ($rows as $m): ?>
          <?php $isLowStock = (float)$m['on_hand'] <= (float)($m['reorder_level'] ?? 0); ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($m['name']) ?></td>
            <td class="px-4 py-2"><?= h($m['generic_name']) ?></td>
            <td class="px-4 py-2"><?= h($m['unit']) ?></td>
            <td class="px-4 py-2 <?= $isLowStock ? 'text-red-600 font-semibold' : '' ?>"><?= h($m['on_hand']) ?></td>
            <td class="px-4 py-2"><?= h($m['reorder_level']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/inventory/medicines/form.php?id=<?= (int)$m['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/inventory/medicines/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this medicine?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
