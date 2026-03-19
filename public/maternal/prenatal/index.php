<?php
$pageTitle = 'Prenatal Visits';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';
$q = trim($_GET['q'] ?? '');
$periodFilter = $_GET['period'] ?? '';
$where = '';
$params = [];
if ($q !== '' || $periodFilter !== '') {
  $clauses = [];
  if ($q !== '') { $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)"; $like = '%' . $q . '%'; $params = [$like, $like]; }
  if ($periodFilter === '30') { $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }
  elseif ($periodFilter === '90') { $clauses[] = "v.visit_datetime >= DATE_SUB(NOW(), INTERVAL 90 DAY)"; }
  $where = "WHERE " . implode(' AND ', $clauses);
}
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM prenatal_visits v JOIN pregnancies pr ON pr.id = v.pregnancy_id JOIN patients p ON p.id = pr.patient_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT v.*, p.first_name, p.last_name FROM prenatal_visits v JOIN pregnancies pr ON pr.id = v.pregnancy_id JOIN patients p ON p.id = pr.patient_id $where ORDER BY v.visit_datetime DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Prenatal Visits</div>
  <a href="/HealthLogs/public/maternal/prenatal/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Visit</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient name" />
  <select name="period" class="w-full md:w-56 border rounded px-3 py-2">
    <option value="">All visits</option>
    <option value="30" <?= $periodFilter === '30' ? 'selected' : '' ?>>Last 30 days</option>
    <option value="90" <?= $periodFilter === '90' ? 'selected' : '' ?>>Last 90 days</option>
  </select>
  <div class="flex gap-2"><button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button><a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/maternal/prenatal/index.php">Clear</a></div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">Visit Date</th><th class="text-left px-4 py-2">GA Weeks</th><th class="text-left px-4 py-2">BP</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No visits found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['visit_datetime']) ?></td>
            <td class="px-4 py-2"><?= h($r['gestational_age_weeks']) ?></td>
            <td class="px-4 py-2"><?= h($r['bp_systolic']) ?>/<?= h($r['bp_diastolic']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/maternal/prenatal/form.php?id=<?= (int)$r['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/maternal/prenatal/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this visit?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
