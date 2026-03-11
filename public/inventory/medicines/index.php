<?php
$pageTitle = 'Medicines';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$rows = $pdo->query("SELECT * FROM medicines ORDER BY id DESC")->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Medicines</div>
  <a href="/HealthLogs/public/inventory/medicines/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Medicine</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Generic</th>
        <th class="text-left px-4 py-2">Unit</th>
        <th class="text-left px-4 py-2">Reorder Level</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No medicines found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $m): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($m['name']) ?></td>
            <td class="px-4 py-2"><?= h($m['generic_name']) ?></td>
            <td class="px-4 py-2"><?= h($m['unit']) ?></td>
            <td class="px-4 py-2"><?= h($m['reorder_level']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/inventory/medicines/form.php?id=<?= (int)$m['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/inventory/medicines/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this medicine?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

