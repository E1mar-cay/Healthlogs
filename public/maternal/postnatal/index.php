<?php
$pageTitle = 'Postnatal Visits';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT v.*, p.first_name, p.last_name
        FROM postnatal_visits v
        JOIN pregnancies pr ON pr.id = v.pregnancy_id
        JOIN patients p ON p.id = pr.patient_id
        ORDER BY v.visit_datetime DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Postnatal Visits</div>
  <a href="/HealthLogs/public/maternal/postnatal/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Visit</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">Visit Date</th>
        <th class="text-left px-4 py-2">Mother</th>
        <th class="text-left px-4 py-2">Baby</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No visits found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['visit_datetime']) ?></td>
            <td class="px-4 py-2"><?= h($r['mother_condition']) ?></td>
            <td class="px-4 py-2"><?= h($r['baby_condition']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/maternal/postnatal/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/maternal/postnatal/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this visit?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

