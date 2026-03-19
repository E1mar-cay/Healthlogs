<?php
$pageTitle = 'Vaccines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$doseFilter = $_GET['dose_group'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $doseFilter !== '') {
    $clauses = [];
    if ($q !== '') {
        $clauses[] = "(name LIKE ? OR code LIKE ?)";
        $like = '%' . $q . '%';
        $params = [$like, $like];
    }
    if ($doseFilter === 'single') {
        $clauses[] = "COALESCE(doses_required, 0) <= 1";
    } elseif ($doseFilter === 'multi') {
        $clauses[] = "COALESCE(doses_required, 0) > 1";
    }
    $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM vaccines $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT * FROM vaccines $where ORDER BY id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Vaccines</div>
  <a href="/HealthLogs/public/immunization/vaccines/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Vaccine</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search vaccine name or code" />
  <select name="dose_group" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All dose groups</option>
    <option value="single" <?= $doseFilter === 'single' ? 'selected' : '' ?>>Single dose</option>
    <option value="multi" <?= $doseFilter === 'multi' ? 'selected' : '' ?>>Multiple doses</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/immunization/vaccines/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Code</th><th class="text-left px-4 py-2">Doses</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="4">No vaccines found.</td></tr><?php else: ?>
        <?php foreach ($rows as $v): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($v['name']) ?></td>
            <td class="px-4 py-2"><?= h($v['code']) ?></td>
            <td class="px-4 py-2"><?= h($v['doses_required']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/immunization/vaccines/form.php?id=<?= (int)$v['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/immunization/vaccines/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this vaccine?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
