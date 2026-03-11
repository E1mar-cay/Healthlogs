<?php
$pageTitle = 'Pregnancies';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT pr.*, p.first_name, p.last_name
        FROM pregnancies pr
        JOIN patients p ON p.id = pr.patient_id
        ORDER BY pr.id DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Pregnancies</div>
  <a href="/HealthLogs/public/maternal/pregnancies/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Pregnancy</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Patient</th>
        <th class="text-left px-4 py-2">LMP</th>
        <th class="text-left px-4 py-2">EDD</th>
        <th class="text-left px-4 py-2">Status</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No pregnancies found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($r['last_name'] . ', ' . $r['first_name']) ?></td>
            <td class="px-4 py-2"><?= h($r['lmp_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['edd_date']) ?></td>
            <td class="px-4 py-2"><?= h($r['status']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/maternal/pregnancies/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/maternal/pregnancies/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this record?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

