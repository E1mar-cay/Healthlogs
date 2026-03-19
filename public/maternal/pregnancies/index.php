<?php
$pageTitle = 'Pregnancies';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $statusFilter !== '') {
  $clauses = [];
  if ($q !== '') { $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)"; $like = '%' . $q . '%'; $params = [$like, $like]; }
  if (in_array($statusFilter, ['ongoing', 'delivered', 'terminated'], true)) { $clauses[] = "pr.status = ?"; $params[] = $statusFilter; }
  $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT pr.*, p.first_name, p.last_name FROM pregnancies pr JOIN patients p ON p.id = pr.patient_id $where ORDER BY pr.id DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Pregnancies</div>
  <a href="/HealthLogs/public/maternal/pregnancies/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Pregnancy</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient or status" />
  <select name="status" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All statuses</option>
    <option value="ongoing" <?= $statusFilter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
    <option value="terminated" <?= $statusFilter === 'terminated' ? 'selected' : '' ?>>Terminated</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/maternal/pregnancies/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">LMP</th><th class="text-left px-4 py-2">EDD</th><th class="text-left px-4 py-2">Status</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No pregnancies found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['lmp_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['edd_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['status']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/maternal/pregnancies/form.php?id=<?= (int)$r['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/maternal/pregnancies/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this record?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
