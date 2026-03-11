<?php
$pageTitle = 'Vaccines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$rows = $pdo->query("SELECT * FROM vaccines ORDER BY id DESC")->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Vaccines</div>
  <a href="/HealthLogs/public/immunization/vaccines/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Vaccine</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Code</th>
        <th class="text-left px-4 py-2">Doses</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="4">No vaccines found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $v): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($v['name']) ?></td>
            <td class="px-4 py-2"><?= h($v['code']) ?></td>
            <td class="px-4 py-2"><?= h($v['doses_required']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/immunization/vaccines/form.php?id=<?= (int)$v['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/immunization/vaccines/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this vaccine?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

