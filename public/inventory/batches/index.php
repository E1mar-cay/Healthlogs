<?php
$pageTitle = 'Batches';
require __DIR__ . '/../../partials/bootstrap.php';
require __DIR__ . '/../../partials/header.php';

$sql = "SELECT b.*, m.name AS medicine_name
        FROM medicine_batches b
        JOIN medicines m ON m.id = b.medicine_id
        ORDER BY b.id DESC";
$rows = $pdo->query($sql)->fetchAll();
?>

<div class="flex items-center justify-between">
  <div class="text-lg font-semibold">Medicine Batches</div>
  <a href="/HealthLogs/public/inventory/batches/form.php" class="bg-slate-900 text-white px-4 py-2 rounded">New Batch</a>
</div>

<div class="mt-4 bg-white rounded shadow overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600">
      <tr>
        <th class="text-left px-4 py-2">Medicine</th>
        <th class="text-left px-4 py-2">Batch No</th>
        <th class="text-left px-4 py-2">Expiry</th>
        <th class="text-left px-4 py-2">Qty</th>
        <th class="text-left px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td class="px-4 py-4" colspan="5">No batches found.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $b): ?>
          <tr class="border-t">
            <td class="px-4 py-2"><?= h($b['medicine_name']) ?></td>
            <td class="px-4 py-2"><?= h($b['batch_no']) ?></td>
            <td class="px-4 py-2"><?= h($b['expiry_date']) ?></td>
            <td class="px-4 py-2"><?= h($b['quantity_received']) ?></td>
            <td class="px-4 py-2">
              <a class="text-blue-600" href="/HealthLogs/public/inventory/batches/form.php?id=<?= (int)$b['id'] ?>">Edit</a>
              <form method="post" action="/HealthLogs/public/inventory/batches/delete.php" class="inline">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>" />
                <button class="text-red-600 ml-2" data-confirm="Delete this batch?">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

