<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM medicine_batches WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$meds = $pdo->query("SELECT id, name FROM medicines ORDER BY name ASC")->fetchAll();

$pageTitle = $rec ? 'Edit Batch' : 'New Batch';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/inventory/batches/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Medicine</label>
      <select name="medicine_id" required class="mt-1 w-full border rounded px-3 py-2">
        <?php foreach ($meds as $m): ?>
          <?php $sel = ($rec['medicine_id'] ?? 0) == $m['id'] ? 'selected' : ''; ?>
          <option value="<?= (int)$m['id'] ?>" <?= $sel ?>><?= h($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm text-slate-600">Batch No</label>
      <input name="batch_no" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['batch_no'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Expiry Date</label>
      <input name="expiry_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['expiry_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Received Date</label>
      <input name="received_date" type="date" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['received_date'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Quantity Received</label>
      <input name="quantity_received" type="number" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['quantity_received'] ?? '') ?>" />
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/inventory/batches/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
