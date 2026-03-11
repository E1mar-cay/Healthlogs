<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$pageTitle = $rec ? 'Edit Medicine' : 'New Medicine';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/inventory/medicines/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Name</label>
      <input name="name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Generic Name</label>
      <input name="generic_name" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['generic_name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Formulation</label>
      <input name="formulation" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['formulation'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Strength</label>
      <input name="strength" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['strength'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Unit</label>
      <input name="unit" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['unit'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Reorder Level</label>
      <input name="reorder_level" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['reorder_level'] ?? '') ?>" />
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/inventory/medicines/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
