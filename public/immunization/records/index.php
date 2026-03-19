<?php
$pageTitle = 'Immunization Records';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$vaccineFilter = (int)($_GET['vaccine_id'] ?? 0);
$where = '';
$params = [];
if ($q !== '' || $vaccineFilter > 0) {
    $clauses = [];
    if ($q !== '') {
        $clauses[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR v.name LIKE ?)";
        $like = '%' . $q . '%';
        $params = [$like, $like, $like];
    }
    if ($vaccineFilter > 0) {
        $clauses[] = "v.id = ?";
        $params[] = $vaccineFilter;
    }
    $where = "WHERE " . implode(' AND ', $clauses);
}
$vaccines = $pdo->query("SELECT id, name FROM vaccines ORDER BY name ASC")->fetchAll();
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM immunization_records r JOIN patients p ON p.id = r.patient_id JOIN vaccines v ON v.id = r.vaccine_id $where");
$countStmt->execute($params);
$paginator = paginate((int)$countStmt->fetchColumn(), 15);
$stmt = $pdo->prepare("SELECT r.*, p.first_name, p.last_name, v.name AS vaccine_name FROM immunization_records r JOIN patients p ON p.id = r.patient_id JOIN vaccines v ON v.id = r.vaccine_id $where ORDER BY r.administered_at DESC " . $paginator->getLimitSql());
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Immunization Records</div>
  <a href="/HealthLogs/public/immunization/records/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Record</a>
</div>
<form method="get" class="mt-4 bg-white rounded shadow p-4 flex flex-col md:flex-row gap-3">
  <input name="q" value="<?= h($q) ?>" class="w-full border rounded px-3 py-2" placeholder="Search patient or vaccine" />
  <select name="vaccine_id" class="w-full md:w-64 border rounded px-3 py-2">
    <option value="0">All vaccines</option>
    <?php foreach ($vaccines as $vaccine): ?>
      <option value="<?= (int)$vaccine['id'] ?>" <?= $vaccineFilter === (int)$vaccine['id'] ? 'selected' : '' ?>><?= h($vaccine['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="flex gap-2">
    <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Search</button>
    <a class="px-4 py-2 rounded border border-slate-300 text-slate-700" href="/HealthLogs/public/immunization/records/index.php">Clear</a>
  </div>
</form>
<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Patient</th><th class="text-left px-4 py-2">Vaccine</th><th class="text-left px-4 py-2">Dose</th><th class="text-left px-4 py-2">Date/Time</th><th class="text-left px-4 py-2">Actions</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?><tr><td class="px-4 py-4" colspan="5">No records found.</td></tr><?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['vaccine_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['dose_no']) ?></td>
            <td class="px-4 py-2"><?= h($r['administered_at']) ?></td>
            <td class="px-4 py-2"><a class="text-blue-600" href="/HealthLogs/public/immunization/records/form.php?id=<?= (int)$r['id'] ?>">Edit</a><form method="post" action="/HealthLogs/public/immunization/records/delete.php" class="inline"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>" /><button class="text-red-600 ml-2" data-confirm="Delete this record?">Delete</button></form></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
