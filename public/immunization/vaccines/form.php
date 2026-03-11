<?php
require __DIR__ . '/../../partials/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rec = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM vaccines WHERE id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
}

$pageTitle = $rec ? 'Edit Vaccine' : 'New Vaccine';
require __DIR__ . '/../../partials/header.php';
?>

<div class="bg-white p-6 rounded shadow">
  <form method="post" action="/HealthLogs/public/immunization/vaccines/save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if ($rec): ?>
      <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>" />
    <?php endif; ?>

    <div>
      <label class="block text-sm text-slate-600">Name</label>
      <input name="name" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['name'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Code</label>
      <input name="code" required class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['code'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Min Age (months)</label>
      <input name="recommended_min_age_months" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['recommended_min_age_months'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Max Age (months)</label>
      <input name="recommended_max_age_months" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['recommended_max_age_months'] ?? '') ?>" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Doses Required</label>
      <input name="doses_required" type="number" class="mt-1 w-full border rounded px-3 py-2" value="<?= h($rec['doses_required'] ?? '') ?>" />
    </div>

    <div class="md:col-span-2 flex items-center gap-2 mt-2">
      <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Save</button>
      <a class="text-slate-600" href="/HealthLogs/public/immunization/vaccines/index.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
